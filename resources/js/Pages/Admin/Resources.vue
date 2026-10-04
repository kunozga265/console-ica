<script setup>
import { reactive, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

defineProps({ resources: { type: Array, default: () => [] } });

const { busy, firstError, submit } = useSubmit();
const TYPES = { pdf: 'PDF', ppt: 'Slides', doc: 'Document', xls: 'Spreadsheet', link: 'Link' };
const today = () => new Date().toISOString().slice(0, 10);

const editing = ref(null);
const form = reactive({ title: '', type: 'pdf', path: '', description: '', date: today() });
const open = (r = null) => {
    Object.assign(form, { title: r?.title ?? '', type: r?.type ?? 'pdf', path: r?.path ?? '', description: r?.description ?? '', date: r?.date ?? today() });
    editing.value = r ?? {};
};
const save = () =>
    editing.value.id
        ? submit('put', route('admin.resources.update', editing.value.id), { ...form }, { onDone: () => (editing.value = null) })
        : submit('post', route('admin.resources.store'), { ...form }, { onDone: () => (editing.value = null) });

const deleting = ref(null);
const destroy = () => submit('delete', route('admin.resources.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Resources" page="resources" crumb="Content">
        <div class="between wrap mb-20">
            <p class="muted small">Documents members can view and download on the site (Google Drive / Docs links or file URLs).</p>
            <button class="btn btn-primary" @click="open()"><Icon name="plus" /> Add resource</button>
        </div>
        <section class="card">
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Resource</th><th>Type</th><th>Date</th><th>Link</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="r in resources" :key="r.id">
                            <td><div class="t">{{ r.title }}</div><div class="s clamp-2" style="max-width: 420px">{{ r.description }}</div></td>
                            <td><span class="badge badge-outline">{{ TYPES[r.type] ?? r.type }}</span></td>
                            <td class="small muted">{{ r.date ? fmtDate(r.date) : '—' }}</td>
                            <td><a class="small link" :href="r.path" target="_blank" rel="noopener">Open <Icon name="ext" /></a></td>
                            <td class="row-actions">
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Edit" @click="open(r)"><Icon name="pencil" /></button>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = r"><Icon name="trash" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!resources.length" class="card-pad small muted">No resources yet.</p>
        </section>

        <Modal v-if="editing" :title="editing.id ? 'Edit resource' : 'Add resource'" @close="editing = null">
            <label class="field"><span>Title *</span><input v-model="form.title" type="text" /></label>
            <div class="form-grid mt-12">
                <label class="field"><span>Type *</span><select v-model="form.type"><option v-for="(l, k) in TYPES" :key="k" :value="k">{{ l }}</option></select></label>
                <label class="field"><span>Date *</span><input v-model="form.date" type="date" /></label>
            </div>
            <label class="field mt-12"><span>Link *</span><input v-model="form.path" type="url" placeholder="https://drive.google.com/file/d/…/view" /></label>
            <label class="field mt-12"><span>Description</span><textarea v-model="form.description" rows="3"></textarea></label>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.title || !form.path" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>
        <ConfirmDialog v-if="deleting" title="Delete resource" :message="`Delete “${deleting.title}”? It will no longer appear on the site.`" @confirm="destroy" @close="deleting = null" />
    </AdminLayout>
</template>
