<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Pager from '@/Components/Admin/Pager.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate, tone } from '@/Components/UI/helpers';

const props = defineProps({
    sermons: { type: Array, default: () => [] },
    paging: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    authors: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    ministries: { type: Array, default: () => [] },
});

const { submit } = useSubmit();

const savedView = (() => {
    try {
        return localStorage.getItem('ica-admin-sermon-view');
    } catch (e) {
        return null;
    }
})();
const view = ref(savedView || 'gallery');
watch(view, (v) => {
    try {
        localStorage.setItem('ica-admin-sermon-view', v);
    } catch (e) {}
});

const f = ref({ search: '', author: '', series: '', ministry: '', status: '', ...props.filters });
const apply = () =>
    router.get(route('admin.sermons.index'), Object.fromEntries(Object.entries(f.value).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
let debounce;
watch(() => f.value.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(apply, 300);
});

const STATUS = { published: ['Published', 'badge-green'], scheduled: ['Scheduled', 'badge-blue'], deleted: ['Deleted', 'badge-red'] };
const deleting = ref(null);
const destroy = () => submit('delete', route('admin.sermons.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
const restore = (s) => submit('post', route('admin.sermons.restore', s.id));
</script>

<template>
    <AdminLayout title="Sermons" page="sermons" crumb="Content">
        <div class="between wrap mb-20" style="gap: 14px">
            <div class="chips">
                <label class="searchbar" style="max-width: 240px; min-width: 180px"><Icon name="search" /><input v-model="f.search" type="search" placeholder="Search titles…" /></label>
                <select v-model="f.author" class="chip-select" @change="apply"><option value="">All ministers</option><option v-for="a in authors" :key="a.id" :value="a.id">{{ a.name }}</option></select>
                <select v-model="f.series" class="chip-select" @change="apply"><option value="">All series</option><option v-for="s in series" :key="s.id" :value="s.id">{{ s.title }}</option></select>
                <select v-model="f.ministry" class="chip-select" @change="apply"><option value="">All ministries</option><option v-for="m in ministries" :key="m.id" :value="m.id">{{ m.name }}</option></select>
                <select v-model="f.status" class="chip-select" @change="apply"><option value="">Any status</option><option value="published">Published</option><option value="scheduled">Scheduled</option><option value="deleted">Deleted</option></select>
            </div>
            <div class="flex gap-8">
                <div class="segmented hide-sm">
                    <button :class="{ on: view === 'gallery' }" @click="view = 'gallery'">Gallery</button>
                    <button :class="{ on: view === 'list' }" @click="view = 'list'">List</button>
                </div>
                <Link class="btn btn-primary" :href="route('admin.sermons.create')"><Icon name="plus" /> New sermon</Link>
            </div>
        </div>

        <div v-if="!sermons.length" class="card card-pad empty-state"><Icon name="mic" /><h3>No sermons match</h3></div>

        <div v-else-if="view === 'gallery'" class="grid cols-3">
            <article v-for="(s, i) in sermons" :key="s.id" class="tile reveal" :style="{ animationDelay: `${i * 0.03}s` }">
                <Link class="thumb" :href="route('admin.sermons.edit', s.id)" :style="{ background: tone(s.title) }">
                    <div class="grad"></div>
                    <span class="tag badge" :class="STATUS[s.status][1]">{{ STATUS[s.status][0] }}</span>
                    <span class="play"><Icon :name="s.hasVideo ? 'play' : 'book'" /></span>
                    <span class="dur">{{ s.views.toLocaleString() }} views</span>
                </Link>
                <div class="body">
                    <h4>{{ s.title }}</h4>
                    <div class="between">
                        <div class="meta">
                            <Avatar v-if="s.author" :person="s.author" size="sm" />
                            <div class="who"><b>{{ s.author?.name }}</b><span>{{ fmtDate(s.date) }}</span></div>
                        </div>
                        <span v-if="s.series" class="badge badge-outline clip">{{ s.series }}</span>
                    </div>
                    <div class="tile-actions">
                        <Link class="btn btn-soft btn-sm" :href="route('admin.sermons.edit', s.id)"><Icon name="pencil" /> Edit</Link>
                        <a v-if="s.status === 'published'" class="btn btn-soft btn-sm" :href="route('ui.sermons.show', s.slug)" target="_blank"><Icon name="eye" /> View</a>
                        <button v-if="s.status === 'deleted'" class="btn btn-soft btn-sm" @click="restore(s)"><Icon name="restore" /> Restore</button>
                        <button v-else class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = s"><Icon name="trash" /></button>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="card">
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Sermon</th><th>Minister</th><th>Series</th><th>Ministry</th><th>Date</th><th>Views</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="s in sermons" :key="s.id">
                            <td>
                                <Link class="cell-main" :href="route('admin.sermons.edit', s.id)">
                                    <div class="thumb-sm" :style="{ background: tone(s.title), display: 'grid', placeItems: 'center', color: 'rgba(255,255,255,.85)' }"><Icon :name="s.hasVideo ? 'play' : 'book'" /></div>
                                    <div><div class="t">{{ s.title }}</div><div class="s">{{ s.subtitle || (s.hasVideo ? 'Text + video' : 'Text') }}</div></div>
                                </Link>
                            </td>
                            <td><div class="cell-main"><Avatar v-if="s.author" :person="s.author" size="sm" /><span class="small" style="font-weight: 600">{{ s.author?.name }}</span></div></td>
                            <td><span v-if="s.series" class="badge badge-outline">{{ s.series }}</span></td>
                            <td class="small muted">{{ s.ministry }}</td>
                            <td class="small muted">{{ fmtDate(s.date) }}</td>
                            <td class="small" style="font-weight: 600">{{ s.views.toLocaleString() }}</td>
                            <td><span class="badge" :class="STATUS[s.status][1]"><span class="bullet"></span>{{ STATUS[s.status][0] }}</span></td>
                            <td class="row-actions">
                                <Link class="btn btn-soft btn-sm btn-icon" :href="route('admin.sermons.edit', s.id)" aria-label="Edit"><Icon name="pencil" /></Link>
                                <button v-if="s.status === 'deleted'" class="btn btn-soft btn-sm btn-icon" aria-label="Restore" @click="restore(s)"><Icon name="restore" /></button>
                                <button v-else class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = s"><Icon name="trash" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Pager class="mt-24" :paging="paging" noun="sermons" />

        <ConfirmDialog
            v-if="deleting"
            title="Delete sermon"
            :message="`Delete “${deleting.title}”? It disappears from the site and app. You can restore it later from the Deleted filter.`"
            @confirm="destroy"
            @close="deleting = null"
        />
    </AdminLayout>
</template>
