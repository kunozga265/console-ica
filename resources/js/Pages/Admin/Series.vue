<script setup>
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { tone } from '@/Components/UI/helpers';

const props = defineProps({
    series: { type: Array, default: () => [] },
    status: { type: String, default: null },
});

const { busy, firstError, submit } = useSubmit();
const filter = (status) => router.get(route('admin.series.index'), status ? { status } : {}, { preserveState: true, replace: true });

const STATUS = { ongoing: ['Ongoing', 'badge-gold'], completed: ['Completed', 'badge-green'], empty: ['No sermons yet', 'badge-outline'] };
const monthYear = (ms) => new Date(ms).toLocaleDateString('en-GB', { month: 'short', year: 'numeric' });
const range = (s) => (!s.first ? 'Not started' : monthYear(s.first) === monthYear(s.last) ? monthYear(s.first) : `${monthYear(s.first)} – ${monthYear(s.last)}`);

const editing = ref(null); // form state
const form = reactive({ title: '', description: '' });
const open = (s = null) => {
    Object.assign(form, { title: s?.title ?? '', description: s?.description ?? '' });
    editing.value = s ?? {};
};
const save = () =>
    editing.value.id
        ? submit('put', route('admin.series.update', editing.value.id), { ...form }, { onDone: () => (editing.value = null) })
        : submit('post', route('admin.series.store'), { ...form }, { onDone: () => (editing.value = null) });

const deleting = ref(null);
const destroy = () => submit('delete', route('admin.series.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Series" page="series" crumb="Content">
        <div class="between wrap mb-20" style="gap: 12px">
            <div class="chips">
                <button class="chip" :class="{ on: !status }" @click="filter(null)">All series</button>
                <button class="chip" :class="{ on: status === 'ongoing' }" @click="filter('ongoing')">Ongoing</button>
                <button class="chip" :class="{ on: status === 'completed' }" @click="filter('completed')">Completed</button>
                <button class="chip" :class="{ on: status === 'empty' }" @click="filter('empty')">Empty</button>
            </div>
            <button class="btn btn-primary" @click="open()"><Icon name="plus" /> New series</button>
        </div>

        <div v-if="series.length" class="grid cols-3">
            <article v-for="(s, i) in series" :key="s.id" class="tile reveal" :style="{ animationDelay: `${i * 0.03}s` }">
                <div class="thumb" :style="{ background: tone(s.title), aspectRatio: '16/8' }">
                    <div class="grad"></div>
                    <span class="tag badge" :class="STATUS[s.status][1]">{{ STATUS[s.status][0] }}</span>
                    <div class="thumb-title">
                        <div class="eyebrow" style="color: rgba(255, 255, 255, 0.75)">{{ range(s) }}</div>
                        <div class="display" style="font-size: 21px; color: #fff; margin-top: 2px">{{ s.title }}</div>
                    </div>
                </div>
                <div class="body">
                    <div class="between mb-12">
                        <div class="meta"><Icon name="person" style="color: var(--muted)" /><span class="small muted">{{ s.lead || 'No minister yet' }}</span></div>
                        <span class="badge badge-outline">{{ s.count }} {{ s.count === 1 ? 'message' : 'messages' }}</span>
                    </div>
                    <p v-if="s.description" class="small muted clamp-2 mb-12">{{ s.description }}</p>
                    <div class="tile-actions">
                        <Link class="btn btn-soft btn-sm" :href="route('admin.sermons.index', { series: s.id })">Sermons →</Link>
                        <button class="btn btn-soft btn-sm" @click="open(s)"><Icon name="pencil" /> Edit</button>
                        <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = s"><Icon name="trash" /></button>
                    </div>
                </div>
            </article>
        </div>
        <div v-else class="card card-pad empty-state"><Icon name="layers" /><h3>No series here</h3></div>

        <Modal v-if="editing" :title="editing.id ? 'Edit series' : 'New series'" @close="editing = null">
            <label class="field"><span>Title *</span><input v-model="form.title" type="text" /></label>
            <label class="field mt-12"><span>Description</span><textarea v-model="form.description" rows="4"></textarea></label>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.title" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>

        <ConfirmDialog
            v-if="deleting"
            title="Delete series"
            :message="`Delete “${deleting.title}”? Its ${deleting.count} sermons stay published but will no longer be grouped in a series.`"
            @confirm="destroy"
            @close="deleting = null"
        />
    </AdminLayout>
</template>
