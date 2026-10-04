<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import PersonForm from '@/Components/UI/PersonForm.vue';
import Pager from '@/Components/Admin/Pager.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';

const props = defineProps({
    people: { type: Array, default: () => [] },
    paging: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    cells: { type: Array, default: () => [] },
});

const { submit } = useSubmit();
const f = ref({ search: '', type: '', cell: '', ...props.filters });
const apply = () => router.get(route('admin.members.index'), Object.fromEntries(Object.entries(f.value).filter(([, v]) => v)), { preserveState: true, replace: true });
let debounce;
watch(() => f.value.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(apply, 300);
});

const phone = (p) => p.phoneNumberAirtel || p.phoneNumberTnm || p.phoneNumberInternational;
const joined = (ms) => (ms ? new Date(ms).toLocaleDateString('en-GB', { month: 'short', year: 'numeric' }) : '—');

const form = ref(null); // { mode, person?, defaultType? }
const deleting = ref(null);
const destroy = () => submit('delete', route('admin.members.destroy', deleting.value.code), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Members" page="members" crumb="Community">
        <div class="grid stat-grid mb-20">
            <div class="stat"><div class="top"><span class="ico ico-brown"><Icon name="users" /></span><span class="label">Members</span></div><div class="value">{{ stats.members.toLocaleString() }}</div><div class="foot"><span class="trend" :class="stats.new90 ? 'up' : 'flat'">+{{ stats.new90 }}</span> in the last 90 days</div></div>
            <div class="stat"><div class="top"><span class="ico ico-gold"><Icon name="star" /></span><span class="label">Visitors</span></div><div class="value">{{ stats.visitors }}</div><div class="foot muted">Not yet registered</div></div>
            <div class="stat"><div class="top"><span class="ico ico-green"><Icon name="check" /></span><span class="label">In a cell</span></div><div class="value">{{ stats.inCells }}</div><div class="foot muted">{{ stats.members ? Math.round((stats.inCells / stats.members) * 100) : 0 }}% of members</div></div>
            <div class="stat"><div class="top"><span class="ico ico-plum"><Icon name="layers" /></span><span class="label">Cells</span></div><div class="value">{{ stats.cells }}</div><div class="foot muted">Verified</div></div>
        </div>

        <section class="card">
            <div class="card-head wrap" style="gap: 12px">
                <h3>Directory</h3>
                <span class="badge">{{ paging.total.toLocaleString() }}</span>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 240px"><Icon name="search" /><input v-model="f.search" type="search" placeholder="Name, code, phone, email…" /></label>
                <select v-model="f.type" class="chip-select" @change="apply"><option value="">Members &amp; visitors</option><option value="members">Members</option><option value="visitors">Visitors</option></select>
                <select v-model="f.cell" class="chip-select" @change="apply"><option value="">Any cell</option><option value="none">No cell</option><option v-for="c in cells" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                <button class="btn btn-primary btn-sm" @click="form = { mode: 'add', defaultType: f.type === 'visitors' ? 'visitor' : 'member' }"><Icon name="plus" /> Add person</button>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Member</th><th>Contact</th><th>Cell</th><th>Role</th><th>Joined</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="p in people" :key="p.id">
                            <td><div class="cell-main"><Avatar :person="p" /><div><div class="t">{{ p.name }}</div><div class="s">{{ p.email || p.code }}</div></div></div></td>
                            <td class="small muted">{{ phone(p) || '—' }}</td>
                            <td><span v-if="p.cell" class="badge badge-outline">{{ p.cell }}</span><span v-else class="small muted">—</span></td>
                            <td class="small">{{ p.leads ? `Leads ${p.leads}` : 'Member' }}</td>
                            <td class="small muted">{{ joined(p.joined) }}</td>
                            <td><span class="badge" :class="p.isRegistered ? 'badge-green' : 'badge-gold'"><span class="bullet"></span>{{ p.isRegistered ? 'Member' : 'Visitor' }}</span></td>
                            <td class="row-actions">
                                <button v-if="!p.isRegistered" class="btn btn-primary btn-sm" @click="form = { mode: 'register', person: p }">Register</button>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Edit" @click="form = { mode: 'edit', person: p }"><Icon name="pencil" /></button>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = p"><Icon name="trash" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!people.length" class="card-pad small muted">No one matches these filters.</p>
            <div class="card-pad" style="border-top: 1px solid var(--line-soft)"><Pager :paging="paging" noun="people" /></div>
        </section>

        <PersonForm v-if="form" :key="`${form.mode}-${form.person?.id ?? 'new'}`" :mode="form.mode" :person="form.person" :default-type="form.defaultType" :cells="cells" @close="form = null" />
        <ConfirmDialog
            v-if="deleting"
            title="Remove from directory"
            :message="`Remove ${deleting.name}? Their attendance history is deleted too. A linked app account is kept but unlinked.`"
            confirm-label="Remove"
            @confirm="destroy"
            @close="deleting = null"
        />
    </AdminLayout>
</template>
