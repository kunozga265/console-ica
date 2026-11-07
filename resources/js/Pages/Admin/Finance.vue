<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Modal from '@/Components/UI/Modal.vue';
import Pager from '@/Components/Admin/Pager.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    tab: { type: String, default: 'receipts' },
    filters: { type: Object, default: () => ({}) },
    receipts: { type: Array, default: () => [] },
    receiptsPaging: { type: Object, required: true },
    proofs: { type: Array, default: () => [] },
    proofsPaging: { type: Object, required: true },
    counts: { type: Object, required: true }, // { waiting, thisMonth, receipts }
    methods: { type: Array, default: () => [] },
});

const { busy, errors, submit } = useSubmit();
const money = (n) => `MWK ${Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const day = (ymd) => (ymd ? fmtDate(new Date(`${ymd}T00:00:00`)) : '—');
const today = new Date().toISOString().slice(0, 10);
const STATUS = { pending: ['New', 'badge-red'], acknowledged: ['Seen', 'badge-gold'], receipted: ['Receipted', 'badge-green'] };

const search = ref(props.filters.search ?? '');
const go = (extra = {}) => router.get(route('admin.finance.index'), Object.fromEntries(Object.entries({ tab: props.tab, search: search.value, ...extra }).filter(([, v]) => v)), { preserveState: true, replace: true });
let debounce;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => go(), 300);
});

/* ---- New receipt ---- */
const form = ref(null); // { member, amount, receivedOn, method, reference, notes, proofId }
const open = (proof = null) => {
    form.value = {
        member: proof?.member ?? null,
        amount: proof?.amount ?? '',
        receivedOn: proof?.paidOn ?? today,
        method: proof ? 'bank' : 'cash',
        reference: '',
        notes: proof?.note ?? '',
        proofId: proof?.id ?? null,
    };
    query.value = '';
    results.value = [];
};
const query = ref('');
const results = ref([]);
let lookup;
watch(query, (q) => {
    clearTimeout(lookup);
    if (q.trim().length < 2) return (results.value = []);
    lookup = setTimeout(async () => {
        const res = await fetch(route('admin.finance.members', { q }), { headers: { Accept: 'application/json' } });
        results.value = res.ok ? await res.json() : [];
    }, 250);
});
const save = () =>
    submit('post', route('admin.finance.receipts.store'), {
        memberId: form.value.member?.id,
        amount: form.value.amount,
        receivedOn: form.value.receivedOn,
        method: form.value.method,
        reference: form.value.reference || null,
        notes: form.value.notes || null,
        proofId: form.value.proofId,
    }, { onDone: () => (form.value = null) });

const proof = ref(null); // the proof being viewed
const acknowledge = (p) => submit('post', route('admin.finance.proofs.acknowledge', p.id));
const resend = (r) => submit('post', route('admin.finance.receipts.resend', r.id));
const fieldErrors = computed(() => ['memberId', 'amount', 'receivedOn', 'method', 'reference', 'notes', 'proofId'].map((k) => errors.value[k]).filter(Boolean));
</script>

<template>
    <AdminLayout title="Tithe receipts" page="finance" crumb="Finance">
        <div class="grid stat-grid mb-20 three">
            <div class="stat"><div class="top"><span class="ico ico-red"><Icon name="bell" /></span><span class="label">Proofs to receipt</span></div><div class="value">{{ counts.waiting }}</div><div class="foot muted">Reminded daily until receipted</div></div>
            <div class="stat"><div class="top"><span class="ico ico-green"><Icon name="gift" /></span><span class="label">Receipted this month</span></div><div class="value" style="font-size: 22px">{{ money(counts.thisMonth) }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-blue"><Icon name="check" /></span><span class="label">Receipts issued</span></div><div class="value">{{ counts.receipts.toLocaleString() }}</div></div>
        </div>

        <section class="card">
            <div class="card-head wrap" style="gap: 12px">
                <div class="segmented">
                    <button :class="{ on: tab === 'receipts' }" @click="go({ tab: 'receipts' })">Receipts</button>
                    <button :class="{ on: tab === 'proofs' }" @click="go({ tab: 'proofs' })">Proofs of payment<span v-if="counts.waiting" class="seg-count">{{ counts.waiting }}</span></button>
                </div>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 260px"><Icon name="search" /><input v-model="search" type="search" placeholder="Member, receipt no., reference…" /></label>
                <button class="btn btn-primary btn-sm" @click="open()"><Icon name="plus" /> New receipt</button>
            </div>

            <template v-if="tab === 'receipts'">
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Receipt</th><th>Member</th><th>Amount</th><th>Received</th><th>Method</th><th>Issued by</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="r in receipts" :key="r.id">
                                <td><b>{{ r.number }}</b><div v-if="r.reference" class="tiny muted">{{ r.reference }}</div></td>
                                <td><div class="cell-main"><Avatar :person="r.member" size="sm" /><span class="t">{{ r.member?.name }}</span></div></td>
                                <td>{{ money(r.amount) }}</td>
                                <td class="small muted">{{ day(r.receivedOn) }}</td>
                                <td class="small">{{ r.methodName }}</td>
                                <td class="small muted">{{ r.issuedBy || '—' }}</td>
                                <td class="row-actions">
                                    <a class="btn btn-ghost btn-sm" :href="route('admin.finance.receipts.pdf', r.id)" target="_blank" rel="noopener">PDF</a>
                                    <button class="btn btn-ghost btn-sm" :disabled="busy" @click="resend(r)">Resend</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="!receipts.length" class="card-pad small muted">No receipts yet.</p>
                <div class="card-pad" style="border-top: 1px solid var(--line-soft)"><Pager :paging="receiptsPaging" noun="receipts" /></div>
            </template>

            <template v-else>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Member</th><th>Amount</th><th>Paid</th><th>Sent</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="p in proofs" :key="p.id" class="clickable" @click="proof = p">
                                <td><div class="cell-main"><Avatar :person="p.member" size="sm" /><div><div class="t">{{ p.member?.name }}</div><div v-if="p.note" class="s">{{ p.note }}</div></div></div></td>
                                <td>{{ p.amount ? money(p.amount) : '—' }}</td>
                                <td class="small muted">{{ day(p.paidOn) }}</td>
                                <td class="small muted">{{ fmtDate(p.submittedAt) }}</td>
                                <td><span class="badge" :class="STATUS[p.status][1]">{{ STATUS[p.status][0] }}</span></td>
                                <td class="row-actions" @click.stop>
                                    <button v-if="p.status === 'pending'" class="btn btn-ghost btn-sm" :disabled="busy" @click="acknowledge(p)">Mark seen</button>
                                    <button v-if="p.status !== 'receipted'" class="btn btn-primary btn-sm" @click="open(p)">Create receipt</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="!proofs.length" class="card-pad small muted">No proofs of payment yet.</p>
                <div class="card-pad" style="border-top: 1px solid var(--line-soft)"><Pager :paging="proofsPaging" noun="proofs" /></div>
            </template>
        </section>

        <!-- View a proof of payment -->
        <Modal v-if="proof" :title="`Proof from ${proof.member?.name}`" width="720px" @close="proof = null">
            <div class="flex gap-8 wrap mb-12">
                <span class="badge" :class="STATUS[proof.status][1]">{{ STATUS[proof.status][0] }}</span>
                <span v-if="proof.amount" class="badge badge-outline">{{ money(proof.amount) }}</span>
                <span v-if="proof.paidOn" class="badge badge-outline">Paid {{ day(proof.paidOn) }}</span>
            </div>
            <p v-if="proof.note" class="small mb-12">{{ proof.note }}</p>
            <img v-if="proof.isImage" :src="route('admin.finance.proofs.file', proof.id)" alt="Proof of payment" style="max-width: 100%; border-radius: 10px; border: 1px solid var(--line)" />
            <iframe v-else :src="route('admin.finance.proofs.file', proof.id)" title="Proof of payment" style="width: 100%; height: 60vh; border: 1px solid var(--line); border-radius: 10px"></iframe>
            <div class="flex gap-8 mt-16" style="justify-content: flex-end">
                <a class="btn btn-ghost btn-sm" :href="route('admin.finance.proofs.file', proof.id)" target="_blank" rel="noopener">Open file</a>
                <button v-if="proof.status !== 'receipted'" class="btn btn-primary btn-sm" @click="open(proof); proof = null">Create receipt</button>
            </div>
        </Modal>

        <!-- New receipt -->
        <Modal v-if="form" title="New tithe receipt" width="560px" @close="form = null">
            <div v-if="form.member" class="list-row" style="padding-left: 0">
                <Avatar :person="form.member" size="sm" />
                <div class="grow"><div class="t">{{ form.member.name }}</div><div class="s">{{ form.member.cell || 'No cell' }}<template v-if="form.member.hasAccount === false"> · no app account (email only)</template></div></div>
                <button v-if="!form.proofId" class="btn btn-ghost btn-sm" @click="form.member = null">Change</button>
            </div>
            <div v-else>
                <label class="field"><span>Member</span><input v-model="query" type="search" placeholder="Search by name, phone or email" autofocus /></label>
                <div v-if="results.length" class="card mt-4" style="max-height: 240px; overflow: auto">
                    <button v-for="m in results" :key="m.id" type="button" class="list-row" style="width: 100%; text-align: left" @click="form.member = m">
                        <Avatar :person="m" size="sm" /><div class="grow"><div class="t">{{ m.name }}</div><div class="s">{{ m.cell || 'No cell' }}</div></div>
                    </button>
                </div>
            </div>
            <div class="form-grid mt-12">
                <label class="field"><span>Amount (MWK)</span><input v-model="form.amount" type="number" min="1" step="0.01" inputmode="decimal" /></label>
                <label class="field"><span>Date received</span><input v-model="form.receivedOn" type="date" :max="today" /></label>
                <label class="field"><span>Method</span><select v-model="form.method"><option v-for="m in methods" :key="m.key" :value="m.key">{{ m.label }}</option></select></label>
                <label class="field"><span>Reference (optional)</span><input v-model="form.reference" placeholder="Transaction ID, slip no." /></label>
            </div>
            <label class="field mt-8"><span>Notes (optional)</span><textarea v-model="form.notes" rows="2"></textarea></label>
            <p v-for="e in fieldErrors" :key="e" class="field-error">{{ e }}</p>
            <p class="tiny muted mt-8">The member gets the receipt in their app and on the website, plus an email with the PDF.</p>
            <div class="flex gap-8 mt-16" style="justify-content: flex-end">
                <button class="btn btn-ghost btn-sm" @click="form = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.member || !form.amount" @click="save">{{ busy ? 'Sending…' : 'Send receipt' }}</button>
            </div>
        </Modal>
    </AdminLayout>
</template>
