<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AuthShell from '@/Components/UI/AuthShell.vue';
import PhoneFields from '@/Components/UI/PhoneFields.vue';

const props = defineProps({
    profile: { type: Object, required: true },
});

const form = useForm({
    firstName: props.profile.firstName ?? '',
    lastName: props.profile.lastName ?? '',
    gender: '',
    dateOfBirth: '',
    phoneNumberAirtel: props.profile.phoneNumberAirtel ?? '',
    phoneNumberTnm: props.profile.phoneNumberTnm ?? '',
    phoneNumberInternational: props.profile.phoneNumberInternational ?? '',
});
const today = new Date().toISOString().slice(0, 10);

const submit = () => form.post(route('member-link.complete.store'));
</script>

<template>
    <AuthShell title="Your details" step="Almost there" heading="Tell us a little about you" :sub="`Signed in as ${profile.email}. We'll find your member profile or create one.`">
        <form class="auth-form" @submit.prevent="submit">
            <div class="row-2">
                <label class="field">
                    <span>First name</span>
                    <input v-model="form.firstName" type="text" required autocomplete="given-name" />
                    <span v-if="form.errors.firstName" class="err">{{ form.errors.firstName }}</span>
                </label>
                <label class="field">
                    <span>Last name</span>
                    <input v-model="form.lastName" type="text" required autocomplete="family-name" />
                    <span v-if="form.errors.lastName" class="err">{{ form.errors.lastName }}</span>
                </label>
            </div>

            <div class="field">
                <span>Gender</span>
                <div class="seg-choice" role="radiogroup" aria-label="Gender">
                    <button v-for="g in ['Male', 'Female']" :key="g" type="button" role="radio" :aria-checked="form.gender === g" :class="{ on: form.gender === g }" @click="form.gender = g">
                        {{ g }}
                    </button>
                </div>
                <span v-if="form.errors.gender" class="err">{{ form.errors.gender }}</span>
            </div>

            <PhoneFields :form="form" />

            <label class="field">
                <span>Date of birth <span class="hint">(optional)</span></span>
                <input v-model="form.dateOfBirth" type="date" :max="today" autocomplete="bday" />
                <span v-if="form.errors.dateOfBirth" class="err">{{ form.errors.dateOfBirth }}</span>
            </label>

            <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving…' : 'Continue' }}
            </button>
        </form>

        <template #foot>
            Not you? <a href="#" @click.prevent="router.post(route('logout'))">Sign out</a>
        </template>
    </AuthShell>
</template>
