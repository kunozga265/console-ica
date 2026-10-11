<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Image from '@tiptap/extension-image';

/*
 * WYSIWYG editor (Tiptap); v-model is HTML.
 * Links are always available. Images are enabled by passing `upload-url`
 * (an endpoint taking `image` and returning { url }) — they can be inserted
 * from the toolbar, pasted or dropped in.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Write here…' },
    uploadUrl: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue']);

const uploading = ref(0);
const error = ref('');

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            link: { openOnClick: false, autolink: true, linkOnPaste: true, defaultProtocol: 'https' },
        }),
        Placeholder.configure({ placeholder: props.placeholder }),
        // Always registered so existing <img> in bodies survive editing, even where uploads are off.
        Image.configure({ inline: false, allowBase64: false }),
    ],
    editorProps: {
        attributes: { class: 'rich-text re-content' },
        handlePaste: (view, event) => insertFiles(event.clipboardData?.files),
        handleDrop: (view, event, slice, moved) => {
            if (moved) return false;
            const pos = view.posAtCoords({ left: event.clientX, top: event.clientY })?.pos;
            return insertFiles(event.dataTransfer?.files, pos);
        },
    },
    // An empty editor reports "<p></p>"; treat that as no content.
    onUpdate: ({ editor }) => emit('update:modelValue', editor.isEmpty ? '' : editor.getHTML()),
});

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== (editor.value.isEmpty ? '' : editor.value.getHTML())) {
        editor.value.commands.setContent(value || '', { emitUpdate: false });
    }
});

onBeforeUnmount(() => editor.value?.destroy());

const tools = [
    { label: 'B', title: 'Bold', name: 'bold', run: (c) => c.toggleBold(), style: 'font-weight:700' },
    { label: 'I', title: 'Italic', name: 'italic', run: (c) => c.toggleItalic(), style: 'font-style:italic' },
    { label: 'S', title: 'Strikethrough', name: 'strike', run: (c) => c.toggleStrike(), style: 'text-decoration:line-through' },
    { label: 'H2', title: 'Heading', name: 'heading', attrs: { level: 2 }, run: (c) => c.toggleHeading({ level: 2 }) },
    { label: 'H3', title: 'Subheading', name: 'heading', attrs: { level: 3 }, run: (c) => c.toggleHeading({ level: 3 }) },
    { label: '• List', title: 'Bullet list', name: 'bulletList', run: (c) => c.toggleBulletList() },
    { label: '1. List', title: 'Numbered list', name: 'orderedList', run: (c) => c.toggleOrderedList() },
    { label: '“ Quote', title: 'Quote', name: 'blockquote', run: (c) => c.toggleBlockquote() },
];
const use = (t) => t.run(editor.value.chain().focus()).run();

/* ---- Links ---- */
const linking = ref(false);
const linkUrl = ref('');
const linkInput = ref(null);
let linkRange = null; // the selection when the link bar opened (focus moves to its input)
const openLink = () => {
    const { from, to } = editor.value.state.selection;
    linkRange = { from, to };
    linkUrl.value = editor.value.getAttributes('link').href ?? '';
    linking.value = true;
    nextTick(() => linkInput.value?.focus());
};
const applyLink = () => {
    let url = linkUrl.value.trim();
    const chain = editor.value.chain().focus();
    if (linkRange) chain.setTextSelection(linkRange);
    chain.extendMarkRange('link');
    const empty = linkRange ? linkRange.from === linkRange.to : editor.value.state.selection.empty;
    if (!url) {
        chain.unsetLink().run();
    } else {
        if (!/^(https?:|mailto:|tel:|#|\/)/i.test(url)) url = `https://${url}`;
        if (empty && !editor.value.isActive('link')) {
            // Nothing selected: insert the URL itself as the link text.
            chain.insertContent({ type: 'text', text: url, marks: [{ type: 'link', attrs: { href: url } }] }).run();
        } else {
            chain.setLink({ href: url }).run();
        }
    }
    linking.value = false;
};
const removeLink = () => {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    linking.value = false;
};

/* ---- Images ---- */
const fileInput = ref(null);

// Resize big photos in the browser (max 1600px, re-encoded) so uploads stay small.
const shrink = (file) =>
    new Promise((resolve) => {
        if (file.type === 'image/gif' || file.type === 'image/svg+xml') return resolve(file);
        const url = URL.createObjectURL(file);
        const img = new window.Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            const scale = Math.min(1, 1600 / Math.max(img.width, img.height));
            if (scale === 1 && file.size < 1.5 * 1024 * 1024) return resolve(file);
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            const type = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
            canvas.toBlob(
                (blob) => resolve(blob ? new File([blob], file.name.replace(/\.\w+$/, type === 'image/png' ? '.png' : '.jpg'), { type }) : file),
                type,
                0.85,
            );
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(file);
        };
        img.src = url;
    });

const upload = async (file, pos) => {
    error.value = '';
    uploading.value++;
    try {
        const body = new FormData();
        body.append('image', await shrink(file));
        const { data } = await window.axios.post(props.uploadUrl, body);
        const node = { type: 'image', attrs: { src: data.url, alt: file.name.replace(/\.\w+$/, '') } };
        const chain = editor.value.chain().focus();
        (pos != null ? chain.insertContentAt(pos, node) : chain.insertContent(node)).run();
    } catch (e) {
        error.value = e.response?.data?.errors?.image?.[0] ?? e.response?.data?.message ?? "Couldn't upload that image.";
    } finally {
        uploading.value--;
    }
};

// Returns true when it took the event (image files and uploads enabled).
function insertFiles(files, pos) {
    const images = [...(files ?? [])].filter((f) => f.type.startsWith('image/'));
    if (!props.uploadUrl || !images.length) return false;
    images.forEach((f) => upload(f, pos));
    return true;
}
const pickImage = () => fileInput.value?.click();
const onPicked = (e) => {
    insertFiles(e.target.files);
    e.target.value = '';
};
</script>

<template>
    <div class="rich-editor">
        <div v-if="editor" class="re-toolbar" role="toolbar" aria-label="Formatting">
            <button
                v-for="t in tools"
                :key="t.title"
                type="button"
                :title="t.title"
                :class="{ on: editor.isActive(t.name, t.attrs || {}) }"
                :style="t.style"
                @click="use(t)"
            >{{ t.label }}</button>
            <button type="button" title="Link (paste a URL)" :class="{ on: editor.isActive('link') || linking }" @click="openLink">Link</button>
            <template v-if="uploadUrl">
                <button type="button" title="Insert image (or paste / drop one in)" :disabled="uploading > 0" @click="pickImage">
                    {{ uploading ? 'Uploading…' : 'Image' }}
                </button>
                <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/gif,image/webp" multiple hidden @change="onPicked" />
            </template>
        </div>

        <form v-if="linking" class="re-linkbar" @submit.prevent="applyLink">
            <input ref="linkInput" v-model="linkUrl" type="text" inputmode="url" placeholder="https://www.bible.com/bible/59/JHN.3.16" aria-label="Link URL" @keydown.esc.prevent="linking = false" />
            <button type="submit" class="re-linkbtn primary">Apply</button>
            <button v-if="editor.isActive('link')" type="button" class="re-linkbtn" @click="removeLink">Remove</button>
            <button type="button" class="re-linkbtn" @click="linking = false">Cancel</button>
        </form>
        <p v-if="error" class="re-error">{{ error }}</p>

        <EditorContent :editor="editor" />
    </div>
</template>
