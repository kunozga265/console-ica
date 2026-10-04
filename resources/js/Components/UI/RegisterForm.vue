<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/UI/Modal.vue';
import { toast } from '@/Components/UI/useMemberActions';

/* Create or edit a service register (attendance sheet). */
const props = defineProps({
    register: { type: Object, default: null }, // { code, name, ministryId, date } when editing
    ministries: { type: Array, default: () => [] },
    routePrefix: { type: String, default: 'ui.attendance' }, // 'admin.registers' in the admin console
});
const emit = defineEmits(['close']);

const page = usePage();
const pad = (n) => String(n).padStart(2, '0');
const toLocal = (ms) => {
    const d = new Date(ms);
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
const nextSunday9 = () => {
    const d = new Date();
    d.setDate(d.getDate() + ((7 - d.getDay()) % 7));
    d.setHours(9, 0, 0, 0);
    return d.getTime();
};

const form = reactive({
    name: props.register?.name ?? 'Sunday Service',
    ministryId: props.register?.ministryId ?? props.ministries[0]?.id ?? '',
    date: toLocal(props.register?.date ?? nextSunday9()),
});
const saving = ref(false);
const firstError = computed(() => Object.values(page.props.errors ?? {})[0]);

const save = () => {
    saving.value = true;
    const editing = !!props.register;
    router[editing ? 'put' : 'post'](editing ? route(`${props.routePrefix}.update`, props.register.code) : route(`${props.routePrefix}.store`), { ...form }, {
        preserveScroll: true,
        preserveState: editing,
        onSuccess: () => {
            if (Object.keys(page.props.errors ?? {}).length) return;
            toast(editing ? 'Register updated' : 'Register created');
            emit('close');
        },
        onFinish: () => (saving.value = false),
    });
};
</script>

<template>
    <Modal :title="register ? 'Edit register' : 'New register'" @close="emit('close')">
        <div class="form-grid" style="grid-template-columns: 1fr">
            <label class="field"><span>Service name</span><input v-model="form.name" type="text" placeholder="e.g. Sunday Service — First Service" /></label>
            <label class="field">
                <span>Ministry</span>
                <select v-model="form.ministryId">
                    <option v-for="m in ministries" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
            </label>
            <label class="field"><span>Date & time</span><input v-model="form.date" type="datetime-local" /></label>
        </div>
        <p class="tiny muted mt-8">Attendance can be marked (and members can scan the QR code) only on the day of the service.</p>
        <p v-if="firstError" class="small mt-8" style="color: var(--danger)">{{ firstError }}</p>
        <template #footer>
            <button class="btn btn-soft btn-sm" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary btn-sm" :disabled="saving || !form.name || !form.ministryId || !form.date" @click="save">
                {{ saving ? 'Saving…' : register ? 'Save changes' : 'Create register' }}
            </button>
        </template>
    </Modal>
</template>
