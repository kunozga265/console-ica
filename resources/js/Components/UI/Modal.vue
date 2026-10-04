<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import Icon from '@/Components/UI/Icon.vue';

/* Centered dialog for the /ui pages (bottom sheet on phones). */
defineProps({
    title: { type: String, required: true },
    width: { type: String, default: '480px' },
});
const emit = defineEmits(['close']);

const onKey = (e) => e.key === 'Escape' && emit('close');
onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="ui-modal" role="dialog" :aria-label="title" @mousedown.self="emit('close')">
        <div class="ui-modal-box" :style="{ maxWidth: width }">
            <div class="fp-head">
                <b>{{ title }}</b>
                <button class="icon-btn fp-close" aria-label="Close" @click="emit('close')"><Icon name="close" /></button>
            </div>
            <div class="ui-modal-body"><slot /></div>
            <div v-if="$slots.footer" class="fp-foot"><slot name="footer" /></div>
        </div>
    </div>
</template>
