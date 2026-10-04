<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import SermonMiniCard from '@/Components/UI/SermonMiniCard.vue';
import SermonCard from '@/Components/UI/SermonCard.vue';
import SeriesCard from '@/Components/UI/SeriesCard.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import SermonFilters from '@/Components/UI/SermonFilters.vue';
import { authorName, fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    sermons: { type: Array, required: true },
    pagination: { type: Object, required: true }, // { page, total, hasMore }
    filters: { type: Object, default: () => ({}) }, // { search?, author?, series?, from?, to? }
    series: { type: Array, default: () => [] },
    ministers: { type: Array, default: () => [] },
    ministries: { type: Array, default: () => [] },
});

const tabs = [
    { label: 'All', target: '#all' },
    { label: 'Series', target: '#series' },
    { label: 'Ministers', target: '#ministers' },
];

/* ---- Filters: a full visit replaces the list; only "Load more" merges ---- */
const visit = (params, { scrollToList = false } = {}) => {
    const query = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''));
    router.get(route('ui.sermons.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: () => scrollToList && document.getElementById('all')?.scrollIntoView({ behavior: 'smooth', block: 'start' }),
    });
};

const search = ref(props.filters.search ?? '');
let debounce = null;
watch(search, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => visit({ ...props.filters, search: value }), 300);
});

const filterBy = (key, id) => visit({ search: search.value, [key]: id }, { scrollToList: true });
const clearFilters = () => {
    search.value = '';
    visit({});
};

// Quick filters: the three most prolific ministers and the two most recent series.
const chips = computed(() => [
    ...props.ministers.slice(0, 3).map((m) => ({ key: 'author', id: m.id, label: authorName(m) })),
    ...props.series.slice(0, 2).map((s) => ({ key: 'series', id: s.id, label: s.title })),
]);
const noFilter = computed(() => !props.filters.author && !props.filters.series);
const isOn = (chip) => String(props.filters[chip.key] ?? '') === String(chip.id);
const toggleChip = (chip) => (isOn(chip) ? visit({ search: search.value }) : filterBy(chip.key, chip.id));

/* ---- Filter window + removable pills for what's active ---- */
const applyFilters = (f) => visit({ search: search.value, ...f }, { scrollToList: true });
const without = (...keys) => visit(Object.fromEntries(Object.entries({ ...props.filters, search: search.value }).filter(([k]) => !keys.includes(k))));

const ymdLabel = (v) => fmtDate(new Date(`${v}T00:00:00`));
const pills = computed(() => {
    const f = props.filters;
    const list = [];
    if (f.ministry) {
        const mi = props.ministries.find((x) => String(x.id) === String(f.ministry));
        list.push({ label: mi ? mi.name : 'Ministry', keys: ['ministry'] });
    }
    if (f.author) {
        const m = props.ministers.find((x) => String(x.id) === String(f.author));
        list.push({ label: `By ${m ? authorName(m) : 'minister'}`, keys: ['author'] });
    }
    if (f.series) {
        const se = props.series.find((x) => String(x.id) === String(f.series));
        list.push({ label: `Series: ${se ? se.title : 'selected'}`, keys: ['series'] });
    }
    if (f.from || f.to) {
        const label = f.from && f.to ? `${ymdLabel(f.from)} – ${ymdLabel(f.to)}` : f.from ? `Since ${ymdLabel(f.from)}` : `Until ${ymdLabel(f.to)}`;
        list.push({ label, keys: ['from', 'to'] });
    }
    return list;
});

/* ---- Minister avatars: tooltip lives outside the scrolling row so it isn't clipped ---- */
const tip = ref(null); // { x, y, minister }
const showTip = (e, m) => {
    const r = e.currentTarget.getBoundingClientRect();
    tip.value = { x: r.left + r.width / 2, y: r.top, minister: m };
};
const hideTip = () => (tip.value = null);
onMounted(() => document.addEventListener('scroll', hideTip, true));
onBeforeUnmount(() => document.removeEventListener('scroll', hideTip, true));

/* ---- Load more: partial reload of the next page, merged onto the list ---- */
const loading = ref(false);
const loadMore = () => {
    router.reload({
        only: ['sermons', 'pagination'],
        data: { page: props.pagination.page + 1 },
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};
</script>

<template>
    <UILayout title="Sermons" page="sermons" >
        <div class="panel mb-24">
            <section id="all" class="section">
                <div class="section-head">
                    <h2>All sermons</h2>
                    <div class="spacer"></div>
                    <label class="searchbar" style="max-width: 260px">
                        <Icon name="search" /><input v-model="search" type="search" placeholder="Search sermons…" />
                    </label>
                    <SermonFilters :filters="filters" :ministers="ministers" :series="series" :ministries="ministries" @apply="applyFilters" />
                </div>
                <!-- <div class="chips mb-20">
                    <button class="chip" :class="{ on: noFilter }" @click="visit({ search })">All</button>
                    <button v-for="c in chips" :key="`${c.key}-${c.id}`" class="chip" :class="{ on: isOn(c) }" @click="toggleChip(c)">{{ c.label }}</button>
                </div> -->

                <div v-if="pills.length || filters.search" class="active-filters mb-16">
                    <span class="small muted">
                        {{ pagination.total }} {{ pagination.total === 1 ? 'sermon' : 'sermons' }}<template v-if="filters.search"> matching “{{ filters.search }}”</template>
                    </span>
                    <button v-for="p in pills" :key="p.label" class="pill" :aria-label="`Remove filter: ${p.label}`" @click="without(...p.keys)">
                        {{ p.label }} <Icon name="close" />
                    </button>
                    <button class="link small" style="font-weight: 600; color: var(--accent-2); margin-left: auto" @click="clearFilters">Clear all</button>
                </div>

                <div v-if="sermons.length" class="grid-sermons">
                    <SermonCard v-for="s in sermons" :key="s.id" :sermon="s" />
                </div>
                <div v-else class="card card-pad empty-state">
                    <Icon name="search" />
                    <h3>No sermons found</h3>
                    <p class="small muted">Try a different search or clear the filters.</p>
                </div>

                <div v-if="pagination.hasMore" class="mt-24" style="text-align: center">
                    <button class="btn btn-ghost" :disabled="loading" @click="loadMore">
                        {{ loading ? 'Loading…' : `Load more (${pagination.total - sermons.length} more)` }}
                    </button>
                </div>
            </section>
        </div>

      

        <div v-if="ministers.length" class="panel mb-24">
            <section id="ministers" class="section">
                <div class="section-head"><h2>Ministers</h2></div>
                <div class="hrow minister-row" @scroll.passive="hideTip">
                    <button
                        v-for="m in ministers"
                        :key="m.id"
                        class="minister-avatar"
                        :class="{ on: String(filters.author) === String(m.id) }"
                        :aria-label="`${authorName(m)} — view sermons`"
                        @click="filterBy('author', m.id)"
                        @mouseenter="showTip($event, m)"
                        @mouseleave="hideTip"
                        @focus="showTip($event, m)"
                        @blur="hideTip"
                    >
                        <Avatar :person="m" size="xl" />
                    </button>
                </div>
            </section>
        </div>

        <div v-if="tip" class="ui-tooltip" :style="{ left: tip.x + 'px', top: tip.y + 'px' }" role="tooltip">
            <b>{{ authorName(tip.minister) }}</b>
            <span v-if="tip.minister.title">{{ tip.minister.title }}</span>
            <span>{{ tip.minister.sermonCount }} {{ tip.minister.sermonCount === 1 ? 'sermon' : 'sermons' }}</span>
        </div>

        <div v-if="series.length" class="panel mb-24">
            <section id="series" class="section">
                <div class="section-head"><h2>Series</h2></div>
                <div class="hrow" style="--w: 320px">
                    <SeriesCard v-for="se in series" :key="se.id" :series="se" />
                </div>
            </section>
        </div>
    </UILayout>
</template>
