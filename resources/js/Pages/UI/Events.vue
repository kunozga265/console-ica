<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import { dayNum, fmtDate, fmtDay, monName, tone } from '@/Components/UI/helpers';
import { fileUrl } from '@/Plugins/composables';
import { toast } from '@/Components/UI/useMemberActions';

const props = defineProps({
    events: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) }, // { ministry?, when?: 'past' }
    ministries: { type: Array, default: () => [] },
});

const page = usePage();
const signedIn = computed(() => !!page.props.auth?.user);
const isAdmin = computed(() => !!page.props.isAdmin);
const past = computed(() => props.filters.when === 'past');

const visit = (params) =>
    router.get(route('ui.events'), Object.fromEntries(Object.entries({ ...props.filters, ...params }).filter(([, v]) => v)), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });

/* ---- Ministry filter dialog ---- */
const filtering = ref(false);
const draftMinistry = ref('');
const openFilter = () => {
    draftMinistry.value = props.filters.ministry ? String(props.filters.ministry) : '';
    filtering.value = true;
};
const applyFilter = () => {
    visit({ ministry: draftMinistry.value || null });
    filtering.value = false;
};
const activeMinistry = computed(() => props.ministries.find((m) => String(m.id) === String(props.filters.ministry)));

/* ---- Event details modal ---- */
const openId = ref(null);
const open = computed(() => props.events.find((e) => e.id === openId.value) ?? null);

/* ---- Attending / not attending (signed-in users) ---- */
const busy = ref(null);
const respond = (event, attending) => {
    if (!signedIn.value) return (window.location.href = route('login'));
    // Clicking your current answer again clears it.
    const value = event.myResponse === attending ? null : attending;
    busy.value = event.id;
    router.post(route('ui.events.respond', event.id), { attending: value }, {
        preserveScroll: true,
        preserveState: true,
        only: ['events'],
        onSuccess: () => toast(value === true ? "You're attending · See you there!" : value === false ? 'Marked as not attending' : 'Response cleared'),
        onFinish: () => (busy.value = null),
    });
};

const sameDay = (a, b) => new Date(a).toDateString() === new Date(b).toDateString();
const when = (e) =>
    `${fmtDay(e.date)} ${fmtDate(e.date)}${sameDay(e.date, e.endDate) ? '' : ` – ${fmtDate(e.endDate)}`}${e.time ? ` · ${e.time}` : ''}`;
</script>

<template>
    <UILayout title="Events" page="events" :breadcrumbs="[{ label: 'Events' }]">
        <div class="panel mb-24">
            <div class="section-head">
                <h2>{{ past ? 'Past events' : 'Upcoming events' }}</h2>
                <div class="spacer"></div>
                <div class="segmented">
                    <button :class="{ on: !past }" @click="visit({ when: null })">Upcoming</button>
                    <button :class="{ on: past }" @click="visit({ when: 'past' })">Past</button>
                </div>
                <button class="icon-btn filter-btn" :class="{ on: activeMinistry }" aria-label="Filter by ministry" @click="openFilter">
                    <Icon name="filter" /><span v-if="activeMinistry" class="count">1</span>
                </button>
            </div>

            <div v-if="activeMinistry" class="active-filters mb-16">
                <span class="small muted">{{ events.length }} {{ events.length === 1 ? 'event' : 'events' }}</span>
                <button class="pill" :aria-label="`Remove filter: ${activeMinistry.name}`" @click="visit({ ministry: null })">{{ activeMinistry.name }} <Icon name="close" /></button>
            </div>

            <div v-if="events.length" class="event-grid">
                <article v-for="e in events" :key="e.id" class="card event-card" tabindex="0" @click="openId = e.id" @keydown.enter="openId = e.id">
                    <div class="event-media" :style="e.image ? null : { background: tone(e.title) }">
                        <img v-if="e.image" :src="fileUrl(e.image)" :alt="e.title" loading="lazy" />
                        <div v-else class="event-date-art">
                            <div class="display">{{ dayNum(e.date) }}</div>
                            <div class="eyebrow">{{ monName(e.date) }}</div>
                        </div>
                        <span v-if="e.ministry" class="badge event-ministry">{{ e.ministry.name }}</span>
                    </div>
                    <div class="event-info">
                        <div class="tiny muted">{{ when(e) }}</div>
                        <h3 class="event-title">{{ e.title }}</h3>
                        <div class="small muted flex event-venue" style="gap: 6px"><Icon name="map" /> <span>{{ e.loc || 'Venue to be announced' }}</span></div>
                        <div class="event-foot">
                            <span v-if="isAdmin" class="tiny muted admin-count" title="Visible to admins only">
                                <Icon name="users" /> {{ e.attendingCount }} attending · {{ e.notAttendingCount }} not
                            </span>
                            <span v-else-if="e.myResponse === true" class="badge badge-green"><Icon name="check" /> You're attending</span>
                            <span v-else-if="e.myResponse === false" class="badge badge-outline">Not attending</span>
                            <span v-else></span>
                            <span class="link small">Details →</span>
                        </div>
                    </div>
                </article>
            </div>
            <div v-else class="card card-pad empty-state">
                <Icon name="calendar" />
                <h3>{{ past ? 'No past events' : 'No upcoming events' }}</h3>
                <p class="small muted">{{ activeMinistry ? 'Try another ministry.' : 'Check back soon.' }}</p>
            </div>
        </div>

        <!-- Ministry filter -->
        <Modal v-if="filtering" title="Filter by ministry" width="420px" @close="filtering = false">
            <div class="fp-list" style="max-height: none">
                <button class="fp-option" :class="{ on: draftMinistry === '' }" @click="draftMinistry = ''"><span class="grow">All ministries</span></button>
                <button v-for="m in ministries" :key="m.id" class="fp-option" :class="{ on: draftMinistry === String(m.id) }" @click="draftMinistry = String(m.id)">
                    <span class="grow">{{ m.name }}</span><Icon v-if="draftMinistry === String(m.id)" name="check" />
                </button>
            </div>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="draftMinistry = ''">Reset</button>
                <button class="btn btn-primary btn-sm" @click="applyFilter">Apply</button>
            </template>
        </Modal>

        <!-- Event details -->
        <Modal v-if="open" :title="open.title" width="640px" @close="openId = null">
            <div class="event-detail-media" :style="open.image ? null : { background: tone(open.title) }">
                <img v-if="open.image" :src="fileUrl(open.image)" :alt="open.title" />
                <div v-else class="event-date-art"><div class="display">{{ dayNum(open.date) }}</div><div class="eyebrow">{{ monName(open.date) }}</div></div>
            </div>
            <div class="flex gap-8 wrap mt-16">
                <span v-if="open.ministry" class="badge badge-blue">{{ open.ministry.name }}</span>
                <span class="small muted">{{ when(open) }}</span>
            </div>
            <div v-if="open.loc" class="small muted flex mt-8" style="gap: 6px"><Icon name="map" /> {{ open.loc }}</div>
            <div v-if="open.body" class="rich-text mt-16" v-html="open.body"></div>
            <p v-else class="small muted mt-16">No further details yet.</p>
            <p v-if="isAdmin" class="tiny muted mt-16 admin-count"><Icon name="users" /> {{ open.attendingCount }} attending · {{ open.notAttendingCount }} not attending <span>(admins only)</span></p>
            <template v-if="!past" #footer>
                <span class="small muted">{{ signedIn ? 'Will you be there?' : 'Sign in to respond' }}</span>
                <div class="flex gap-8">
                    <button class="btn btn-sm" :class="open.myResponse === false ? 'btn-soft on-neg' : 'btn-ghost'" :disabled="busy === open.id" @click="respond(open, false)">Not attending</button>
                    <button class="btn btn-sm" :class="open.myResponse === true ? 'btn-primary' : 'btn-ghost'" :disabled="busy === open.id" @click="respond(open, true)">
                        <Icon name="check" /> Attending
                    </button>
                </div>
            </template>
        </Modal>
    </UILayout>
</template>
