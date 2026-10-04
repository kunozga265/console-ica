<script setup>
import { ref, watch } from 'vue';
import { fileUrl } from '@/Plugins/composables';
import { avClass, initials } from '@/Components/UI/helpers';

/* Person avatar: their uploaded image when present and loadable, otherwise tinted initials. */
const props = defineProps({
    person: { type: Object, required: true }, // { id, name, avatar }
    size: { type: String, default: '' }, // '', 'sm', 'lg'
});

const failed = ref(false);
watch(() => props.person.avatar, () => (failed.value = false));
</script>

<template>
    <img
        v-if="person.avatar && !failed"
        class="avatar"
        :class="size && `avatar-${size}`"
        :src="fileUrl(person.avatar)"
        :alt="person.name"
        @error="failed = true"
    />
    <span v-else class="avatar" :class="[size && `avatar-${size}`, avClass(person.id)]">{{ initials(person.name) }}</span>
</template>
