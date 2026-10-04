<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Modal from '@/Components/UI/Modal.vue';
import { fmtDate, fmtDay } from '@/Components/UI/helpers';
import { toast } from '@/Components/UI/useMemberActions';

const props = defineProps({
    cell: { type: Object, required: true },
    mine: { type: Object, default: () => ({}) }, // { leadership?: {code,name}, member?: {code,name} }
    role: { type: String, default: null }, // which of `mine` this cell is
    canManage: { type: Boolean, default: false },
    chart: { type: Array, default: () => [] }, // [{ date, attendees }] oldest first
    meetings: { type: Array, default: () => [] }, // newest first
    members: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
    joinRequests: { type: Array, default: () => [] },
    otherCells: { type: Array, default: () => [] },
    memberQ: { type: String, default: '' },
    memberResults: { type: Array, default: () => [] },
});

const page = usePage();
const errors = computed(() => page.props.errors ?? {});

/* ---- Tabs (remembered in the hash so notification links can open "requests") ---- */
const TABS = computed(() =>
    [
        { id: 'meetings', label: 'Meetings', count: props.meetings.length },
        { id: 'members', label: 'Members', count: props.members.length },
        props.canManage && { id: 'transactions', label: 'Transactions' },
        props.canManage && { id: 'requests', label: 'Requests', count: props.joinRequests.length },
    ].filter(Boolean),
);
const tab = ref('meetings');
onMounted(() => {
    const h = window.location.hash.slice(1);
    if (TABS.value.some((t) => t.id === h)) tab.value = h;
});

const money = (n) => `MK ${Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
const dateTime = (ms) => `${fmtDay(ms)} ${fmtDate(ms)} · ${new Date(ms).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
const toLocalInput = (ms) => {
    const d = new Date(ms);
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
};

/* ---- Stats + chart ---- */
const avgAttendance = computed(() => (props.chart.length ? Math.round(props.chart.reduce((s, m) => s + m.attendees, 0) / props.chart.length) : 0));
const chartSeries = computed(() => [{ name: 'Attendance', data: props.chart.map((m) => ({ x: m.date, y: m.attendees })) }]);
const chartOptions = {
    chart: { type: 'area', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'Inter, sans-serif' },
    colors: ['#148ddd'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
    markers: { size: 4 },
    xaxis: { type: 'datetime', labels: { datetimeUTC: false } },
    yaxis: { min: 0, forceNiceScale: true, labels: { formatter: (v) => Math.round(v) } },
    grid: { borderColor: '#eef0f2' },
    tooltip: { x: { format: 'ddd dd MMM yyyy' } },
};

/* ---- Shared request helper ---- */
const busy = ref(false);
const submit = (method, url, data, message, done) => {
    busy.value = true;
    router[method](url, data, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (Object.keys(page.props.errors ?? {}).length) return;
            toast(message);
            done?.();
        },
        onFinish: () => (busy.value = false),
    });
};

/* ---- Meetings ---- */
const meetingForm = ref(null); // { code?, date, venue, offering, memberIds:Set }
const openNewMeeting = () => (meetingForm.value = { date: toLocalInput(Date.now()), venue: props.cell.location || '' });
const openMeeting = (m) =>
    (meetingForm.value = { code: m.code, date: toLocalInput(m.date), venue: m.venue, offering: m.offering ?? 0, memberIds: new Set(m.memberIds) });
const toggleAttendee = (id) => {
    const s = new Set(meetingForm.value.memberIds);
    s.has(id) ? s.delete(id) : s.add(id);
    meetingForm.value.memberIds = s;
};
const allPresent = computed(() => meetingForm.value?.memberIds && props.members.every((m) => meetingForm.value.memberIds.has(m.id)));
const toggleAll = () => (meetingForm.value.memberIds = allPresent.value ? new Set() : new Set(props.members.map((m) => m.id)));
const saveMeeting = () => {
    const f = meetingForm.value;
    if (!f.code) {
        return submit('post', route('ui.cells.meetings.store', props.cell.code), { date: f.date, venue: f.venue }, 'Meeting created', () => (meetingForm.value = null));
    }
    submit('put', route('ui.cells.meetings.update', [props.cell.code, f.code]), { date: f.date, venue: f.venue, offering: f.offering, memberIds: [...f.memberIds] }, 'Meeting saved', () => (meetingForm.value = null));
};

/* ---- Members ---- */
const adding = ref(false);
const memberSearch = ref(props.memberQ);
let debounce;
watch(memberSearch, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => router.reload({ data: { memberQ: v || undefined }, only: ['memberResults', 'memberQ'], preserveState: true, preserveScroll: true, replace: true }), 300);
});
const attach = (m) => submit('post', route('ui.cells.members.attach', props.cell.code), { memberId: m.id }, `${m.name} added to ${props.cell.name}`);

const managing = ref(null); // { member, action: 'transfer' | 'remove', cellId }
const doManage = () => {
    const { member, action, cellId } = managing.value;
    if (action === 'remove') {
        return submit('delete', route('ui.cells.members.detach', [props.cell.code, member.code]), {}, `${member.name} removed from the cell`, () => (managing.value = null));
    }
    const target = props.otherCells.find((c) => c.id === Number(cellId));
    submit('post', route('ui.cells.members.transfer', [props.cell.code, member.code]), { cellId }, `${member.name} moved to ${target?.name}`, () => (managing.value = null));
};

/* ---- Transactions ---- */
const txForm = ref(null);
const saveTx = () => submit('post', route('ui.cells.transactions.store', props.cell.code), txForm.value, 'Transaction recorded', () => (txForm.value = null));

/* ---- Join requests ---- */
const decide = (r, approve) => submit('post', route('ui.cells.requests.decide', [props.cell.code, r.id]), { approve }, approve ? `${r.name} added to the cell` : 'Request declined');
</script>

<template>
    <UILayout :title="cell.name" page="cells" :breadcrumbs="[{ label: 'Cells', href: route('ui.cells') }, { label: cell.name }]">
        <template #feature>
            <!-- Leadership / member cell toggle -->
            <div v-if="mine.leadership && mine.member && mine.leadership.code !== mine.member.code" class="segmented mb-16">
                <Link :href="route('ui.cells.show', mine.leadership.code)" :class="{ on: role === 'leadership' }" class="seg-link">Leadership cell</Link>
                <Link :href="route('ui.cells.show', mine.member.code)" :class="{ on: role === 'member' }" class="seg-link">Member cell</Link>
            </div>

            <div class="card card-pad feature mb-20 cell-hero">
                <div class="grow" style="min-width: 240px">
                    <div class="flex gap-8 wrap">
                        <span class="eyebrow" style="color: #ffdd57">{{ role === 'leadership' ? 'You lead this cell' : role === 'member' ? 'Your cell' : cell.type }}</span>
                        <span v-if="!cell.verified" class="badge" style="background: rgba(255, 255, 255, 0.15); color: #fff">Not verified</span>
                    </div>
                    <h3 style="font-size: 26px; margin-top: 6px">{{ cell.name }}</h3>
                    <p class="small mt-8" style="color: rgba(255, 255, 255, 0.78)">
                        {{ [cell.type, cell.zone, cell.location].filter(Boolean).join(' · ') }}
                    </p>
                    <p v-if="cell.leaders.length" class="small mt-4" style="color: rgba(255, 255, 255, 0.78)">Led by {{ cell.leaders.join(', ') }}</p>
                </div>
                <div class="cell-stats">
                    <div><b>{{ cell.memberCount }}</b><span>members</span></div>
                    <div><b>{{ meetings.length }}</b><span>meetings</span></div>
                    <div><b>{{ avgAttendance }}</b><span>avg. attendance</span></div>
                    <div v-if="canManage"><b>{{ money(cell.balance) }}</b><span>balance</span></div>
                </div>
            </div>
        </template>

        <div v-if="chart.length" class="panel mb-24">
            <section class="card card-pad mb-20">
                <div class="between mb-8">
                    <h3 style="font-size: 16px; color: var(--ink)">Attendance</h3>
                    <span class="tiny muted">Members present at each meeting</span>
                </div>
                <apexchart type="area" height="240" :options="chartOptions" :series="chartSeries" />
            </section>
        </div>

        <div class="panel mb-24">
            <div class="between mb-16 wrap" style="gap: 12px">
                <div class="segmented">
                    <button v-for="t in TABS" :key="t.id" :class="{ on: tab === t.id }" @click="tab = t.id">
                        {{ t.label }}<span v-if="t.count" class="seg-count">{{ t.count }}</span>
                    </button>
                </div>
                <template v-if="canManage">
                    <button v-if="tab === 'meetings'" class="btn btn-primary btn-sm" @click="openNewMeeting"><Icon name="plus" /> New meeting</button>
                    <button v-if="tab === 'members'" class="btn btn-primary btn-sm" @click="adding = true"><Icon name="plus" /> Add member</button>
                    <button v-if="tab === 'transactions'" class="btn btn-primary btn-sm" :disabled="!cell.verified" @click="txForm = { type: 0, amount: '', description: '' }">
                        <Icon name="plus" /> Add transaction
                    </button>
                </template>
            </div>

            <!-- Meetings -->
            <div v-if="tab === 'meetings'" class="card">
                <div v-for="m in meetings" :key="m.code" class="list-row cell-row">
                    <div class="dateblock lg"><div class="d">{{ new Date(m.date).getDate() }}</div><div class="m">{{ new Date(m.date).toLocaleString('en', { month: 'short' }).toUpperCase() }}</div></div>
                    <div class="grow">
                        <div class="t">{{ m.venue }}</div>
                        <div class="s">{{ dateTime(m.date) }}</div>
                    </div>
                    <span class="badge badge-blue">{{ m.attendees }} present</span>
                    <span v-if="canManage" class="badge badge-gold hide-sm">{{ money(m.offering) }}</span>
                    <button v-if="canManage" class="btn btn-ghost btn-sm" @click="openMeeting(m)">Edit</button>
                </div>
                <p v-if="!meetings.length" class="card-pad small muted">No meetings yet.</p>
            </div>

            <!-- Members -->
            <div v-if="tab === 'members'" class="card">
                <div v-for="m in members" :key="m.id" class="list-row cell-row">
                    <Avatar :person="m" size="sm" />
                    <div class="grow">
                        <div class="t">{{ m.name }} <span v-if="m.isLeader" class="badge badge-gold" style="margin-left: 6px">Leader</span></div>
                        <div class="s">{{ [m.code, canManage ? m.phone : null].filter(Boolean).join(' · ') }}</div>
                    </div>
                    <template v-if="canManage">
                        <button class="btn btn-ghost btn-sm" @click="managing = { member: m, action: 'transfer', cellId: '' }">Transfer</button>
                        <button class="btn btn-soft btn-sm" @click="managing = { member: m, action: 'remove' }">Remove</button>
                    </template>
                </div>
                <p v-if="!members.length" class="card-pad small muted">No members yet.</p>
            </div>

            <!-- Transactions -->
            <div v-if="tab === 'transactions' && canManage" class="card">
                <p v-if="!cell.verified" class="card-pad small" style="color: var(--danger)">This cell isn't verified yet, so transactions can't be recorded.</p>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Date</th><th>Description</th><th class="text-right">Amount</th><th class="text-right hide-sm">Balance</th></tr></thead>
                        <tbody>
                            <tr v-for="t in transactions" :key="t.id">
                                <td class="small muted">{{ fmtDate(t.date) }}</td>
                                <td>{{ t.description }}</td>
                                <td class="text-right" :style="{ color: t.type === 0 ? 'var(--success)' : 'var(--danger)', fontWeight: 600 }">
                                    {{ t.type === 0 ? '+' : '−' }}{{ money(t.amount) }}
                                </td>
                                <td class="text-right small muted hide-sm">{{ money(t.balance) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="!transactions.length" class="card-pad small muted">No transactions yet.</p>
            </div>

            <!-- Join requests -->
            <div v-if="tab === 'requests' && canManage" id="requests" class="card">
                <div v-for="r in joinRequests" :key="r.id" class="list-row cell-row" style="align-items: flex-start">
                    <Avatar :person="{ id: r.id, name: r.name, avatar: r.avatar }" size="sm" />
                    <div class="grow">
                        <div class="t">{{ r.name }}</div>
                        <div class="s">Asked {{ fmtDate(r.date) }}</div>
                        <p v-if="r.message" class="small mt-4" style="color: var(--ink-soft)">“{{ r.message }}”</p>
                    </div>
                    <button class="btn btn-primary btn-sm" :disabled="busy" @click="decide(r, true)">Add to cell</button>
                    <button class="btn btn-soft btn-sm" :disabled="busy" @click="decide(r, false)">Decline</button>
                </div>
                <p v-if="!joinRequests.length" class="card-pad small muted">No pending requests.</p>
            </div>
        </div>

        <!-- New / edit meeting -->
        <Modal v-if="meetingForm" :title="meetingForm.code ? 'Edit meeting' : 'New meeting'" width="560px" @close="meetingForm = null">
            <div class="form-grid">
                <label class="field"><span>Date & time</span><input v-model="meetingForm.date" type="datetime-local" /></label>
                <label class="field"><span>Venue</span><input v-model="meetingForm.venue" type="text" placeholder="e.g. The Bandas' home" /></label>
                <label v-if="meetingForm.code" class="field">
                    <span>Offering (MK)</span>
                    <input v-model="meetingForm.offering" type="number" min="0" step="any" :disabled="!cell.verified" />
                </label>
            </div>
            <template v-if="meetingForm.code">
                <div class="between mt-16 mb-8">
                    <b class="small">Attendance · {{ meetingForm.memberIds.size }} of {{ members.length }}</b>
                    <button class="link small" style="font-weight: 600; color: var(--accent-2)" @click="toggleAll">{{ allPresent ? 'Clear all' : 'Mark all present' }}</button>
                </div>
                <div class="attend-list">
                    <label v-for="m in members" :key="m.id" class="attend-item" :class="{ on: meetingForm.memberIds.has(m.id) }">
                        <input type="checkbox" class="rowcheck" :checked="meetingForm.memberIds.has(m.id)" @change="toggleAttendee(m.id)" />
                        <Avatar :person="m" size="sm" /> <span>{{ m.name }}</span>
                    </label>
                </div>
            </template>
            <p v-if="errors.meeting || errors.date || errors.venue || errors.offering" class="small mt-8" style="color: var(--danger)">
                {{ errors.meeting || errors.date || errors.venue || errors.offering }}
            </p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="meetingForm = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !meetingForm.venue || !meetingForm.date" @click="saveMeeting">{{ busy ? 'Saving…' : 'Save meeting' }}</button>
            </template>
        </Modal>

        <!-- Add member -->
        <Modal v-if="adding" title="Add a member" @close="adding = false">
            <label class="searchbar" style="max-width: none"><Icon name="search" /><input v-model="memberSearch" type="search" placeholder="Search members by name, code or phone…" /></label>
            <div class="mt-8">
                <div v-for="m in memberResults" :key="m.id" class="list-row compact">
                    <Avatar :person="m" size="sm" />
                    <div class="grow">
                        <div class="t">{{ m.name }}</div>
                        <div class="s">{{ m.currentCell ? `Currently in ${m.currentCell}` : 'Not in a cell' }}</div>
                    </div>
                    <button class="btn btn-ghost btn-sm" :disabled="busy" @click="attach(m)">{{ m.currentCell ? 'Move here' : 'Add' }}</button>
                </div>
                <p v-if="memberSearch && !memberResults.length" class="small muted mt-8">No members match “{{ memberSearch }}”.</p>
            </div>
        </Modal>

        <!-- Transfer / remove member -->
        <Modal v-if="managing" :title="managing.action === 'remove' ? 'Remove member' : 'Transfer member'" @close="managing = null">
            <template v-if="managing.action === 'remove'">
                <p class="small">Remove <b>{{ managing.member.name }}</b> from {{ cell.name }}? They'll no longer be in any cell.</p>
            </template>
            <template v-else>
                <p class="small mb-12">Move <b>{{ managing.member.name }}</b> to another cell:</p>
                <label class="field">
                    <span>New cell</span>
                    <select v-model="managing.cellId">
                        <option value="" disabled>Choose a cell…</option>
                        <option v-for="c in otherCells" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </label>
            </template>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="managing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || (managing.action === 'transfer' && !managing.cellId)" @click="doManage">
                    {{ managing.action === 'remove' ? 'Remove' : 'Transfer' }}
                </button>
            </template>
        </Modal>

        <!-- Add transaction -->
        <Modal v-if="txForm" title="Add transaction" @close="txForm = null">
            <div class="segmented mb-12">
                <button :class="{ on: txForm.type === 0 }" @click="txForm.type = 0">Money in</button>
                <button :class="{ on: txForm.type === 1 }" @click="txForm.type = 1">Money out</button>
            </div>
            <div class="form-grid">
                <label class="field"><span>Amount (MK)</span><input v-model="txForm.amount" type="number" min="0" step="any" /></label>
                <label class="field"><span>Description</span><input v-model="txForm.description" type="text" placeholder="e.g. Welfare support" /></label>
            </div>
            <p class="tiny muted mt-8">Balance after: {{ money((cell.balance || 0) + (txForm.type === 0 ? 1 : -1) * (Number(txForm.amount) || 0)) }}</p>
            <p v-if="errors.transaction || errors.amount || errors.description" class="small mt-8" style="color: var(--danger)">
                {{ errors.transaction || errors.amount || errors.description }}
            </p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="txForm = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !(Number(txForm.amount) > 0) || !txForm.description" @click="saveTx">Save</button>
            </template>
        </Modal>
    </UILayout>
</template>
