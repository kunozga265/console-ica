<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import SermonCard from '@/Components/UI/SermonCard.vue';
import { fmtDate, fmtDay } from '@/Components/UI/helpers';

const props = defineProps({
    profile: { type: Object, required: true },
    notes: { type: Array, default: () => [] },
    saved: { type: Array, default: () => [] },
    favorites: { type: Array, default: () => [] },
    bookmarks: { type: Array, default: () => [] }, // bookmarked sermon lines
    attendance: { type: Object, default: null }, // { items, stats } — null without a member profile
    receipts: { type: Array, default: () => [] }, // tithe receipts issued to this member
    proofs: { type: Array, default: () => [] }, // proofs of payment they sent
    notificationSettings: { type: Object, default: null }, // { categories, labels, ministries }
});

const TABS = computed(() => [
    { id: 'notes', label: 'Notes', count: props.notes.length },
    { id: 'saved', label: 'Saved sermons', count: props.saved.length },
    { id: 'favorites', label: 'Favorites', count: props.favorites.length },
    { id: 'lines', label: 'Bookmarked lines', count: props.bookmarks.length },
    { id: 'attendance', label: 'Attendance', count: props.attendance?.stats.total ?? 0 },
    { id: 'receipts', label: 'Tithe receipts', count: props.receipts.length },
    { id: 'notifications', label: 'Notifications', count: 0 },
]);
const tab = ref('notes');
onMounted(() => {
    const h = window.location.hash.slice(1);
    if (TABS.value.some((t) => t.id === h)) tab.value = h;
});
const pick = (id) => {
    tab.value = id;
    history.replaceState(history.state, '', `#${id}`);
};

// Notes may be rich text (web) or plain text (older mobile notes).
const isHtml = (s) => /<\/?[a-z][\s\S]*>/i.test(s || '');

/* ---- Tithe receipts + proof of payment ---- */
const money = (n) => `MWK ${Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const PROOF_STATUS = { pending: ['Sent', 'badge-gold'], acknowledged: ['Seen by finance', 'badge-blue'], receipted: ['Receipted', 'badge-green'] };
const proofForm = useForm({ file: null, amount: '', paid_on: new Date().toISOString().slice(0, 10), note: '' });
const fileInput = ref(null);
const sendProof = () =>
    proofForm.post(route('ui.proofs.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            proofForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });

/* ---- Notification settings ---- */
const ns = ref({
    categories: { ...(props.notificationSettings?.categories ?? {}) },
    ministries: (props.notificationSettings?.ministries ?? []).filter((m) => m.subscribed).map((m) => m.id),
});
const savingNs = ref(false);
const saveNs = () => {
    savingNs.value = true;
    router.put(route('ui.notifications.settings'), ns.value, { preserveScroll: true, onFinish: () => (savingNs.value = false) });
};

const attFilter = ref('all');
const attItems = computed(() => (props.attendance?.items ?? []).filter((i) => attFilter.value === 'all' || i.kind === attFilter.value));
</script>

<template>
    <UILayout title="My profile" page="profile" :breadcrumbs="[{ label: 'Profile' }]">
        <div class="panel mb-24">
            <div class="card card-pad profile-head mb-20">
                <Avatar :person="{ id: 1, name: profile.name, avatar: profile.avatar }" size="lg" />
                <div class="grow" style="min-width: 0">
                    <h2 style="font-size: 24px; color: var(--ink)">{{ profile.name }}</h2>
                    <div class="small muted">{{ profile.email }}<template v-if="profile.memberCode"> · Member {{ profile.memberCode }}</template></div>
                    <div class="flex gap-8 mt-8 wrap">
                        <Link v-if="profile.leadershipCell" :href="route('ui.cells.show', profile.leadershipCell.code)" class="badge badge-gold">Leads {{ profile.leadershipCell.name }}</Link>
                        <Link v-if="profile.cell" :href="route('ui.cells.show', profile.cell.code)" class="badge badge-blue">Cell: {{ profile.cell.name }}</Link>
                    </div>
                </div>
                <div class="flex gap-8 wrap">
                    <a v-if="$page.props.isAdmin" :href="route('admin.dashboard')" class="btn btn-primary btn-sm"><Icon name="grid" /> Admin dashboard</a>
                    <a :href="route('profile.show')" class="btn btn-ghost btn-sm">Account settings</a>
                    <Link :href="route('ui.deactivate')" class="btn btn-soft btn-sm" style="color: var(--danger)">Delete account</Link>
                </div>
            </div>
        </div>

        <div class="panel mb-24">
            <div class="segmented mb-16 profile-tabs">
                <button v-for="t in TABS" :key="t.id" :class="{ on: tab === t.id }" @click="pick(t.id)">
                    {{ t.label }}<span v-if="t.count" class="seg-count">{{ t.count }}</span>
                </button>
            </div>

            <!-- Notes -->
            <div v-if="tab === 'notes'">
                <div v-if="notes.length" class="grid cols-2">
                    <Link v-for="n in notes" :key="n.id" :href="route('ui.sermons.show', n.sermonId)" class="card card-pad note-card">
                        <div class="tiny muted">{{ fmtDate(n.date) }}</div>
                        <div class="note-sermon">{{ n.sermonTitle }}</div>
                        <div v-if="isHtml(n.body)" class="rich-text clamp-3 small" v-html="n.body"></div>
                        <p v-else class="small clamp-3" style="white-space: pre-line; color: var(--ink-soft)">{{ n.body }}</p>
                    </Link>
                </div>
                <div v-else class="card card-pad empty-state"><Icon name="comment" /><h3>No notes yet</h3><p class="small muted">Notes you write on a sermon appear here.</p></div>
            </div>

            <!-- Saved / favorites -->
            <template v-for="list in [{ id: 'saved', items: saved, empty: 'Tap the bookmark on any sermon to save it for later.' }, { id: 'favorites', items: favorites, empty: 'Tap the heart on a sermon to add it to your favorites.' }]" :key="list.id">
                <div v-if="tab === list.id">
                    <div v-if="list.items.length" class="grid-sermons">
                        <SermonCard v-for="s in list.items" :key="s.id" :sermon="s" />
                    </div>
                    <div v-else class="card card-pad empty-state"><Icon :name="list.id === 'saved' ? 'bookmark' : 'heart'" /><h3>Nothing here yet</h3><p class="small muted">{{ list.empty }}</p></div>
                </div>
            </template>

            <!-- Bookmarked lines -->
            <div v-if="tab === 'lines'">
                <div v-if="bookmarks.length" class="card">
                    <Link v-for="b in bookmarks" :key="b.id" :href="route('ui.sermons.show', b.sermonId)" class="list-row cell-row" style="align-items: flex-start">
                        <span class="ico ico-blue" style="width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex: none"><Icon name="bookmark" /></span>
                        <div class="grow">
                            <div class="quote">“{{ b.caption }}”</div>
                            <div class="s mt-4">{{ b.sermonTitle }} · {{ fmtDate(b.date) }}</div>
                        </div>
                    </Link>
                </div>
                <div v-else class="card card-pad empty-state"><Icon name="bookmark" /><h3>No bookmarked lines</h3><p class="small muted">Tap a sentence in a sermon and choose Bookmark.</p></div>
            </div>

            <!-- Attendance -->
            <div v-if="tab === 'attendance'">
                <div v-if="!attendance" class="card card-pad empty-state">
                    <Icon name="info" /><h3>No member profile</h3><p class="small muted">Your account isn't linked to a church member yet, so there's no attendance to show.</p>
                </div>
                <template v-else>
                    <div class="grid cols-3 mb-20">
                        <div class="stat"><div class="top"><span class="ico ico-green"><Icon name="check" /></span><span class="label">Services this year</span></div><div class="value">{{ attendance.stats.servicesThisYear }}</div></div>
                        <div class="stat"><div class="top"><span class="ico ico-blue"><Icon name="users" /></span><span class="label">Cell meetings this year</span></div><div class="value">{{ attendance.stats.cellThisYear }}</div></div>
                        <div class="stat"><div class="top"><span class="ico ico-gold"><Icon name="star" /></span><span class="label">All time</span></div><div class="value">{{ attendance.stats.total }}</div></div>
                    </div>
                    <div class="chips mb-12">
                        <button class="chip" :class="{ on: attFilter === 'all' }" @click="attFilter = 'all'">All</button>
                        <button class="chip" :class="{ on: attFilter === 'service' }" @click="attFilter = 'service'">Services</button>
                        <button class="chip" :class="{ on: attFilter === 'cell' }" @click="attFilter = 'cell'">Cell meetings</button>
                    </div>
                    <div v-if="attItems.length" class="card">
                        <div v-for="(a, i) in attItems" :key="i" class="list-row cell-row">
                            <span class="ico" :class="a.kind === 'service' ? 'ico-green' : 'ico-blue'" style="width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex: none">
                                <Icon :name="a.kind === 'service' ? 'check' : 'users'" />
                            </span>
                            <div class="grow">
                                <div class="t">{{ a.title }}</div>
                                <div class="s">{{ [a.kind === 'service' ? 'Service' : 'Cell meeting', a.detail].filter(Boolean).join(' · ') }}</div>
                            </div>
                            <span class="tiny muted" style="flex: none">{{ fmtDay(a.date) }} {{ fmtDate(a.date) }}</span>
                        </div>
                    </div>
                    <div v-else class="card card-pad small muted">No attendance recorded yet.</div>
                </template>
            </div>

            <!-- Tithe receipts -->
            <div v-if="tab === 'receipts'" class="grid receipts-grid" style="align-items: start">
                <section>
                    <div v-if="receipts.length" class="card">
                        <a v-for="r in receipts" :key="r.id" :href="route('ui.receipts.pdf', r.id)" target="_blank" rel="noopener" class="list-row cell-row">
                            <span class="ico ico-green" style="width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex: none"><Icon name="check" /></span>
                            <div class="grow">
                                <div class="t">{{ money(r.amount) }}</div>
                                <div class="s">{{ r.number }} · {{ r.methodName }}<template v-if="r.reference"> · {{ r.reference }}</template></div>
                            </div>
                            <span class="tiny muted" style="flex: none">{{ fmtDate(new Date(`${r.receivedOn}T00:00:00`)) }}</span>
                            <span class="btn btn-ghost btn-sm" style="flex: none">PDF</span>
                        </a>
                    </div>
                    <div v-else class="card card-pad empty-state"><Icon name="check" /><h3>No receipts yet</h3><p class="small muted">Receipts from the finance team appear here, and are emailed to you.</p></div>

                    <template v-if="proofs.length">
                        <h3 class="mt-20 mb-8" style="font-size: 16px">Proofs of payment you sent</h3>
                        <div class="card">
                            <div v-for="p in proofs" :key="p.id" class="list-row cell-row">
                                <div class="grow">
                                    <div class="t">{{ p.amount ? money(p.amount) : p.fileName || 'Proof of payment' }}</div>
                                    <div class="s">{{ p.paidOn ? `Paid ${fmtDate(new Date(`${p.paidOn}T00:00:00`))}` : 'Sent' }} · {{ fmtDate(p.submittedAt) }}</div>
                                </div>
                                <span class="badge" :class="PROOF_STATUS[p.status][1]">{{ PROOF_STATUS[p.status][0] }}</span>
                            </div>
                        </div>
                    </template>
                </section>

                <section class="card card-pad">
                    <h3 style="font-size: 16px">Send proof of payment</h3>
                    <p class="small muted mt-4 mb-12">Paid by bank or mobile money? Upload the slip or screenshot and the finance team will send your receipt.</p>
                    <div v-if="!profile.memberCode" class="small muted">Your account isn't linked to a member profile yet.</div>
                    <form v-else @submit.prevent="sendProof">
                        <label class="field"><span>Photo or PDF</span><input ref="fileInput" type="file" accept="image/*,application/pdf" @change="proofForm.file = $event.target.files[0] ?? null" /></label>
                        <div class="form-grid mt-8">
                            <label class="field"><span>Amount (MWK, optional)</span><input v-model="proofForm.amount" type="number" min="0" step="0.01" inputmode="decimal" /></label>
                            <label class="field"><span>Date paid</span><input v-model="proofForm.paid_on" type="date" /></label>
                        </div>
                        <label class="field mt-8"><span>Note (optional)</span><textarea v-model="proofForm.note" rows="2" placeholder="e.g. Tithe for October"></textarea></label>
                        <p v-for="e in [proofForm.errors.file, proofForm.errors.amount, proofForm.errors.paid_on, proofForm.errors.note].filter(Boolean)" :key="e" class="field-error">{{ e }}</p>
                        <button class="btn btn-primary btn-sm mt-12" :disabled="proofForm.processing || !proofForm.file">{{ proofForm.processing ? 'Sending…' : 'Send to finance' }}</button>
                    </form>
                </section>
            </div>

            <!-- Notification settings -->
            <div v-if="tab === 'notifications' && notificationSettings" class="card card-pad" style="max-width: 640px">
                <h3 style="font-size: 16px">What would you like to hear about?</h3>
                <p class="small muted mt-4 mb-12">These settings apply to the ICA app on all your devices. Birthday wishes, receipts and event reminders always reach you.</p>
                <label v-for="(meta, key) in notificationSettings.labels" :key="key" class="list-row" style="cursor: pointer">
                    <div class="grow"><div class="t">{{ meta.label }}</div><div class="s">{{ meta.hint }}</div></div>
                    <input v-model="ns.categories[key]" type="checkbox" class="switch" />
                </label>
                <div v-if="ns.categories.sermons" class="mt-16">
                    <div class="tiny muted caps mb-8">Sermons from these ministries</div>
                    <div class="chips">
                        <label v-for="m in notificationSettings.ministries" :key="m.id" class="chip" :class="{ on: ns.ministries.includes(m.id) }" style="cursor: pointer">
                            <input v-model="ns.ministries" type="checkbox" :value="m.id" style="display: none" />{{ m.name }}
                        </label>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm mt-16" :disabled="savingNs" @click="saveNs">{{ savingNs ? 'Saving…' : 'Save settings' }}</button>
            </div>
        </div>
    </UILayout>
</template>
