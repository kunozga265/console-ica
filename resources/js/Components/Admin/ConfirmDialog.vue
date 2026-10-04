<script setup>
import Modal from '@/Components/UI/Modal.vue';

/* Small "are you sure?" dialog. */
defineProps({
    title: { type: String, required: true },
    message: { type: String, required: true },
    confirmLabel: { type: String, default: 'Delete' },
    danger: { type: Boolean, default: true },
});
const emit = defineEmits(['confirm', 'close']);
</script>

<template>
    <Modal :title="title" width="440px" @close="emit('close')">
        <p class="small" style="line-height: 1.6">{{ message }}</p>
        <template #footer>
            <button class="btn btn-soft btn-sm" @click="emit('close')">Cancel</button>
            <button class="btn btn-sm" :class="danger ? 'btn-danger' : 'btn-primary'" @click="emit('confirm')">{{ confirmLabel }}</button>
        </template>
    </Modal>
</template>
