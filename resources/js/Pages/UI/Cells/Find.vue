<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import { mono, tone } from '@/Components/UI/helpers';
import { toast } from '@/Components/UI/useMemberActions';

const props = defineProps({
    cells: { type: Array, default: () => [] }, // { code, name, location, zone, type, leaders, memberCount, requested }
    q: { type: String, default: '' },
    hasMember: { type: Boolean, default: false },
});

const page = usePage();
const error = computed(() => page.props.errors?.cell);

const search = ref(props.q);
let debounce;
watch(search, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => router.get(route('ui.cells'), v ? { q: v } : {}, { preserveState: true, preserveScroll: true, replace: true, only: ['cells', 'q'] }), 300);
});

/* ---- Request to join ---- */
const asking = ref(null); // the cell being requested
const message = ref('');
const sending = ref(false);
const send = () => {
    sending.value = true;
    router.post(route('ui.cells.join', asking.value.code), { message: message.value || null }, {
        preserveScroll: true,
        preserveState: true,
        only: ['cells', 'errors'],
        onSuccess: () => {
            if (error.value) return;
            toast(`Request sent to ${asking.value.name}'s leaders`);
            asking.value = null;
            message.value = '';
        },
        onFinish: () => (sending.value = false),
    });
};
</script>

<template>
    <UILayout title="Find a cell" page="cells" :breadcrumbs="[{ label: 'Cells' }]">
        <template #feature>
            <div class="card card-pad feature mb-24 find-hero">
                <div class="grow" style="min-width: 240px">
                    <span class="eyebrow" style="color: #ffdd57">Life in community</span>
                    <h3 style="font-size: 23px; margin-top: 6px">You're not in a cell yet</h3>
                    <p class="small mt-8" style="color: rgba(255, 255, 255, 0.78); max-width: 56ch">
                        Cells are where the church becomes family — we meet in homes across the city to pray, study the Word and care for one another.
                        Find one near you and ask its leaders to add you.
                    </p>
                </div>
            </div>
        </template>

        <div class="panel mb-24">
            <div v-if="!hasMember" class="card card-pad mb-20 notice">
                <Icon name="info" />
                <div class="small">
                    Your account isn't linked to a church member profile yet, so you can't join a cell from here.
                    Please speak to the church office or a cell leader to be added.
                </div>
            </div>

            <div class="section-head">
                <h2>Find a cell</h2>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 300px"><Icon name="search" /><input v-model="search" type="search" placeholder="Search by name, area or zone…" /></label>
            </div>

            <div v-if="cells.length" class="grid cols-3">
                <article v-for="c in cells" :key="c.code" class="card card-pad cell-card">
                    <div class="flex" style="gap: 12px">
                        <span class="smon" :style="{ background: tone(c.name) }">{{ mono(c.name) }}</span>
                        <div class="grow" style="min-width: 0">
                            <h3 style="font-size: 16px; line-height: 1.2; color: var(--ink)">{{ c.name }}</h3>
                            <div class="small muted">{{ [c.type, c.zone].filter(Boolean).join(' · ') }}</div>
                        </div>
                    </div>
                    <div v-if="c.leaders.length" class="small muted flex mt-12" style="gap: 6px"><Icon name="person" /> Led by {{ c.leaders.join(', ') }}</div>
                    <div v-if="c.location" class="small muted flex mt-4" style="gap: 6px"><Icon name="map" /> {{ c.location }}</div>
                    <div class="small muted flex mt-4" style="gap: 6px"><Icon name="users" /> {{ c.memberCount }} {{ c.memberCount === 1 ? 'member' : 'members' }}</div>
                    <button v-if="c.requested" class="btn btn-soft btn-sm mt-16" style="width: 100%" disabled><Icon name="clock" /> Request sent</button>
                    <button v-else class="btn btn-ghost btn-sm mt-16" style="width: 100%" :disabled="!hasMember" @click="asking = c">Ask to join</button>
                </article>
            </div>
            <div v-else class="card card-pad empty-state">
                <Icon name="search" />
                <h3>No cells match “{{ q }}”</h3>
                <p class="small muted">Try an area or zone name.</p>
            </div>
        </div>

        <Modal v-if="asking" :title="`Ask to join ${asking.name}`" @close="asking = null">
            <p class="small muted">We'll let the cell's leaders know. They can add you to the cell from their dashboard.</p>
            <label class="field mt-12">
                <span>Message (optional)</span>
                <textarea v-model="message" rows="3" maxlength="500" placeholder="e.g. I live in Area 47 and would love to join on Wednesdays."></textarea>
            </label>
            <p v-if="error" class="small mt-8" style="color: var(--danger)">{{ error }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="asking = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="sending" @click="send">{{ sending ? 'Sending…' : 'Send request' }}</button>
            </template>
        </Modal>
    </UILayout>
</template>
