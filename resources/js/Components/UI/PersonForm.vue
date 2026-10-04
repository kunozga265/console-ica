<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/UI/Modal.vue';
import { toast } from '@/Components/UI/useMemberActions';

/*
 * Add / edit a person, or register a visitor as a full member.
 *   mode 'add'      → choose Member or Visitor; optional registerCode marks them present
 *   mode 'edit'     → update details
 *   mode 'register' → complete a visitor's details and make them a full member
 */
const props = defineProps({
    mode: { type: String, default: 'add' },
    person: { type: Object, default: null }, // existing person for edit/register
    cells: { type: Array, default: () => [] },
    registerCode: { type: String, default: null }, // add + mark present on this register
    defaultType: { type: String, default: 'visitor' }, // 'member' | 'visitor' (add mode)
    only: { type: Array, default: () => [] }, // props to reload afterwards (empty = all)
});
const emit = defineEmits(['close', 'saved']);

const page = usePage();
const errors = computed(() => page.props.errors ?? {});

const p = props.person ?? {};
const form = reactive({
    isRegistered: props.mode === 'register' ? true : props.mode === 'edit' ? p.isRegistered : props.defaultType === 'member',
    firstName: p.firstName ?? '',
    middleName: p.middleName ?? '',
    lastName: p.lastName ?? '',
    gender: p.gender ?? '',
    phoneNumberAirtel: p.phoneNumberAirtel ?? '',
    phoneNumberTnm: p.phoneNumberTnm ?? '',
    phoneNumberInternational: p.phoneNumberInternational ?? '',
    email: p.email ?? '',
    dateOfBirth: p.dateOfBirth ?? '',
    cellId: p.cellId ?? '',
});
const full = computed(() => form.isRegistered);

const title = computed(() =>
    props.mode === 'register' ? `Register ${p.name} as a member` : props.mode === 'edit' ? `Edit ${p.name}` : form.isRegistered ? 'Add member' : 'Add visitor',
);

const saving = ref(false);
const save = () => {
    const payload = { ...form, cellId: form.cellId || null, dateOfBirth: form.dateOfBirth || null };
    if (props.registerCode) payload.registerCode = props.registerCode;

    const [method, url] =
        props.mode === 'register' ? ['post', route('ui.members.register', p.code)]
        : props.mode === 'edit' ? ['put', route('ui.members.update', p.code)]
        : ['post', route('ui.members.store')];

    saving.value = true;
    router[method](url, payload, {
        preserveScroll: true,
        preserveState: true,
        ...(props.only.length ? { only: props.only } : {}),
        onSuccess: () => {
            if (Object.keys(page.props.errors ?? {}).length) return;
            const name = `${form.firstName} ${form.lastName}`;
            toast(
                props.mode === 'register' ? `${name} is now a full member`
                : props.mode === 'edit' ? 'Details saved'
                : `${name} added as a ${form.isRegistered ? 'member' : 'visitor'}${props.registerCode ? ' and marked present' : ''}`,
            );
            emit('saved');
            emit('close');
        },
        onFinish: () => (saving.value = false),
    });
};
const firstError = computed(() => Object.values(errors.value)[0]);
</script>

<template>
    <Modal :title="title" width="600px" @close="emit('close')">
        <div v-if="mode === 'add'" class="segmented mb-16">
            <button :class="{ on: !form.isRegistered }" @click="form.isRegistered = false">Visitor</button>
            <button :class="{ on: form.isRegistered }" @click="form.isRegistered = true">Full member</button>
        </div>
        <p v-if="mode === 'add' && !form.isRegistered" class="small muted mb-12">
            Visitors only need a name and gender. You can register them as full members later.
        </p>

        <div class="form-grid">
            <label class="field"><span>First name *</span><input v-model="form.firstName" type="text" autocomplete="given-name" /></label>
            <label class="field"><span>Last name *</span><input v-model="form.lastName" type="text" autocomplete="family-name" /></label>
            <label v-if="full" class="field"><span>Middle name</span><input v-model="form.middleName" type="text" /></label>
            <label class="field">
                <span>Gender *</span>
                <select v-model="form.gender">
                    <option value="" disabled>Choose…</option>
                    <option>Male</option>
                    <option>Female</option>
                </select>
            </label>
            <label class="field"><span>Airtel number{{ full ? ' *' : '' }}</span><input v-model="form.phoneNumberAirtel" type="tel" placeholder="099…" /></label>
            <label v-if="full" class="field"><span>TNM number</span><input v-model="form.phoneNumberTnm" type="tel" placeholder="088…" /></label>
            <label v-if="full" class="field"><span>International number</span><input v-model="form.phoneNumberInternational" type="tel" placeholder="+44…" /></label>
            <label class="field"><span>Email</span><input v-model="form.email" type="email" autocomplete="email" /></label>
            <template v-if="full">
                <label class="field"><span>Date of birth</span><input v-model="form.dateOfBirth" type="date" /></label>
                <label class="field">
                    <span>Cell</span>
                    <select v-model="form.cellId">
                        <option value="">No cell</option>
                        <option v-for="c in cells" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </label>
            </template>
        </div>
        <p v-if="full" class="tiny muted mt-8">* Full members need at least one phone number or an email.</p>
        <p v-if="firstError" class="small mt-8" style="color: var(--danger)">{{ firstError }}</p>

        <template #footer>
            <button class="btn btn-soft btn-sm" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary btn-sm" :disabled="saving || !form.firstName || !form.lastName || !form.gender" @click="save">
                {{ saving ? 'Saving…' : mode === 'register' ? 'Register as member' : registerCode ? 'Add & mark present' : 'Save' }}
            </button>
        </template>
    </Modal>
</template>
