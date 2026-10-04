<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Modal from '@/Components/UI/Modal.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { authorName } from '@/Components/UI/helpers';
import { fileUrl } from '@/Plugins/composables';

const props = defineProps({
    ministers: { type: Array, default: () => [] },
    type: { type: String, default: null },
});

const { busy, firstError, submit } = useSubmit();
const filter = (type) => router.get(route('admin.ministers.index'), type ? { type } : {}, { preserveState: true, replace: true });

const editing = ref(null);
const form = reactive({ name: '', suffix: '', title: '', biography: '', icaPastor: false, avatar: null });
const preview = ref(null);
const open = (m = null) => {
    Object.assign(form, { name: m?.name ?? '', suffix: m?.suffix ?? '', title: m?.title ?? '', biography: m?.biography ?? '', icaPastor: m?.icaPastor ?? false, avatar: null });
    preview.value = m?.avatar ? fileUrl(m.avatar) : null;
    editing.value = m ?? {};
};
const pick = (e) => {
    form.avatar = e.target.files[0] ?? null;
    if (form.avatar) preview.value = URL.createObjectURL(form.avatar);
};
const save = () => {
    const data = { ...form, icaPastor: form.icaPastor ? 1 : 0 };
    if (!data.avatar) delete data.avatar;
    const done = { onDone: () => (editing.value = null), forceFormData: true };
    editing.value.id ? submit('post', route('admin.ministers.update', editing.value.id), data, done) : submit('post', route('admin.ministers.store'), data, done);
};

const deleting = ref(null);
const destroy = () => submit('delete', route('admin.ministers.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Ministers" page="ministers" crumb="Content">
        <div class="between wrap mb-20" style="gap: 12px">
            <div class="chips">
                <button class="chip" :class="{ on: !type }" @click="filter(null)">All ministers</button>
                <button class="chip" :class="{ on: type === 'pastors' }" @click="filter('pastors')">ICA pastors</button>
                <button class="chip" :class="{ on: type === 'guests' }" @click="filter('guests')">Guests &amp; members</button>
            </div>
            <button class="btn btn-primary" @click="open()"><Icon name="plus" /> Add minister</button>
        </div>

        <div v-if="ministers.length" class="grid cols-3">
            <article v-for="(m, i) in ministers" :key="m.id" class="card card-pad reveal" :style="{ animationDelay: `${i * 0.02}s` }">
                <div class="flex" style="gap: 14px">
                    <Avatar :person="m" size="lg" />
                    <div class="grow" style="min-width: 0">
                        <div class="between">
                            <h3 style="font-size: 17px; line-height: 1.2">{{ authorName(m) }}</h3>
                            <button class="btn btn-soft btn-sm btn-icon" aria-label="Edit" @click="open(m)"><Icon name="pencil" /></button>
                        </div>
                        <div class="small muted">{{ m.title }}</div>
                        <span class="badge mt-8" :class="m.icaPastor ? 'badge-gold' : 'badge-outline'">{{ m.icaPastor ? 'ICA pastor' : 'Guest / member' }}</span>
                    </div>
                </div>
                <div class="flex mt-16" style="gap: 10px">
                    <div class="mini-stat"><div class="display" style="font-size: 20px">{{ m.sermons }}</div><div class="tiny muted">Sermons</div></div>
                    <div class="mini-stat"><div class="display" style="font-size: 20px">{{ m.series }}</div><div class="tiny muted">Series</div></div>
                </div>
                <hr class="divider" style="margin: 16px 0" />
                <div class="tiny muted caps">Latest message</div>
                <div class="flex mt-8" style="gap: 10px">
                    <Icon name="mic" style="color: var(--accent-2)" />
                    <Link v-if="m.latest" class="small clip" style="font-weight: 600" :href="route('admin.sermons.edit', m.latest.id)">{{ m.latest.title }}</Link>
                    <span v-else class="small muted">None yet</span>
                </div>
                <div class="between mt-16">
                    <Link class="small link" :href="route('admin.sermons.index', { author: m.id })">All sermons →</Link>
                    <button class="btn btn-soft btn-sm btn-icon" aria-label="Remove" @click="deleting = m"><Icon name="trash" /></button>
                </div>
            </article>
        </div>
        <div v-else class="card card-pad empty-state"><Icon name="person" /><h3>No ministers here</h3></div>

        <Modal v-if="editing" :title="editing.id ? 'Edit minister' : 'Add minister'" width="560px" @close="editing = null">
            <div class="flex mb-16" style="gap: 14px">
                <img v-if="preview" :src="preview" class="avatar avatar-lg" alt="" />
                <span v-else class="avatar avatar-lg a-1">{{ (form.name || '?')[0] }}</span>
                <label class="btn btn-soft btn-sm" style="cursor: pointer">
                    <Icon name="download" style="transform: rotate(180deg)" /> {{ preview ? 'Change photo' : 'Upload photo' }}
                    <input type="file" accept="image/*" hidden @change="pick" />
                </label>
            </div>
            <div class="form-grid">
                <label class="field"><span>Honorific</span><input v-model="form.suffix" type="text" placeholder="e.g. Rev. Dr." /></label>
                <label class="field"><span>Name *</span><input v-model="form.name" type="text" /></label>
                <label class="field" style="grid-column: 1 / -1"><span>Title *</span><input v-model="form.title" type="text" placeholder="e.g. Senior Pastor" /></label>
            </div>
            <label class="field mt-12"><span>Biography</span><textarea v-model="form.biography" rows="4"></textarea></label>
            <label class="flex mt-12 small" style="gap: 9px; cursor: pointer"><input v-model="form.icaPastor" type="checkbox" class="rowcheck" /> ICA pastor (shown on the About page)</label>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.name || !form.title" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>

        <p v-if="$page.props.errors?.minister" class="card card-pad notice mt-16"><Icon name="info" /> {{ $page.props.errors.minister }}</p>
        <ConfirmDialog
            v-if="deleting"
            title="Remove minister"
            :message="deleting.sermons ? `${authorName(deleting)} has ${deleting.sermons} sermons, so they can't be removed. Move the sermons to another minister first.` : `Remove ${authorName(deleting)}?`"
            confirm-label="Remove"
            @confirm="destroy"
            @close="deleting = null"
        />
    </AdminLayout>
</template>
