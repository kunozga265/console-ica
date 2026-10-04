<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthShell from '@/Components/UI/AuthShell.vue';
import PhoneFields from '@/Components/UI/PhoneFields.vue';
import Icon from '@/Components/UI/Icon.vue';
import { fileUrl } from '@/Plugins/composables';

const props = defineProps({
    candidate: { type: Object, required: true }, // { name, avatar, cell, phone, email } — phone/email partly hidden
    details: { type: Object, default: () => ({}) }, // already-entered gender / phones / dateOfBirth
});

const notMe = ref(false);

const linkForm = useForm({});
const linkExisting = () => linkForm.post(route('member-link.link-existing'));

const newForm = useForm({
    gender: props.details.gender ?? '',
    dateOfBirth: props.details.dateOfBirth ?? '',
    phoneNumberAirtel: props.details.phoneNumberAirtel ?? '',
    phoneNumberTnm: props.details.phoneNumberTnm ?? '',
    phoneNumberInternational: props.details.phoneNumberInternational ?? '',
});
const createNew = () => newForm.post(route('member-link.create-new'), { preserveState: true });
const today = new Date().toISOString().slice(0, 10);
</script>

<template>
    <AuthShell
        title="Confirm your profile"
        step="Almost there"
        :heading="notMe ? 'Create your member profile' : 'Is this you?'"
        :sub="notMe ? 'We\'ll set up a new profile for you.' : 'We found a church member profile that matches your details.'"
    >
        <template v-if="!notMe">
            <div class="match-card">
                <img :src="fileUrl(candidate.avatar || 'images/avatar.png')" alt="" />
                <div style="min-width: 0">
                    <b>{{ candidate.name }}</b>
                    <div v-if="candidate.cell" class="small muted flex" style="gap: 6px"><Icon name="users" /> {{ candidate.cell }}</div>
                    <div v-if="candidate.phone" class="small muted flex" style="gap: 6px"><Icon name="phone" /> {{ candidate.phone }}</div>
                    <div v-if="candidate.email" class="small muted flex" style="gap: 6px"><Icon name="mail" /> {{ candidate.email }}</div>
                </div>
            </div>

            <div class="auth-form mt-20">
                <button class="btn btn-primary btn-block" :disabled="linkForm.processing" @click="linkExisting">
                    <Icon name="check" /> Yes, this is me
                </button>
                <button class="btn btn-ghost btn-block" @click="notMe = true">No, that's not me</button>
            </div>
        </template>

        <form v-else class="auth-form" @submit.prevent="createNew">
            <div class="field">
                <span>Gender</span>
                <div class="seg-choice" role="radiogroup" aria-label="Gender">
                    <button v-for="g in ['Male', 'Female']" :key="g" type="button" role="radio" :aria-checked="newForm.gender === g" :class="{ on: newForm.gender === g }" @click="newForm.gender = g">
                        {{ g }}
                    </button>
                </div>
                <span v-if="newForm.errors.gender" class="err">{{ newForm.errors.gender }}</span>
            </div>

            <PhoneFields :form="newForm" />

            <label class="field">
                <span>Date of birth <span class="hint">(optional)</span></span>
                <input v-model="newForm.dateOfBirth" type="date" :max="today" autocomplete="bday" />
                <span v-if="newForm.errors.dateOfBirth" class="err">{{ newForm.errors.dateOfBirth }}</span>
            </label>

            <button class="btn btn-primary btn-block" type="submit" :disabled="newForm.processing">
                {{ newForm.processing ? 'Creating…' : 'Create my profile' }}
            </button>
            <button class="btn btn-soft btn-block" type="button" @click="notMe = false">Back</button>
        </form>

        <template #foot>
            Not you? <a href="#" @click.prevent="router.post(route('logout'))">Sign out</a>
        </template>
    </AuthShell>
</template>
