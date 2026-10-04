<script setup>
import Icon from '@/Components/UI/Icon.vue';
import { useMemberActions } from '@/Components/UI/useMemberActions';

/* Register-for-event / mark-attendance button: ghost when off, primary when on. */
defineProps({
    kind: { type: String, required: true }, // 'events' | 'attendance'
    itemId: { type: [Number, String], required: true },
    offLabel: { type: String, required: true },
    onLabel: { type: String, required: true },
    offClass: { type: String, default: 'btn-ghost' },
});

const { isOn, toggle } = useMemberActions();
</script>

<template>
    <button
        class="btn"
        :class="isOn(kind, itemId) ? 'btn-primary' : offClass"
        @click="toggle(kind, itemId)"
    >
        <template v-if="isOn(kind, itemId)"><Icon name="check" /> {{ onLabel }}</template>
        <template v-else>{{ offLabel }}</template>
    </button>
</template>
