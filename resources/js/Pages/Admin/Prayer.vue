<script setup>
import { reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import RichEditor from '@/Components/UI/RichEditor.vue';
import Pager from '@/Components/Admin/Pager.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';

const props = defineProps({
    prayers: { type: Array, default: () => [] },
    paging: { type: Object, required: true },
    search: { type: String, default: '' },
    stats: { type: Object, required: true },
});

const { busy, firstError, submit } = useSubmit();
const q = ref(props.search);
let debounce;
watch(q, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => router.get(route('admin.prayer.index'), v ? { search: v } : {}, { preserveState: true, replace: true }), 300);
});

const STATE = { today: ['Today', 'badge-green'], scheduled: ['Scheduled', 'badge-blue'], past: ['Past', 'badge-outline'] };
const fmtDay = (ymd) => new Date(`${ymd}T00:00:00`).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
const nextDay = () => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
};

const editing = ref(null);
const form = reactive({ title: '', verses: '', body: '', date: '' });
const open = (p = null) => {
    Object.assign(form, { title: p?.title ?? '', verses: p?.verses ?? '', body: p?.body ?? '', date: p?.date ?? nextDay() });
    editing.value = p ?? {};
};
const save = () =>
    editing.value.id
        ? submit('put', route('admin.prayer.update', editing.value.id), { ...form }, { onDone: () => (editing.value = null) })
        : submit('post', route('admin.prayer.store'), { ...form }, { onDone: () => (editing.value = null) });

const deleting = ref(null);
const destroy = () => submit('delete', route('admin.prayer.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Prayer Points" page="prayer" crumb="Engagement">
        <div class="grid stat-grid mb-20 three">
            <div class="stat"><div class="top"><span class="ico ico-plum"><Icon name="pray" /></span><span class="label">Prayer points</span></div><div class="value">{{ stats.total.toLocaleString() }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-blue"><Icon name="calendar" /></span><span class="label">Scheduled ahead</span></div><div class="value">{{ stats.scheduled }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-green"><Icon name="heart" /></span><span class="label">“I'm praying” taps</span></div><div class="value">{{ stats.praying.toLocaleString() }}</div></div>
        </div>

        <section class="card">
            <div class="card-head wrap" style="gap: 12px">
                <h3>All prayer points</h3>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 260px"><Icon name="search" /><input v-model="q" type="search" placeholder="Search title or verses…" /></label>
                <button class="btn btn-primary btn-sm" @click="open()"><Icon name="plus" /> New prayer point</button>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Date</th><th>Prayer point</th><th>Praying</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="p in prayers" :key="p.id">
                            <td class="small" style="white-space: nowrap">{{ fmtDay(p.date) }}</td>
                            <td><div class="t">{{ p.title }}</div><div class="s">{{ p.verses }}</div></td>
                            <td class="small" style="font-weight: 600">{{ p.praying }}</td>
                            <td><span class="badge" :class="STATE[p.state][1]"><span class="bullet"></span>{{ STATE[p.state][0] }}</span></td>
                            <td class="row-actions">
                                <a v-if="p.state !== 'scheduled'" class="btn btn-soft btn-sm btn-icon" :href="route('ui.prayer.show', p.id)" target="_blank" aria-label="View"><Icon name="eye" /></a>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Edit" @click="open(p)"><Icon name="pencil" /></button>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = p"><Icon name="trash" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-pad" style="border-top: 1px solid var(--line-soft)"><Pager :paging="paging" noun="prayer points" /></div>
        </section>

        <Modal v-if="editing" :title="editing.id ? 'Edit prayer point' : 'New prayer point'" width="680px" @close="editing = null">
            <div class="form-grid">
                <label class="field"><span>Date *</span><input v-model="form.date" type="date" /></label>
                <label class="field"><span>Verses</span><input v-model="form.verses" type="text" placeholder="e.g. Proverbs 22:6 ESV" /></label>
            </div>
            <label class="field mt-12"><span>Title *</span><input v-model="form.title" type="text" /></label>
            <div class="field mt-12"><span>Prayer *</span><RichEditor v-model="form.body" placeholder="Points to pray through…" /></div>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.title || !form.body || !form.date" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>
        <ConfirmDialog v-if="deleting" title="Delete prayer point" :message="`Delete “${deleting.title}”?`" @confirm="destroy" @close="deleting = null" />
    </AdminLayout>
</template>
