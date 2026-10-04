<script setup>
import { computed, ref } from 'vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import { copyText } from '@/Components/UI/helpers';
import { toast } from '@/Components/UI/useMemberActions';

/*
 * Share: the device's native share sheet where available (phones, Safari, Edge…),
 * otherwise a dialog with copy-link and common share targets.
 */
const props = defineProps({
    title: { type: String, required: true },
    url: { type: String, required: true },
    text: { type: String, default: '' },
});

const open = ref(false);
const copied = ref(false);

const share = async () => {
    const data = { title: props.title, text: props.text || props.title, url: props.url };
    if (navigator.share && (!navigator.canShare || navigator.canShare(data))) {
        try {
            await navigator.share(data);
            return;
        } catch (e) {
            if (e?.name === 'AbortError') return; // user closed the sheet
        }
    }
    copied.value = false;
    open.value = true;
};

const copy = async () => {
    copied.value = await copyText(props.url);
    toast(copied.value ? 'Link copied to clipboard' : 'Select the link and copy it');
};

const enc = encodeURIComponent;
const targets = computed(() => [
    { label: 'WhatsApp', href: `https://wa.me/?text=${enc(`${props.title} ${props.url}`)}`, color: '#25d366' },
    { label: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${enc(props.url)}`, color: '#1877f2' },
    { label: 'X', href: `https://twitter.com/intent/tweet?text=${enc(props.title)}&url=${enc(props.url)}`, color: '#111111' },
    { label: 'Email', href: `mailto:?subject=${enc(props.title)}&body=${enc(`${props.text || props.title}\n\n${props.url}`)}`, color: '#5b6b7a' },
]);
</script>

<template>
    <button class="btn btn-ghost btn-sm" type="button" @click="share"><Icon name="share" /> Share</button>

    <Modal v-if="open" title="Share" width="420px" @close="open = false">
        <p class="small muted clamp-2">{{ title }}</p>
        <div class="share-link mt-12">
            <input :value="url" readonly aria-label="Link" @focus="$event.target.select()" />
            <button class="btn btn-primary btn-sm" type="button" @click="copy"><Icon :name="copied ? 'check' : 'copy'" /> {{ copied ? 'Copied' : 'Copy' }}</button>
        </div>
        <div class="share-targets mt-16">
            <a v-for="t in targets" :key="t.label" :href="t.href" target="_blank" rel="noopener" class="share-target">
                <span :style="{ background: t.color }">{{ t.label[0] }}</span>{{ t.label }}
            </a>
        </div>
    </Modal>
</template>
