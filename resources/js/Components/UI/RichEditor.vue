<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';

/* WYSIWYG editor (Tiptap) styled for the /ui pages; v-model is HTML. */
const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Write here…' },
});
const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [StarterKit.configure({ heading: { levels: [2, 3] } }), Placeholder.configure({ placeholder: props.placeholder })],
    editorProps: { attributes: { class: 'rich-text re-content' } },
    // An empty editor reports "<p></p>"; treat that as no note.
    onUpdate: ({ editor }) => emit('update:modelValue', editor.isEmpty ? '' : editor.getHTML()),
});

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== (editor.value.isEmpty ? '' : editor.value.getHTML())) {
        editor.value.commands.setContent(value || '', false);
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
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
