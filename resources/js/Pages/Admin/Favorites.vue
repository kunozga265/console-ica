<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import { fmtDate } from '@/Components/UI/helpers';

defineProps({
    favorites: { type: Array, default: () => [] },
    saved: { type: Array, default: () => [] },
    highlights: { type: Array, default: () => [] },
    bookmarks: { type: Array, default: () => [] },
    totals: { type: Object, required: true },
});

const LISTS = [
    { key: 'favorites', title: 'Most favourited', icon: 'heart', tone: 'red', unit: 'favourites' },
    { key: 'saved', title: 'Most saved', icon: 'bookmark', tone: 'blue', unit: 'saves' },
    { key: 'highlights', title: 'Most highlighted', icon: 'highlighter', tone: 'gold', unit: 'highlights' },
    { key: 'bookmarks', title: 'Most bookmarked lines', icon: 'bookmark', tone: 'plum', unit: 'bookmarks' },
];
</script>

<template>
    <AdminLayout title="Favorites" page="favorites" crumb="Engagement">
        <div class="grid stat-grid mb-20">
            <div v-for="l in LISTS" :key="l.key" class="stat">
                <div class="top"><span class="ico" :class="`ico-${l.tone}`"><Icon :name="l.icon" /></span><span class="label">{{ l.title.replace('Most ', '').replace(/^./, (c) => c.toUpperCase()) }}</span></div>
                <div class="value">{{ totals[l.key].toLocaleString() }}</div>
            </div>
        </div>
        <div class="grid cols-2">
            <section v-for="l in LISTS" :key="l.key" class="card">
                <div class="card-head"><h3>{{ l.title }}</h3></div>
                <div class="card-pad" style="padding-top: 4px">
                    <Link v-for="(s, i) in $props[l.key]" :key="s.id" class="list-row" :href="route('admin.sermons.edit', s.id)">
                        <span class="rank">{{ i + 1 }}</span>
                        <div class="grow"><div class="t">{{ s.title }}</div><div class="s">{{ s.author }} · {{ fmtDate(s.date) }}</div></div>
                        <span class="badge badge-outline">{{ s.count }} {{ l.unit }}</span>
                    </Link>
                    <p v-if="!$props[l.key].length" class="small muted" style="padding: 12px 0">Nothing yet.</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
