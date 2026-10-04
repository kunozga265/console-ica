<script setup>
import { router } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';

/* "Showing x–y of n" + numbered pages; keeps the current query string. */
const props = defineProps({
    paging: { type: Object, required: true }, // { page, lastPage, total, from?, to? }
    noun: { type: String, default: 'items' },
});

const go = (page) => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: false });
};
const pages = () => {
    const { page, lastPage } = props.paging;
    const set = new Set([1, lastPage, page - 1, page, page + 1].filter((p) => p >= 1 && p <= lastPage));
    const list = [...set].sort((a, b) => a - b);
    return list.flatMap((p, i) => (i && p - list[i - 1] > 1 ? ['…', p] : [p]));
};
</script>

<template>
    <div class="between pager-row">
        <span class="small muted">
            <template v-if="paging.total">Showing {{ paging.from ?? '' }}<template v-if="paging.from">–{{ paging.to }} of </template>{{ paging.total.toLocaleString() }} {{ noun }}</template>
            <template v-else>No {{ noun }}</template>
        </span>
        <div v-if="paging.lastPage > 1" class="flex gap-8">
            <button class="btn btn-ghost btn-sm btn-icon" :disabled="paging.page <= 1" aria-label="Previous page" @click="go(paging.page - 1)"><Icon name="chevright" style="transform: rotate(180deg)" /></button>
            <template v-for="(p, i) in pages()" :key="i">
                <span v-if="p === '…'" class="small muted">…</span>
                <button v-else class="btn btn-sm btn-icon" :class="p === paging.page ? 'btn-primary' : 'btn-ghost'" @click="go(p)">{{ p }}</button>
            </template>
            <button class="btn btn-ghost btn-sm btn-icon" :disabled="paging.page >= paging.lastPage" aria-label="Next page" @click="go(paging.page + 1)"><Icon name="chevright" /></button>
        </div>
    </div>
</template>
