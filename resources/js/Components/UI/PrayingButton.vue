<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import { toast } from '@/Components/UI/useMemberActions';

/* "I'm praying" toggle with the number of people praying. Signed-in users only. */
const props = defineProps({
    prayer: { type: Object, required: true }, // { id, praying, prayingCount }
    // Props to refresh afterwards. Leave empty on lists that use "load more"
    // (merge props), where a refresh would append duplicates; the button then
    // keeps its own state.
    reload: { type: Array, default: () => [] },
});

const page = usePage();
const busy = ref(false);
const optimistic = ref(null); // local value; kept after success when nothing is reloaded
const praying = computed(() => optimistic.value ?? props.prayer.praying);
const count = computed(() => props.prayer.prayingCount + (optimistic.value === null ? 0 : optimistic.value === props.prayer.praying ? 0 : optimistic.value ? 1 : -1));

const click = () => {
    if (!page.props.auth?.user) return (window.location.href = route('login'));
    optimistic.value = !praying.value;
    busy.value = true;
    const next = optimistic.value;
    router.post(route('ui.prayer.praying', props.prayer.id), {}, {
        preserveScroll: true,
        preserveState: true,
        only: props.reload.length ? props.reload : ['pagination'],
        onSuccess: () => {
            toast(next ? 'Thank you for praying 🙏' : 'Removed from your prayers');
            if (props.reload.length) optimistic.value = null; // fresh props now carry the state
        },
        onError: () => (optimistic.value = null),
        onFinish: () => (busy.value = false),
    });
};
</script>

<template>
    <button class="btn btn-sm praying-btn" :class="praying ? 'btn-primary' : 'btn-soft'" :disabled="busy" :aria-pressed="praying" @click.stop.prevent="click">
        <Icon name="pray" /> {{ praying ? "I'm praying" : 'Pray' }}
        <span v-if="count" class="praying-count">{{ count }}</span>
    </button>
</template>
