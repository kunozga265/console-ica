<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    resources: { type: Array, default: () => [] }, // { id, title, type, description, date, url, previewUrl, downloadUrl }
});

const search = ref('');
const shown = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? props.resources.filter((r) => `${r.title} ${r.description ?? ''}`.toLowerCase().includes(q)) : props.resources;
});

const KIND = {
    pdf: { label: 'PDF', tone: 'red' },
    ppt: { label: 'Slides', tone: 'gold' },
    pptx: { label: 'Slides', tone: 'gold' },
    doc: { label: 'Document', tone: 'blue' },
    docx: { label: 'Document', tone: 'blue' },
    xls: { label: 'Spreadsheet', tone: 'green' },
    xlsx: { label: 'Spreadsheet', tone: 'green' },
};
const kind = (r) => KIND[r.type] ?? { label: (r.type || 'File').toUpperCase(), tone: 'plum' };

/* ---- In-page viewer ---- */
const viewing = ref(null);
const onKey = (e) => e.key === 'Escape' && (viewing.value = null);
onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <UILayout title="Resources" page="resources" :breadcrumbs="[{ label: 'More' }, { label: 'Resources' }]">
        <div class="panel mb-24">
            <div class="section-head">
                <h2>Resources</h2>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 260px"><Icon name="search" /><input v-model="search" type="search" placeholder="Search resources…" /></label>
            </div>

            <div v-if="shown.length" class="grid cols-2">
                <article v-for="r in shown" :key="r.id" class="card card-pad resource-card">
                    <span class="ico" :class="`ico-${kind(r).tone}`" style="width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; flex: none">
                        <Icon name="book" />
                    </span>
                    <div class="grow" style="min-width: 0">
                        <h4 style="font-family: var(--font-serif); font-weight: 600; font-size: 16px; color: var(--ink)">{{ r.title }}</h4>
                        <p v-if="r.description" class="small muted clamp-2 mt-4">{{ r.description }}</p>
                        <div class="tiny muted mt-4">{{ kind(r).label }}<template v-if="r.date"> · {{ fmtDate(r.date) }}</template></div>
                        <div class="flex gap-8 mt-12 wrap">
                            <button v-if="r.previewUrl" class="btn btn-primary btn-sm" @click="viewing = r"><Icon name="book" /> View</button>
                            <a class="btn btn-ghost btn-sm" :href="r.downloadUrl" target="_blank" rel="noopener"><Icon name="download" /> Download</a>
                            <a v-if="!r.previewUrl" class="btn btn-ghost btn-sm" :href="r.url" target="_blank" rel="noopener"><Icon name="ext" /> Open</a>
                        </div>
                    </div>
                </article>
            </div>
            <div v-else class="card card-pad empty-state">
                <Icon name="folder" />
                <h3>{{ search ? 'No resources match' : 'No resources yet' }}</h3>
            </div>
        </div>

        <div v-if="viewing" class="viewer" role="dialog" :aria-label="viewing.title" @click.self="viewing = null">
            <div class="viewer-box">
                <div class="viewer-head">
                    <b class="grow" style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ viewing.title }}</b>
                    <a class="btn btn-ghost btn-sm" :href="viewing.downloadUrl" target="_blank" rel="noopener"><Icon name="download" /> Download</a>
                    <a class="btn btn-ghost btn-sm hide-sm" :href="viewing.url" target="_blank" rel="noopener"><Icon name="ext" /> Open</a>
                    <button class="icon-btn" aria-label="Close" @click="viewing = null"><Icon name="close" /></button>
                </div>
                <iframe :src="viewing.previewUrl" :title="viewing.title" allow="autoplay" allowfullscreen></iframe>
            </div>
        </div>
    </UILayout>
</template>
