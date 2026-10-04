<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import { toast, useMemberActions } from '@/Components/UI/useMemberActions';

/*
 * Whole-sermon "saved" (bookmark) or "favorite" (heart) toggle. Signed-in
 * users' lists live on the server (shared as `sermonSaves`); guests keep them
 * on this device.
 */
const props = defineProps({
    sermonId: { type: [Number, String], required: true },
    kind: { type: String, default: 'saved' }, // 'saved' | 'favorite'
});

const page = usePage();
const { isOn, toggle } = useMemberActions();
const signedIn = computed(() => !!page.props.auth?.user);
const localKind = computed(() => (props.kind === 'favorite' ? 'favorites' : 'bookmarks'));

const pending = ref(null); // optimistic value while the request is in flight
const on = computed(() => {
    if (pending.value !== null) return pending.value;
    return signedIn.value
        ? (page.props.sermonSaves?.[props.kind] ?? []).includes(Number(props.sermonId))
        : isOn(localKind.value, props.sermonId);
});

const LABELS = {
    saved: ['Saved to your sermons', 'Removed from saved'],
    favorite: ['Added to favorites', 'Removed from favorites'],
};

const click = () => {
    if (!signedIn.value) return toggle(localKind.value, props.sermonId);

    const next = !on.value;
    pending.value = next;
    router.post(route('ui.sermons.saves', props.sermonId), { kind: props.kind }, {
        preserveScroll: true,
        preserveState: true,
        only: ['sermonSaves'],
        onSuccess: () => toast(LABELS[props.kind][next ? 0 : 1]),
        onFinish: () => (pending.value = null),
    });
};
</script>

<template>
    <button
        class="bookmark"
        :class="{ on, fav: kind === 'favorite' }"
        :aria-label="kind === 'favorite' ? 'Favorite' : 'Save sermon'"
        :aria-pressed="on"
        @click.stop.prevent="click"
    >
        <Icon :name="kind === 'favorite' ? 'heart' : 'bookmark'" />
    </button>
</template>
