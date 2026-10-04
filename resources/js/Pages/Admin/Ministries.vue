<script setup>
import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';

defineProps({
    ministries: { type: Array, default: () => [] },
    leaders: { type: Array, default: () => [] },
});

const page = usePage();
const blocked = computed(() => page.props.errors?.ministry);
const { busy, firstError, submit } = useSubmit();

const editing = ref(null);
const form = reactive({ name: '', leaderId: '' });
const open = (m = null) => {
    Object.assign(form, { name: m?.name ?? '', leaderId: m?.leaderId ?? '' });
    editing.value = m ?? {};
};
const save = () => {
    const data = { name: form.name, leaderId: form.leaderId || null };
    editing.value.id
        ? submit('put', route('admin.ministries.update', editing.value.id), data, { onDone: () => (editing.value = null) })
        : submit('post', route('admin.ministries.store'), data, { onDone: () => (editing.value = null) });
};
const deleting = ref(null);
const destroy = () => submit('delete', route('admin.ministries.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
</script>

<template>
    <AdminLayout title="Ministries" page="ministries" crumb="Community">
        <div class="between wrap mb-20">
            <p class="muted small">Sermons, events and service registers each belong to a ministry.</p>
            <button class="btn btn-primary" @click="open()"><Icon name="plus" /> Add ministry</button>
        </div>
        <p v-if="blocked" class="card card-pad notice mb-16"><Icon name="info" /> {{ blocked }}</p>
        <div class="grid cols-3">
            <article v-for="m in ministries" :key="m.id" class="card card-pad">
                <div class="between">
                    <h3 style="font-size: 17px">{{ m.name }}</h3>
                    <div class="flex gap-8">
                        <button class="btn btn-soft btn-sm btn-icon" aria-label="Edit" @click="open(m)"><Icon name="pencil" /></button>
                        <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = m"><Icon name="trash" /></button>
                    </div>
                </div>
                <div class="small muted mt-4">{{ m.leader ? `Led by ${m.leader}` : 'No leader set' }}</div>
                <div class="flex mt-16" style="gap: 10px">
                    <div class="mini-stat"><div class="display" style="font-size: 19px">{{ m.sermons }}</div><div class="tiny muted">Sermons</div></div>
                    <div class="mini-stat"><div class="display" style="font-size: 19px">{{ m.events }}</div><div class="tiny muted">Events</div></div>
                    <div class="mini-stat"><div class="display" style="font-size: 19px">{{ m.registers }}</div><div class="tiny muted">Registers</div></div>
                </div>
            </article>
        </div>

        <Modal v-if="editing" :title="editing.id ? 'Edit ministry' : 'Add ministry'" width="460px" @close="editing = null">
            <label class="field"><span>Name *</span><input v-model="form.name" type="text" /></label>
            <label class="field mt-12">
                <span>Leader</span>
                <select v-model="form.leaderId"><option value="">No leader</option><option v-for="l in leaders" :key="l.id" :value="l.id">{{ l.name }}</option></select>
            </label>
            <p class="tiny muted mt-8">Leaders are chosen from admins.</p>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.name" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>
        <ConfirmDialog
            v-if="deleting"
            title="Delete ministry"
            :message="deleting.sermons + deleting.events + deleting.registers ? `${deleting.name} still has sermons, events or registers, so it can't be deleted. Move them to another ministry first.` : `Delete ${deleting.name}?`"
            @confirm="destroy"
            @close="deleting = null"
        />
    </AdminLayout>
</template>
