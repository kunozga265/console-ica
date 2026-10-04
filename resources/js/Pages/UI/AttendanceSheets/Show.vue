<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import RegisterForm from '@/Components/UI/RegisterForm.vue';
import PersonForm from '@/Components/UI/PersonForm.vue';
import Modal from '@/Components/UI/Modal.vue';
import { copyText, fmtDate, fmtDay } from '@/Components/UI/helpers';
import { toast } from '@/Components/UI/useMemberActions';
import { downloadPdf, downloadPng, printPoster } from '@/Components/UI/qrPoster';

const props = defineProps({
    register: { type: Object, required: true }, // { code, name, ministry, date, active, attendeeCount }
    attendees: { type: Array, default: () => [] }, // members marked present
    results: { type: Array, default: () => [] }, // member search results, with `marked`
    q: { type: String, default: '' },
    checkInUrl: { type: String, required: true },
    qrSvg: { type: String, required: true },
    ministries: { type: Array, default: () => [] },
    cells: { type: Array, default: () => [] },
});

const editing = ref(false);
const adding = ref(false);
const confirmingDelete = ref(false);
const destroy = () => router.delete(route('ui.attendance.destroy', props.register.code), { onSuccess: () => toast('Register deleted') });

const page = usePage();
const error = computed(() => page.props.errors?.register);

/* ---- Search members to mark (server search, partial reload) ---- */
const query = ref(props.q);
let debounce;
watch(query, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => router.reload({ data: { q: v || undefined }, only: ['results', 'q'], preserveState: true, preserveScroll: true, replace: true }), 300);
});

const busy = ref(null);
const setMarked = (member, marked) => {
    busy.value = member.id;
    router.post(route('ui.attendance.toggle', props.register.code), { memberId: member.id, marked }, {
        preserveScroll: true,
        preserveState: true,
        only: ['attendees', 'results', 'register', 'errors'],
        onSuccess: () => !error.value && toast(marked ? `${member.name} marked present` : `${member.name} unmarked`),
        onFinish: () => (busy.value = null),
    });
};

const filterAttendees = ref('');
const shownAttendees = computed(() => {
    const f = filterAttendees.value.trim().toLowerCase();
    return f ? props.attendees.filter((a) => a.name.toLowerCase().includes(f)) : props.attendees;
});
const markedTime = (ms) => (ms ? new Date(ms).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '');

const copyLink = () => {
    copyText(props.checkInUrl);
    toast('Check-in link copied');
};
/* QR poster export — renders just the poster, never the whole page. */
const exporting = ref(null);
const posterOpts = () => ({
    svg: props.qrSvg,
    title: props.register.name,
    subtitle: [`${fmtDay(props.register.date)} ${fmtDate(props.register.date)}`, props.register.ministry].filter(Boolean).join(' · '),
    url: props.checkInUrl,
});
const exportQr = async (kind) => {
    exporting.value = kind;
    try {
        await { png: downloadPng, pdf: downloadPdf, print: printPoster }[kind](posterOpts());
    } catch (e) {
        console.error(e);
        toast("Couldn't export the QR code");
    } finally {
        exporting.value = null;
    }
};
</script>

<template>
    <UILayout
        :title="register.name"
        page="attendance"
        :breadcrumbs="[{ label: 'More' }, { label: 'Attendance Sheets', href: route('ui.attendance') }, { label: register.name }]"
    >
        <div class="panel mb-24">
            <div class="register-head card card-pad mb-24">
                <div class="grow" style="min-width: 0">
                    <div class="flex gap-8 mb-8 wrap">
                        <span v-if="register.active" class="badge badge-red live"><span class="bullet"></span>Active today</span>
                        <span v-else class="badge badge-outline">{{ register.date > Date.now() ? 'Upcoming' : 'Closed' }}</span>
                        <span class="badge badge-blue">{{ register.ministry }}</span>
                    </div>
                    <h2 style="font-size: 24px; color: var(--ink)">{{ register.name }}</h2>
                    <div class="small muted mt-4">{{ fmtDay(register.date) }} {{ fmtDate(register.date) }}</div>
                </div>
                <div class="count"><b>{{ register.attendeeCount }}</b><span>present</span></div>
                <div class="register-actions">
                    <button class="btn btn-ghost btn-sm" @click="editing = true">Edit</button>
                    <button class="btn btn-soft btn-sm on-neg" @click="confirmingDelete = true">Delete</button>
                </div>
            </div>
        </div>

        <div class="panel mb-24">
            <div class="grid register-grid" style="align-items: start">
                <div>
                    <!-- Mark members (active registers only) -->
                    <section class="card card-pad mb-20">
                        <div class="between">
                            <h3 style="font-size: 16px; color: var(--ink)">Mark attendance</h3>
                            <button v-if="register.active" class="btn btn-ghost btn-sm" @click="adding = true"><Icon name="plus" /> New person</button>
                        </div>
                        <p v-if="!register.active" class="small muted mt-8">
                            Only today's registers can be marked. This register is {{ register.date > Date.now() ? 'not open yet' : 'closed' }}.
                        </p>
                        <template v-else>
                            <label class="searchbar mt-12" style="max-width: none">
                                <Icon name="search" /><input v-model="query" type="search" placeholder="Search members by name, code or phone…" />
                            </label>
                            <p v-if="error" class="small mt-8" style="color: var(--danger)">{{ error }}</p>
                            <div v-if="query && results.length" class="mt-8">
                                <div v-for="m in results" :key="m.id" class="list-row compact">
                                    <Avatar :person="m" size="sm" />
                                    <div class="grow">
                                        <div class="t">{{ m.name }} <span v-if="!m.isRegistered" class="badge badge-gold" style="margin-left: 6px">Visitor</span></div>
                                        <div class="s">{{ [m.code, m.cell].filter(Boolean).join(' · ') }}</div>
                                    </div>
                                    <button
                                        class="btn btn-sm"
                                        :class="m.marked ? 'btn-primary' : 'btn-ghost'"
                                        :disabled="busy === m.id"
                                        @click="setMarked(m, !m.marked)"
                                    >
                                        <template v-if="m.marked"><Icon name="check" /> Present</template>
                                        <template v-else>Mark present</template>
                                    </button>
                                </div>
                            </div>
                            <p v-else-if="query" class="small muted mt-8">No members match “{{ query }}”.</p>
                        </template>
                    </section>

                    <!-- Present -->
                    <section class="card">
                        <div class="card-head">
                            <h3>Present</h3>
                            <span class="badge badge-gold">{{ attendees.length }}</span>
                            <div class="spacer"></div>
                            <label v-if="attendees.length > 8" class="fp-search" style="margin: 0; width: 200px">
                                <Icon name="search" /><input v-model="filterAttendees" type="search" placeholder="Filter…" />
                            </label>
                        </div>
                        <div v-if="shownAttendees.length" style="padding: 4px 22px 8px">
                            <div v-for="a in shownAttendees" :key="a.id" class="list-row compact">
                                <Avatar :person="a" size="sm" />
                                <div class="grow">
                                    <div class="t">{{ a.name }} <span v-if="!a.isRegistered" class="badge badge-gold" style="margin-left: 6px">Visitor</span></div>
                                    <div class="s">{{ [a.cell, markedTime(a.markedAt)].filter(Boolean).join(' · ') }}</div>
                                </div>
                                <button v-if="register.active" class="btn btn-soft btn-sm" :disabled="busy === a.id" @click="setMarked(a, false)">Unmark</button>
                            </div>
                        </div>
                        <p v-else class="card-pad small muted">No one has been marked present yet.</p>
                    </section>
                </div>

                <!-- QR self check-in -->
                <aside class="card card-pad qr-card">
                    <h3 style="font-size: 16px; color: var(--ink)">Self check-in</h3>
                    <p class="small muted mt-4">Members scan this code with their phone camera to mark themselves present{{ register.active ? '' : ' (works on the day of the service)' }}.</p>
                    <div class="qr" v-html="qrSvg"></div>
                    <div class="flex gap-8 wrap" style="justify-content: center">
                        <button class="btn btn-ghost btn-sm" @click="copyLink"><Icon name="copy" /> Copy link</button>
                        <button class="btn btn-ghost btn-sm" :disabled="!!exporting" @click="exportQr('png')"><Icon name="download" /> PNG</button>
                        <button class="btn btn-ghost btn-sm" :disabled="!!exporting" @click="exportQr('pdf')"><Icon name="download" /> PDF</button>
                        <button class="btn btn-ghost btn-sm" :disabled="!!exporting" @click="exportQr('print')">Print</button>
                    </div>
                </aside>
            </div>
        </div>

        <RegisterForm v-if="editing" :register="register" :ministries="ministries" @close="editing = false" />
        <PersonForm v-if="adding" mode="add" default-type="visitor" :register-code="register.code" :cells="cells" @close="adding = false" />
        <Modal v-if="confirmingDelete" title="Delete register" @close="confirmingDelete = false">
            <p class="small">Delete <b>{{ register.name }}</b>? Its {{ register.attendeeCount }} attendance {{ register.attendeeCount === 1 ? 'record' : 'records' }} will be deleted too. This can't be undone.</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="confirmingDelete = false">Cancel</button>
                <button class="btn btn-primary btn-sm" style="background: var(--danger)" @click="destroy">Delete register</button>
            </template>
        </Modal>
    </UILayout>
</template>
