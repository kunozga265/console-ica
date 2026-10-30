<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Modal from '@/Components/UI/Modal.vue';
import PersonForm from '@/Components/UI/PersonForm.vue';
import Pager from '@/Components/Admin/Pager.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    visitors: { type: Array, default: () => [] },
    paging: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true }, // { all, due, new }
    visitor: { type: Object, default: null }, // the open visitor (?visitor=id)
    cells: { type: Array, default: () => [] },
});

const { busy, errors, submit } = useSubmit();
const f = ref({ search: '', status: 'due', ...props.filters });
const query = (extra = {}) => Object.fromEntries(Object.entries({ ...f.value, ...extra }).filter(([, v]) => v));
const apply = () => router.get(route('admin.visitors.index'), query({ visitor: null }), { preserveState: true, replace: true });
let debounce;
watch(() => f.value.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(apply, 300);
});
const setStatus = (s) => {
    f.value.status = s;
    apply();
};

const phone = (p) => p.phoneNumberAirtel || p.phoneNumberTnm || p.phoneNumberInternational;
const day = (ymd) => (ymd ? fmtDate(new Date(`${ymd}T00:00:00`)) : '—');
const today = new Date().toISOString().slice(0, 10);
const STATUS = {
    new: { label: 'Not followed up', cls: 'badge-gold' },
    due: { label: 'Check-up due', cls: 'badge-red' },
    scheduled: { label: 'Scheduled', cls: 'badge-blue' },
    done: { label: 'Followed up', cls: 'badge-green' },
};

/* ---- Visitor panel (loaded via ?visitor=id) ---- */
const open = (v) => router.get(route('admin.visitors.index'), query({ visitor: v.id }), { preserveState: true, preserveScroll: true, replace: true, only: ['visitor', 'filters'] });
const close = () => router.get(route('admin.visitors.index'), query({ visitor: null }), { preserveState: true, preserveScroll: true, replace: true, only: ['visitor', 'filters'] });
const v = computed(() => props.visitor);

const blankFollowUp = () => ({ date: today, comment: '', nextDate: '' });
const fu = ref(blankFollowUp());
watch(() => v.value?.id, () => (fu.value = blankFollowUp()));
const saveFollowUp = () =>
    submit('post', route('admin.visitors.follow-ups.store', v.value.code), { ...fu.value, nextDate: fu.value.nextDate || null }, {
        onDone: () => (fu.value = blankFollowUp()),
    });
const removing = ref(null);
const removeFollowUp = () => submit('delete', route('admin.visitors.follow-ups.destroy', removing.value.id), {}, { onDone: () => (removing.value = null) });

const form = ref(null); // PersonForm { mode, person }
</script>

<template>
    <AdminLayout title="Visitors" page="visitors" crumb="Community">
        <div class="grid stat-grid mb-20 three">
            <div class="stat"><div class="top"><span class="ico ico-gold"><Icon name="star" /></span><span class="label">Visitors</span></div><div class="value">{{ counts.all.toLocaleString() }}</div><div class="foot muted">Not yet full members</div></div>
            <div class="stat"><div class="top"><span class="ico ico-red"><Icon name="bell" /></span><span class="label">Need follow-up</span></div><div class="value">{{ counts.due }}</div><div class="foot muted">Never contacted, or check-up due</div></div>
            <div class="stat"><div class="top"><span class="ico ico-green"><Icon name="plus" /></span><span class="label">New</span></div><div class="value">{{ counts.new }}</div><div class="foot muted">In the last 30 days</div></div>
        </div>

        <section class="card">
            <div class="card-head wrap" style="gap: 12px">
                <div class="segmented">
                    <button :class="{ on: f.status === 'due' }" @click="setStatus('due')">Needs follow-up</button>
                    <button :class="{ on: f.status === 'all' }" @click="setStatus('all')">All visitors</button>
                </div>
                <span class="badge">{{ paging.total.toLocaleString() }}</span>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 260px"><Icon name="search" /><input v-model="f.search" type="search" placeholder="Name, phone, email…" /></label>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Visitor</th><th>Contact</th><th>First visit</th><th>Visits</th><th>Last follow-up</th><th>Next check-up</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="p in visitors" :key="p.id" class="clickable" @click="open(p)">
                            <td><div class="cell-main"><Avatar :person="p" /><div><div class="t">{{ p.name }}</div><div class="s">{{ p.cell || p.email || '—' }}</div></div></div></td>
                            <td class="small muted">{{ phone(p) || '—' }}</td>
                            <td class="small muted">{{ p.firstVisit ? fmtDate(p.firstVisit) : 'Not recorded' }}</td>
                            <td class="small">{{ p.visits }}</td>
                            <td class="small muted">{{ day(p.lastFollowUp) }}</td>
                            <td>
                                <span class="badge" :class="STATUS[p.status].cls"><span class="bullet"></span>{{ p.status === 'scheduled' || p.status === 'due' ? day(p.nextFollowUp) : STATUS[p.status].label }}</span>
                            </td>
                            <td class="row-actions"><button class="btn btn-ghost btn-sm" @click.stop="open(p)">Open</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!visitors.length" class="card-pad small muted">{{ f.status === 'due' ? 'Everyone is followed up. 🎉' : 'No visitors match.' }}</p>
            <div class="card-pad" style="border-top: 1px solid var(--line-soft)"><Pager :paging="paging" noun="visitors" /></div>
        </section>

        <!-- Visitor panel -->
        <Modal v-if="v" :title="v.name" width="720px" @close="close">
            <div class="visitor-head">
                <Avatar :person="v" />
                <div class="grow">
                    <div class="small muted">{{ [phone(v), v.email, v.cell].filter(Boolean).join(' · ') || 'No contact details' }}</div>
                    <div class="flex gap-8 wrap mt-4">
                        <span class="badge" :class="STATUS[v.status].cls">{{ STATUS[v.status].label }}</span>
                        <span class="badge badge-outline">{{ v.visits }} {{ v.visits === 1 ? 'visit' : 'visits' }}</span>
                        <span v-if="v.firstVisit" class="badge badge-outline">First visit {{ fmtDate(v.firstVisit) }}</span>
                    </div>
                </div>
                <button class="btn btn-ghost btn-sm" @click="form = { mode: 'edit', person: v }"><Icon name="pencil" /> Edit</button>
                <button class="btn btn-primary btn-sm" @click="form = { mode: 'register', person: v }"><Icon name="check" /> Make member</button>
            </div>

            <div class="visitor-grid mt-16">
                <section>
                    <h4 class="mb-8">Log a follow-up</h4>
                    <div class="form-grid">
                        <label class="field"><span>Date</span><input v-model="fu.date" type="date" :max="today" /></label>
                        <label class="field"><span>Next check-up</span><input v-model="fu.nextDate" type="date" :min="fu.date" /></label>
                    </div>
                    <label class="field mt-8"><span>What happened?</span><textarea v-model="fu.comment" rows="3" placeholder="Called them, prayed together, invited to a cell…"></textarea></label>
                    <p v-for="e in [errors.date, errors.comment, errors.nextDate].filter(Boolean)" :key="e" class="field-error">{{ e }}</p>
                    <button class="btn btn-primary btn-sm mt-8" :disabled="busy || !fu.comment.trim()" @click="saveFollowUp">Save follow-up</button>

                    <h4 class="mt-20 mb-8">Visits</h4>
                    <ul v-if="v.visitList.length" class="visit-list">
                        <li v-for="r in v.visitList" :key="r.code + r.date"><a :href="route('admin.registers.show', r.code)">{{ r.name }}</a><span class="small muted">{{ fmtDate(r.date) }}</span></li>
                    </ul>
                    <p v-else class="small muted">No register attendance recorded yet.</p>
                </section>

                <section>
                    <h4 class="mb-8">Follow-ups</h4>
                    <ol v-if="v.followUps.length" class="fu-timeline">
                        <li v-for="x in v.followUps" :key="x.id">
                            <div class="fu-meta">
                                <b>{{ day(x.date) }}</b>
                                <span v-if="x.by" class="small muted">by {{ x.by }}</span>
                                <span class="spacer"></span>
                                <button class="icon-btn" aria-label="Remove follow-up" @click="removing = x"><Icon name="trash" /></button>
                            </div>
                            <p class="small fu-comment">{{ x.comment }}</p>
                            <span v-if="x.nextDate" class="badge" :class="x.nextDate <= today ? 'badge-red' : 'badge-blue'">Next check-up {{ day(x.nextDate) }}</span>
                        </li>
                    </ol>
                    <p v-else class="small muted">No follow-ups yet.</p>
                </section>
            </div>
        </Modal>

        <PersonForm v-if="form" :key="`${form.mode}-${form.person?.id}`" :mode="form.mode" :person="form.person" :cells="cells" @close="form = null" />
        <ConfirmDialog
            v-if="removing"
            title="Remove follow-up"
            :message="`Remove the follow-up from ${day(removing.date)}?`"
            confirm-label="Remove"
            danger
            @confirm="removeFollowUp"
            @close="removing = null"
        />
    </AdminLayout>
</template>
