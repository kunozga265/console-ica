<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    registers: { type: Array, default: () => [] },
    selected: { type: String, default: null },
    summary: { type: Object, default: null },
    trend: { type: Array, default: () => [] },
    cells: { type: Array, default: () => [] },
});

const pick = (code) => router.get(route('admin.attendance'), { register: code }, { preserveState: true, preserveScroll: true, replace: true });
const change = computed(() => (props.summary?.previous ? Math.round(((props.summary.present - props.summary.previous) / props.summary.previous) * 100) : null));
const maxTrend = computed(() => Math.max(1, ...props.trend.map((t) => t.value)));

// Donut: male vs female among those present.
const C = 2 * Math.PI * 70;
const genderTotal = computed(() => (props.summary ? props.summary.gender.Male + props.summary.gender.Female : 0));
const maleArc = computed(() => (genderTotal.value ? (props.summary.gender.Male / genderTotal.value) * C : 0));

const rate = (c) => (c.expected && c.present !== null ? Math.round((c.present / c.expected) * 100) : null);
const rateColor = (r) => (r >= 75 ? 'var(--success)' : r >= 50 ? 'var(--warn)' : 'var(--danger)');
const exportCsv = () => {
    const rows = [['Cell', 'Leaders', 'Members', 'Present at last meeting', 'Last meeting'], ...props.cells.map((c) => [c.name, c.leaders, c.expected, c.present ?? '', c.lastMeeting ? fmtDate(c.lastMeeting) : ''])];
    const csv = rows.map((r) => r.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
    const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([csv], { type: 'text/csv' })), download: 'attendance-by-cell.csv' });
    a.click();
};
</script>

<template>
    <AdminLayout title="Attendance" page="attendance" crumb="Community">
        <div class="between wrap mb-20" style="gap: 12px">
            <select v-if="registers.length" :value="selected" class="chip-select wide" @change="pick($event.target.value)">
                <option v-for="r in registers" :key="r.code" :value="r.code">{{ r.name }} · {{ fmtDate(r.date) }} ({{ r.present }})</option>
            </select>
            <div class="flex gap-8">
                <Link class="btn btn-ghost btn-sm" :href="route('admin.registers.index')"><Icon name="calendar" /> Registers</Link>
                <Link v-if="selected" class="btn btn-primary" :href="route('admin.registers.show', selected)"><Icon name="check" /> Take attendance</Link>
            </div>
        </div>

        <div v-if="!summary" class="card card-pad empty-state"><Icon name="check" /><h3>No registers yet</h3><p class="small muted">Create one under Registers.</p></div>
        <template v-else>
            <div class="grid stat-grid mb-20">
                <div class="stat"><div class="top"><span class="ico ico-green"><Icon name="users" /></span><span class="label">Present</span></div><div class="value">{{ summary.present.toLocaleString() }}</div><div class="foot"><span v-if="change !== null" class="trend" :class="change >= 0 ? 'up' : 'down'">{{ change >= 0 ? '+' : '' }}{{ change }}%</span> {{ change !== null ? 'vs previous' : summary.name }}</div></div>
                <div class="stat"><div class="top"><span class="ico ico-brown"><Icon name="grid" /></span><span class="label">Of all members</span></div><div class="value">{{ summary.membersTotal ? Math.round((summary.present / summary.membersTotal) * 100) : 0 }}%</div><div class="foot muted">{{ summary.membersTotal.toLocaleString() }} members</div></div>
                <div class="stat"><div class="top"><span class="ico ico-gold"><Icon name="star" /></span><span class="label">Visitors</span></div><div class="value">{{ summary.visitors }}</div><div class="foot muted">Not yet registered</div></div>
                <div class="stat"><div class="top"><span class="ico ico-blue"><Icon name="plus" /></span><span class="label">New this week</span></div><div class="value">{{ summary.firstTime }}</div><div class="foot muted">Added to the directory</div></div>
            </div>

            <div class="grid dash-grid mb-20">
                <section class="card">
                    <div class="card-head"><div><h3>Attendance trend</h3><div class="sub">Last {{ trend.length }} services</div></div></div>
                    <div class="card-pad">
                        <div class="chart-bars">
                            <div v-for="(t, i) in trend" :key="i" class="col" :title="`${t.name}: ${t.value}`">
                                <div class="stack" :style="{ height: `${Math.max(2, (t.value / maxTrend) * 100)}%` }"><div class="seg p" style="flex: 1"></div></div>
                                <span class="xl">{{ t.label }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card card-pad">
                    <h3 style="font-size: 16px" class="mb-16">Who's here · {{ fmtDate(summary.date) }}</h3>
                    <div class="flex" style="justify-content: center; margin: 6px 0 18px">
                        <svg class="donut" width="176" height="176" viewBox="0 0 176 176">
                            <circle cx="88" cy="88" r="70" fill="none" stroke="#4ec3c3" stroke-width="20" />
                            <circle cx="88" cy="88" r="70" fill="none" stroke="var(--primary)" stroke-width="20" :stroke-dasharray="`${maleArc} ${C}`" transform="rotate(-90 88 88)" />
                            <text x="88" y="84" text-anchor="middle" font-size="30" font-weight="600" fill="var(--ink)" font-family="Poppins, sans-serif">{{ summary.present }}</text>
                            <text x="88" y="104" text-anchor="middle" font-size="12" fill="var(--muted)" font-family="Inter, sans-serif">present</text>
                        </svg>
                    </div>
                    <div class="grid" style="gap: 10px">
                        <div class="between"><span class="flex" style="gap: 9px"><i class="dot-key" style="background: var(--primary)"></i> Men</span><b>{{ summary.gender.Male }}</b></div>
                        <div class="between"><span class="flex" style="gap: 9px"><i class="dot-key" style="background: #4ec3c3"></i> Women</span><b>{{ summary.gender.Female }}</b></div>
                    </div>
                </section>
            </div>
        </template>

        <section class="card">
            <div class="card-head">
                <h3>Attendance by cell group</h3>
                <div class="spacer"></div>
                <button class="btn btn-soft btn-sm" @click="exportCsv"><Icon name="download" /> Export</button>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Cell group</th><th>Leaders</th><th>Members</th><th>Present (last meeting)</th><th style="width: 220px">Rate</th></tr></thead>
                    <tbody>
                        <tr v-for="c in cells" :key="c.name">
                            <td class="t" style="font-weight: 600">{{ c.name }}</td>
                            <td class="small muted">{{ c.leaders || '—' }}</td>
                            <td class="small">{{ c.expected }}</td>
                            <td class="small" style="font-weight: 600">{{ c.present ?? '—' }}<span v-if="c.lastMeeting" class="tiny muted"> · {{ fmtDate(c.lastMeeting) }}</span></td>
                            <td>
                                <div v-if="rate(c) !== null" class="flex" style="gap: 12px">
                                    <div class="bar grow"><i :style="{ width: `${Math.min(100, rate(c))}%`, background: rateColor(rate(c)) }"></i></div>
                                    <span class="small" style="font-weight: 600; min-width: 38px; text-align: right">{{ rate(c) }}%</span>
                                </div>
                                <span v-else class="tiny muted">No meetings yet</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminLayout>
</template>
