<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Modal from '@/Components/UI/Modal.vue';
import RichEditor from '@/Components/UI/RichEditor.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate, tone } from '@/Components/UI/helpers';
import { fileUrl } from '@/Plugins/composables';

const props = defineProps({
    events: { type: Array, default: () => [] },
    when: { type: String, default: 'upcoming' },
    ministries: { type: Array, default: () => [] },
});

const { busy, firstError, submit } = useSubmit();
const show = (when) => router.get(route('admin.events.index'), when === 'upcoming' ? {} : { when }, { preserveState: true, replace: true });

const day = (ms) => ({ d: new Date(ms).getDate(), m: new Date(ms).toLocaleString('en', { month: 'short' }).toUpperCase(), wd: new Date(ms).toLocaleString('en', { weekday: 'long' }) });
const range = (e) => (new Date(e.start).toDateString() === new Date(e.end).toDateString() ? fmtDate(e.start) : `${fmtDate(e.start)} – ${fmtDate(e.end)}`);

const editing = ref(null);
const preview = ref(null);
const form = reactive({ title: '', venue: '', time: '', ministryId: '', startDate: '', endDate: '', body: '', image: null });
const open = (e = null) => {
    const today = new Date().toISOString().slice(0, 10);
    Object.assign(form, {
        title: e?.title ?? '', venue: e?.venue ?? '', time: e?.time ?? '', ministryId: e?.ministryId ?? props.ministries[0]?.id ?? '',
        startDate: e?.startDate ?? today, endDate: e?.endDate ?? '', body: e?.body ?? '', image: null,
    });
    preview.value = e?.image ? fileUrl(e.image) : null;
    editing.value = e ?? {};
};
const pick = (ev) => {
    form.image = ev.target.files[0] ?? null;
    if (form.image) preview.value = URL.createObjectURL(form.image);
};
const save = () => {
    const data = { ...form, endDate: form.endDate || null };
    if (!data.image) delete data.image;
    const opts = { onDone: () => (editing.value = null), forceFormData: true };
    editing.value.id ? submit('post', route('admin.events.update', editing.value.id), data, opts) : submit('post', route('admin.events.store'), data, opts);
};

const deleting = ref(null);
const destroy = () => submit('delete', route('admin.events.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Events" page="events" crumb="Community">
        <div class="between wrap mb-20" style="gap: 12px">
            <div class="segmented">
                <button :class="{ on: when === 'upcoming' }" @click="show('upcoming')">Upcoming</button>
                <button :class="{ on: when === 'past' }" @click="show('past')">Past</button>
                <button :class="{ on: when === 'all' }" @click="show('all')">All</button>
            </div>
            <div class="flex gap-8">
                <a class="btn btn-ghost btn-sm" :href="route('ui.events')" target="_blank"><Icon name="calendar" /> View on site</a>
                <button class="btn btn-primary" @click="open()"><Icon name="plus" /> Create event</button>
            </div>
        </div>

        <div v-if="events.length" class="grid" style="gap: 16px">
            <article v-for="(e, i) in events" :key="e.id" class="card reveal event-admin" :style="{ animationDelay: `${i * 0.03}s` }">
                <div class="event-admin-art hide-sm" :style="e.image ? null : { background: tone(e.title) }">
                    <img v-if="e.image" :src="fileUrl(e.image)" alt="" />
                    <div v-else style="text-align: center"><div class="display" style="font-size: 40px; color: #fff; line-height: 1">{{ day(e.start).d }}</div><div class="eyebrow" style="color: rgba(255, 255, 255, 0.8)">{{ day(e.start).m }}</div></div>
                </div>
                <div class="card-pad grow event-admin-body">
                    <div class="grow" style="min-width: 220px">
                        <div class="flex gap-8 mb-8"><span v-if="e.ministry" class="badge badge-blue">{{ e.ministry }}</span><span class="tiny muted">{{ day(e.start).wd }}</span></div>
                        <h3 style="font-size: 19px">{{ e.title }}</h3>
                        <div class="flex mt-8" style="gap: 16px; flex-wrap: wrap">
                            <span v-if="e.venue" class="small muted flex" style="gap: 6px"><Icon name="map" />{{ e.venue }}</span>
                            <span class="small muted flex" style="gap: 6px"><Icon name="clock" />{{ range(e) }}<template v-if="e.time"> · {{ e.time }}</template></span>
                        </div>
                    </div>
                    <div style="min-width: 200px">
                        <div class="between tiny muted mb-8"><span>{{ e.attending }} attending</span><span>{{ e.notAttending }} not attending</span></div>
                        <div class="bar mb-12"><i :style="{ width: `${e.attending + e.notAttending ? (e.attending / (e.attending + e.notAttending)) * 100 : 0}%` }"></i></div>
                        <div class="between">
                            <div class="avatar-stack">
                                <Avatar v-for="a in e.attendees" :key="a.id" :person="a" size="sm" />
                                <span v-if="e.attending > e.attendees.length" class="more">+{{ e.attending - e.attendees.length }}</span>
                            </div>
                            <div class="flex gap-8">
                                <button class="btn btn-soft btn-sm" @click="open(e)"><Icon name="pencil" /> Edit</button>
                                <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = e"><Icon name="trash" /></button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>
        <div v-else class="card card-pad empty-state"><Icon name="calendar" /><h3>No {{ when === 'all' ? '' : when }} events</h3></div>

        <Modal v-if="editing" :title="editing.id ? 'Edit event' : 'Create event'" width="680px" @close="editing = null">
            <div class="image-pick mb-16" :style="preview ? null : { background: tone(form.title || 'Event') }">
                <img v-if="preview" :src="preview" alt="" />
                <label class="btn btn-soft btn-sm" style="cursor: pointer">
                    {{ preview ? 'Change image' : 'Add image' }}
                    <input type="file" accept="image/*" hidden @change="pick" />
                </label>
            </div>
            <label class="field"><span>Title *</span><input v-model="form.title" type="text" /></label>
            <div class="form-grid mt-12">
                <label class="field"><span>Ministry *</span><select v-model="form.ministryId"><option v-for="m in ministries" :key="m.id" :value="m.id">{{ m.name }}</option></select></label>
                <label class="field"><span>Venue</span><input v-model="form.venue" type="text" placeholder="e.g. Main Auditorium" /></label>
                <label class="field"><span>Starts *</span><input v-model="form.startDate" type="date" /></label>
                <label class="field"><span>Ends</span><input v-model="form.endDate" type="date" :min="form.startDate" /></label>
                <label class="field" style="grid-column: 1 / -1"><span>Time</span><input v-model="form.time" type="text" placeholder="e.g. 9:00 AM – 4:00 PM" /></label>
            </div>
            <div class="field mt-12"><span>Details</span><RichEditor v-model="form.body" placeholder="What's happening, who it's for, what to bring…" /></div>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.title || !form.startDate" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>
        <ConfirmDialog v-if="deleting" title="Delete event" :message="`Delete “${deleting.title}” and its ${deleting.attending + deleting.notAttending} responses?`" @confirm="destroy" @close="deleting = null" />
    </AdminLayout>
</template>
