<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { fmtDate, tone } from '@/Components/UI/helpers';

const props = defineProps({
    greetingName: { type: String, default: '' },
    stats: { type: Object, required: true },
    weeks: { type: Array, default: () => [] }, // [{ label, services, cells }]
    sermons: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
    prayer: { type: Object, default: null },
    birthdays: { type: Array, default: () => [] },
});

const now = new Date();
const today = now.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const greeting = now.getHours() < 12 ? 'Good morning' : now.getHours() < 18 ? 'Good afternoon' : 'Good evening';

const money = (n) => `MK ${Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
const pct = (a, b) => (b ? Math.round(((a - b) / b) * 100) : null);
const serviceTrend = computed(() => (props.stats.lastService && props.stats.previousService ? pct(props.stats.lastService.present, props.stats.previousService.present) : null));
const offeringTrend = computed(() => pct(props.stats.offeringsThisMonth, props.stats.offeringsLastMonth));

// Stacked bars: services (primary) + cell meetings (teal), scaled to the busiest week.
const maxWeek = computed(() => Math.max(1, ...props.weeks.map((w) => w.services + w.cells)));
const dayMon = (ms) => {
    const d = new Date(ms);
    return { d: d.getDate(), m: d.toLocaleString('en', { month: 'short' }).toUpperCase() };
};
</script>

<template>
    <AdminLayout title="Dashboard" page="dashboard" crumb="Home">
        <div class="between wrap mb-24">
            <div>
                <div class="eyebrow mb-8">{{ today }}</div>
                <h1 style="font-size: 30px">{{ greeting }}, {{ greetingName }}</h1>
                <p class="muted mt-4">Here's what's happening across the church this week.</p>
            </div>
            <div class="flex gap-8">
                <Link class="btn btn-ghost btn-sm" :href="route('ui.attendance')"><Icon name="check" /> Take attendance</Link>
                <Link class="btn btn-primary" :href="route('admin.sermons.create')"><Icon name="plus" /> New sermon</Link>
            </div>
        </div>

        <div class="grid stat-grid mb-20">
            <div class="stat reveal">
                <div class="top"><span class="ico ico-brown"><Icon name="users" /></span><span class="label">Members</span></div>
                <div class="value">{{ stats.members.toLocaleString() }}</div>
                <div class="foot">
                    <span class="trend" :class="stats.membersNewThisMonth ? 'up' : 'flat'">+{{ stats.membersNewThisMonth }}</span> this month · {{ stats.visitors }} visitors
                </div>
            </div>
            <div class="stat reveal" style="animation-delay: 0.05s">
                <div class="top"><span class="ico ico-gold"><Icon name="check" /></span><span class="label">Last service</span></div>
                <div class="value">{{ stats.lastService ? stats.lastService.present.toLocaleString() : '—' }}</div>
                <div class="foot">
                    <span v-if="serviceTrend !== null" class="trend" :class="serviceTrend >= 0 ? 'up' : 'down'">{{ serviceTrend >= 0 ? '+' : '' }}{{ serviceTrend }}%</span>
                    {{ stats.lastService ? `${stats.lastService.name} · ${fmtDate(stats.lastService.date)}` : 'No registers yet' }}
                </div>
            </div>
            <div class="stat reveal" style="animation-delay: 0.1s">
                <div class="top"><span class="ico ico-blue"><Icon name="mic" /></span><span class="label">Sermons published</span></div>
                <div class="value">{{ stats.sermons.toLocaleString() }}</div>
                <div class="foot"><span class="trend" :class="stats.sermonsThisMonth ? 'up' : 'flat'">+{{ stats.sermonsThisMonth }}</span> this month</div>
            </div>
            <div class="stat reveal" style="animation-delay: 0.15s">
                <div class="top"><span class="ico ico-green"><Icon name="heart" /></span><span class="label">Cell offerings · {{ stats.monthName }}</span></div>
                <div class="value">{{ money(stats.offeringsThisMonth) }}</div>
                <div class="foot">
                    <span v-if="offeringTrend !== null" class="trend" :class="offeringTrend >= 0 ? 'up' : 'down'">{{ offeringTrend >= 0 ? '+' : '' }}{{ offeringTrend }}%</span>
                    vs {{ money(stats.offeringsLastMonth) }} last month
                </div>
            </div>
        </div>

        <div class="grid dash-grid mb-20">
            <div class="grid" style="gap: 20px">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h3>Attendance overview</h3>
                            <div class="sub">Last 8 weeks · services &amp; cell meetings</div>
                        </div>
                        <div class="spacer"></div>
                        <Link class="btn btn-soft btn-sm" :href="route('admin.attendance')">Details <Icon name="chevright" /></Link>
                    </div>
                    <div class="card-pad">
                        <div v-if="!weeks.some((w) => w.services + w.cells)" class="chart-empty small muted">
                            No attendance recorded in the last 8 weeks. Registers and cell meetings will show here.
                        </div>
                        <div v-else class="chart-bars">
                            <div v-for="w in weeks" :key="w.label" class="col" :title="`${w.services} at services · ${w.cells} at cell meetings`">
                                <div class="stack" :style="{ height: `${Math.max(2, ((w.services + w.cells) / maxWeek) * 100)}%` }">
                                    <div class="seg a" :style="{ flex: w.cells || 0.0001 }"></div>
                                    <div class="seg p" :style="{ flex: w.services || 0.0001 }"></div>
                                </div>
                                <span class="xl">{{ w.label }}</span>
                            </div>
                        </div>
                        <div class="legend mt-16">
                            <span><i style="background: var(--primary)"></i> Services</span>
                            <span><i style="background: #4ec3c3"></i> Cell meetings</span>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-head">
                        <h3>Latest sermons</h3>
                        <div class="spacer"></div>
                        <Link class="btn btn-soft btn-sm" :href="route('admin.sermons.index')">View all <Icon name="chevright" /></Link>
                    </div>
                    <div class="card-pad" style="padding-top: 6px; padding-bottom: 8px">
                        <Link v-for="s in sermons" :key="s.id" class="list-row" :href="route('admin.sermons.edit', s.id)">
                            <div class="thumb-sm" :style="{ background: tone(s.title), display: 'grid', placeItems: 'center', color: 'rgba(255,255,255,.9)' }"><Icon name="play" /></div>
                            <div class="grow">
                                <div class="t">{{ s.title }}</div>
                                <div class="s">{{ s.author }}<template v-if="s.series"> · <span class="muted">{{ s.series }}</span></template></div>
                            </div>
                            <div class="text-right hide-sm">
                                <div class="small" style="font-weight: 600">{{ fmtDate(s.date) }}</div>
                                <div class="tiny muted">{{ s.views.toLocaleString() }} views</div>
                            </div>
                        </Link>
                        <p v-if="!sermons.length" class="small muted" style="padding: 12px 0">No sermons yet.</p>
                    </div>
                </section>
            </div>

            <div class="grid" style="gap: 20px">
                <section class="card">
                    <div class="card-head">
                        <h3>Upcoming events</h3>
                        <div class="spacer"></div>
                        <Link class="btn btn-soft btn-sm" :href="route('admin.events.index')">All</Link>
                    </div>
                    <div class="card-pad" style="padding-top: 8px; padding-bottom: 10px">
                        <div v-for="e in events" :key="e.id" class="list-row">
                            <div class="date-chip"><div class="display">{{ dayMon(e.date).d }}</div><div class="tiny muted">{{ dayMon(e.date).m }}</div></div>
                            <div class="grow">
                                <div class="t">{{ e.title }}</div>
                                <div class="s">{{ [e.time, e.venue].filter(Boolean).join(' · ') }}</div>
                            </div>
                            <span v-if="e.ministry" class="badge badge-blue hide-sm">{{ e.ministry }}</span>
                        </div>
                        <p v-if="!events.length" class="small muted" style="padding: 12px 0">No upcoming events.</p>
                    </div>
                </section>

                <section class="card card-pad">
                    <div class="between mb-16">
                        <h3 style="font-size: 16px">This week at church</h3>
                        <Link class="btn btn-soft btn-sm" :href="route('admin.prayer.index')">Prayer</Link>
                    </div>
                    <div class="flex" style="align-items: flex-start; gap: 12px">
                        <span class="ico ico-plum ico-box"><Icon name="pray" /></span>
                        <div class="grow">
                            <div class="tiny muted caps">Prayer point</div>
                            <template v-if="prayer">
                                <div style="font-weight: 600; margin-top: 2px">{{ prayer.title }}</div>
                                <div class="small muted">{{ prayer.verses || fmtDate(prayer.date * 1000) }}</div>
                            </template>
                            <div v-else class="small muted">None scheduled — <Link :href="route('admin.prayer.index')" class="link">add one</Link></div>
                        </div>
                    </div>
                    <hr class="divider" style="margin: 16px 0" />
                    <div class="flex" style="align-items: flex-start; gap: 12px">
                        <span class="ico ico-gold ico-box"><Icon name="star" /></span>
                        <div class="grow">
                            <div class="tiny muted caps">Birthdays this week</div>
                            <div v-for="b in birthdays.slice(0, 5)" :key="b.id" class="flex mt-8" style="gap: 10px">
                                <Avatar :person="b" size="sm" />
                                <div><div style="font-weight: 600; font-size: 13.5px">{{ b.name }}</div><div class="tiny muted">Turns {{ b.age }} · {{ fmtDate(b.date) }} 🎂</div></div>
                            </div>
                            <div v-if="birthdays.length > 5" class="tiny muted mt-8">+{{ birthdays.length - 5 }} more</div>
                            <div v-if="!birthdays.length" class="small muted mt-4">No birthdays this week.</div>
                        </div>
                    </div>
                </section>

                <section class="card card-pad">
                    <div class="between mb-12">
                        <h3 style="font-size: 16px">{{ stats.monthName }} cell offerings</h3>
                        <span v-if="offeringTrend !== null" class="trend" :class="offeringTrend >= 0 ? 'up' : 'down'">{{ offeringTrend >= 0 ? '+' : '' }}{{ offeringTrend }}%</span>
                    </div>
                    <div class="flex" style="align-items: baseline; gap: 8px; margin-bottom: 12px">
                        <span class="display" style="font-size: 26px">{{ money(stats.offeringsThisMonth) }}</span>
                        <span class="muted small">recorded by cells</span>
                    </div>
                    <div class="bar"><i :style="{ width: `${Math.min(100, stats.offeringsLastMonth ? (stats.offeringsThisMonth / stats.offeringsLastMonth) * 100 : 0)}%` }"></i></div>
                    <div class="between mt-12">
                        <span class="small muted">vs last month</span>
                        <span class="small" style="font-weight: 600">{{ money(stats.offeringsLastMonth) }}</span>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
