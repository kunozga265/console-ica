<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthShell from '@/Components/UI/AuthShell.vue';
import GoogleButton from '@/Components/UI/GoogleButton.vue';
import PhoneFields from '@/Components/UI/PhoneFields.vue';

const form = useForm({
    firstName: '',
    lastName: '',
    email: '',
    gender: '',
    dateOfBirth: '',
    phoneNumberAirtel: '',
    phoneNumberTnm: '',
    phoneNumberInternational: '',
    password: '',
    password_confirmation: '',
    terms: false,
    remember: true,
});

const page = usePage();
const needsTerms = computed(() => page.props.jetstream?.hasTermsAndPrivacyPolicyFeature);
const today = new Date().toISOString().slice(0, 10);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthShell title="Create account" heading="Create your account" sub="We'll link it to your church member profile.">
        <GoogleButton label="Sign up with Google" />

        <div class="auth-or">or with email</div>

        <form class="auth-form" @submit.prevent="submit">
            <div class="row-2">
                <label class="field">
                    <span>First name</span>
                    <input v-model="form.firstName" type="text" required autofocus autocomplete="given-name" />
                    <span v-if="form.errors.firstName" class="err">{{ form.errors.firstName }}</span>
                </label>
                <label class="field">
                    <span>Last name</span>
                    <input v-model="form.lastName" type="text" required autocomplete="family-name" />
                    <span v-if="form.errors.lastName" class="err">{{ form.errors.lastName }}</span>
                </label>
            </div>

            <label class="field">
                <span>Email</span>
                <input v-model="form.email" type="email" required autocomplete="username" />
                <span v-if="form.errors.email" class="err">{{ form.errors.email }}</span>
            </label>

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

            <div class="row-2">
                <label class="field">
                    <span>Password</span>
                    <input v-model="form.password" type="password" required autocomplete="new-password" />
                </label>
                <label class="field">
                    <span>Confirm password</span>
                    <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
                </label>
            </div>
            <span v-if="form.errors.password" class="err" style="margin-top: -6px">{{ form.errors.password }}</span>

            <label v-if="needsTerms" class="auth-check">
                <input v-model="form.terms" type="checkbox" required />
                <span>I agree to the <a class="auth-link" target="_blank" :href="route('terms.show')">Terms</a> and <a class="auth-link" target="_blank" :href="route('policy.show')">Privacy Policy</a></span>
            </label>
            <span v-if="form.errors.terms" class="err">{{ form.errors.terms }}</span>

            <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Creating account…' : 'Create account' }}
            </button>
        </form>

        <template #foot>
            Already have an account? <Link :href="route('login')">Sign in</Link>
        </template>
    </AuthShell>
</template>
