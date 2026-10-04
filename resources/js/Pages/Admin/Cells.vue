<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    cells: { type: Array, default: () => [] },
    status: { type: String, default: null },
    totals: { type: Object, required: true },
    zones: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
});

const { busy, firstError, submit } = useSubmit();
const filter = (status) => router.get(route('admin.cells.index'), status ? { status } : {}, { preserveState: true, replace: true });
const money = (n) => `MK ${Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

const editing = ref(null);
const form = reactive({ name: '', details: '', location: '', zoneId: '', typeId: 1, balance: '', verified: true });
const open = (c = null) => {
    Object.assign(form, { name: c?.name ?? '', details: c?.details ?? '', location: c?.location ?? '', zoneId: c?.zoneId ?? props.zones[0]?.id ?? '', typeId: c?.typeId ?? 1, balance: '', verified: c?.verified ?? true });
    editing.value = c ?? {};
};
const save = () => {
    const data = { name: form.name, details: form.details, location: form.location, zoneId: form.zoneId, typeId: form.typeId, verified: form.verified };
    if (!editing.value.id) data.balance = form.balance || 0;
    editing.value.id
        ? submit('put', route('admin.cells.update', editing.value.id), data, { onDone: () => (editing.value = null) })
        : submit('post', route('admin.cells.store'), data, { onDone: () => (editing.value = null) });
};
const verify = (c) => submit('post', route('admin.cells.verify', c.id));
const deleting = ref(null);
const destroy = () => submit('delete', route('admin.cells.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Cells" page="cells" crumb="Community">
        <div class="grid stat-grid mb-20 three">
            <div class="stat"><div class="top"><span class="ico ico-plum"><Icon name="shield" /></span><span class="label">Cells</span></div><div class="value">{{ totals.cells }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-gold"><Icon name="info" /></span><span class="label">Awaiting verification</span></div><div class="value">{{ totals.unverified }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-green"><Icon name="heart" /></span><span class="label">Combined balance</span></div><div class="value">{{ money(totals.balance) }}</div></div>
        </div>

        <section class="card">
            <div class="card-head wrap" style="gap: 12px">
                <div class="chips">
                    <button class="chip" :class="{ on: !status }" @click="filter(null)">All</button>
                    <button class="chip" :class="{ on: status === 'unverified' }" @click="filter('unverified')">Unverified</button>
                    <button class="chip" :class="{ on: status === 'verified' }" @click="filter('verified')">Verified</button>
                </div>
                <div class="spacer"></div>
                <button class="btn btn-primary btn-sm" @click="open()"><Icon name="plus" /> New cell</button>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Cell</th><th>Leaders</th><th>Members</th><th>Meetings</th><th>Balance</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="c in cells" :key="c.id">
                            <td><div class="t">{{ c.name }}</div><div class="s">{{ [c.type, c.zone, c.location].filter(Boolean).join(' · ') }}</div></td>
                            <td class="small muted">{{ c.leaders.join(', ') || '—' }}</td>
                            <td class="small">{{ c.members }}</td>
                            <td class="small">{{ c.meetings }}<span v-if="c.lastMeeting" class="tiny muted"> · last {{ fmtDate(c.lastMeeting) }}</span></td>
                            <td class="small" style="font-weight: 600">{{ money(c.balance) }}</td>
                            <td><span class="badge" :class="c.verified ? 'badge-green' : 'badge-gold'"><span class="bullet"></span>{{ c.verified ? 'Verified' : 'Unverified' }}</span></td>
                            <td class="row-actions">
                                <button v-if="!c.verified" class="btn btn-primary btn-sm" :disabled="busy" @click="verify(c)">Verify</button>
                                <a class="btn btn-soft btn-sm btn-icon" :href="route('ui.cells.show', c.code)" target="_blank" aria-label="Open cell dashboard" title="Meetings, members & money"><Icon name="eye" /></a>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Edit" @click="open(c)"><Icon name="pencil" /></button>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = c"><Icon name="trash" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!cells.length" class="card-pad small muted">No cells here.</p>
        </section>

        <Modal v-if="editing" :title="editing.id ? 'Edit cell' : 'New cell'" width="560px" @close="editing = null">
            <label class="field"><span>Name *</span><input v-model="form.name" type="text" /></label>
            <div class="form-grid mt-12">
                <label class="field"><span>Zone *</span><select v-model="form.zoneId"><option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option></select></label>
                <label class="field"><span>Type *</span><select v-model="form.typeId"><option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option></select></label>
                <label class="field" style="grid-column: 1 / -1"><span>Location</span><input v-model="form.location" type="text" placeholder="e.g. Area 47, Sector 3" /></label>
                <label v-if="!editing.id" class="field"><span>Opening balance (MK)</span><input v-model="form.balance" type="number" min="0" step="any" placeholder="0" /></label>
            </div>
            <label class="field mt-12"><span>Details</span><textarea v-model="form.details" rows="3" placeholder="Meeting day, who it's for…"></textarea></label>
            <label class="flex mt-12 small" style="gap: 9px; cursor: pointer"><input v-model="form.verified" type="checkbox" class="rowcheck" /> Verified (can record offerings and transactions)</label>
            <p class="tiny muted mt-8">Leaders, members, meetings and money are managed from the cell's dashboard.</p>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.name || !form.zoneId" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>
        <ConfirmDialog
            v-if="deleting"
            title="Delete cell"
            :message="`Delete ${deleting.name}? Its ${deleting.meetings} meetings, their attendance and its money records are deleted. Its ${deleting.members} members stay in the directory without a cell.`"
            @confirm="destroy"
            @close="deleting = null"
        />
    </AdminLayout>
</template>
