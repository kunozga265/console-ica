<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { authorName } from '@/Components/UI/helpers';

/*
 * Filter window for the sermon list: pick a minister and/or series (each list
 * is searchable) and a published-date range. Edits a draft; "Apply" emits it.
 */
const props = defineProps({
    filters: { type: Object, default: () => ({}) }, // { ministry?, author?, series?, from?, to? }
    ministers: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    ministries: { type: Array, default: () => [] },
});
const emit = defineEmits(['apply']);

const open = ref(false);
const root = ref(null);

const fromFilters = () => ({
    ministry: props.filters.ministry ? String(props.filters.ministry) : '',
    author: props.filters.author ? String(props.filters.author) : '',
    series: props.filters.series ? String(props.filters.series) : '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});
const draft = ref(fromFilters());
watch(open, (isOpen) => isOpen && (draft.value = fromFilters()));

// Ministry, minister, series and the date range count as one filter each.
const activeCount = computed(() => [props.filters.ministry, props.filters.author, props.filters.series, props.filters.from || props.filters.to].filter(Boolean).length);

/* ---- searchable lists ---- */
const ministerQuery = ref('');
const seriesQuery = ref('');
const matches = (text, q) => text.toLowerCase().includes(q.trim().toLowerCase());
const ministerOptions = computed(() => props.ministers.filter((m) => matches(authorName(m), ministerQuery.value)));
const seriesOptions = computed(() => props.series.filter((s) => matches(s.title, seriesQuery.value)));
const pick = (key, id) => (draft.value[key] = draft.value[key] === String(id) ? '' : String(id));

/* ---- date presets ---- */
const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const presets = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const daysAgo = (n) => ymd(new Date(now.getFullYear(), now.getMonth(), now.getDate() - n));
    return [
        { label: 'Last 30 days', from: daysAgo(30), to: '' },
        { label: 'Last 3 months', from: daysAgo(91), to: '' },
        { label: 'This year', from: `${y}-01-01`, to: '' },
        { label: String(y - 1), from: `${y - 1}-01-01`, to: `${y - 1}-12-31` },
    ];
});
const presetOn = (p) => draft.value.from === p.from && draft.value.to === p.to;
const applyPreset = (p) => (presetOn(p) ? Object.assign(draft.value, { from: '', to: '' }) : Object.assign(draft.value, { from: p.from, to: p.to }));

const apply = () => {
    emit('apply', { ...draft.value });
    open.value = false;
};
const reset = () => (draft.value = { ministry: '', author: '', series: '', from: '', to: '' });

/* ---- open/close ---- */
const onDocDown = (e) => open.value && root.value && !root.value.contains(e.target) && (open.value = false);
const onKey = (e) => e.key === 'Escape' && (open.value = false);
onMounted(() => {
    document.addEventListener('mousedown', onDocDown);
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocDown);
    document.removeEventListener('keydown', onKey);
});
</script>

<template>
    <div ref="root" class="filter-wrap">
        <button class="icon-btn filter-btn" :class="{ on: activeCount }" aria-label="Filter sermons" :aria-expanded="open" @click="open = !open">
            <Icon name="filter" />
            <span v-if="activeCount" class="count">{{ activeCount }}</span>
        </button>

        <div v-if="open" class="filter-panel" role="dialog" aria-label="Filter sermons">
            <div class="fp-head">
                <b>Filter sermons</b>
                <button class="icon-btn fp-close" aria-label="Close" @click="open = false"><Icon name="close" /></button>
            </div>

            <div class="fp-body">
                <div v-if="ministries.length" class="fp-group">
                    <div class="fp-label">Ministry</div>
                    <div class="chips">
                        <button v-for="m in ministries" :key="m.id" class="chip" :class="{ on: draft.ministry === String(m.id) }" @click="pick('ministry', m.id)">{{ m.name }}</button>
                    </div>
                </div>

                <div class="fp-group">
                    <div class="fp-label">Minister</div>
                    <label class="fp-search"><Icon name="search" /><input v-model="ministerQuery" type="search" placeholder="Search ministers…" /></label>
                    <div class="fp-list">
                        <button v-for="m in ministerOptions" :key="m.id" class="fp-option" :class="{ on: draft.author === String(m.id) }" @click="pick('author', m.id)">
                            <Avatar :person="m" size="sm" />
                            <span class="grow">{{ authorName(m) }}</span>
                            <span class="tiny muted">{{ m.sermonCount }}</span>
                        </button>
                        <p v-if="!ministerOptions.length" class="tiny muted fp-empty">No ministers match.</p>
                    </div>
                </div>

                <div class="fp-group">
                    <div class="fp-label">Series</div>
                    <label class="fp-search"><Icon name="search" /><input v-model="seriesQuery" type="search" placeholder="Search series…" /></label>
                    <div class="fp-list">
                        <button v-for="s in seriesOptions" :key="s.id" class="fp-option" :class="{ on: draft.series === String(s.id) }" @click="pick('series', s.id)">
                            <span class="grow">{{ s.title }}</span>
                            <span class="tiny muted">{{ s.sermonCount }}</span>
                        </button>
                        <p v-if="!seriesOptions.length" class="tiny muted fp-empty">No series match.</p>
                    </div>
                </div>

                <div class="fp-group">
                    <div class="fp-label">Date preached</div>
                    <div class="chips">
                        <button v-for="p in presets" :key="p.label" class="chip" :class="{ on: presetOn(p) }" @click="applyPreset(p)">{{ p.label }}</button>
                    </div>
                    <div class="fp-dates">
                        <label>From<input v-model="draft.from" type="date" :max="draft.to || undefined" /></label>
                        <label>To<input v-model="draft.to" type="date" :min="draft.from || undefined" /></label>
                    </div>
                </div>
            </div>

            <div class="fp-foot">
                <button class="btn btn-soft btn-sm" @click="reset">Reset</button>
                <button class="btn btn-primary btn-sm" @click="apply">Apply filters</button>
            </div>
        </div>
    </div>
</template>
