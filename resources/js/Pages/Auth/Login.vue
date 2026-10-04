<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthShell from '@/Components/UI/AuthShell.vue';
import GoogleButton from '@/Components/UI/GoogleButton.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: true, // keep members signed in on their phones
});

const submit = () => {
    form.transform((data) => ({ ...data, remember: data.remember ? 'on' : '' })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// Errors from the Google callback arrive as page errors.
const page = usePage();
const googleError = computed(() => (!form.isDirty ? page.props.errors?.email : null));
</script>

<template>
    <AuthShell title="Sign in" heading="Welcome back" sub="Sign in to your ICA account.">
        <div v-if="status" class="auth-status">{{ status }}</div>

        <GoogleButton />

        <div class="auth-or">or with email</div>

        <form class="auth-form" @submit.prevent="submit">
            <label class="field">
                <span>Email</span>
                <input v-model="form.email" type="email" required autofocus autocomplete="username" />
                <span v-if="form.errors.email || googleError" class="err">{{ form.errors.email || googleError }}</span>
            </label>

            <label class="field">
                <span class="auth-row">
                    Password
                    <Link v-if="canResetPassword" :href="route('password.request')" class="auth-link" style="font-size: 12px">Forgot password?</Link>
                </span>
                <input v-model="form.password" type="password" required autocomplete="current-password" />
                <span v-if="form.errors.password" class="err">{{ form.errors.password }}</span>
            </label>

            <label class="auth-check">
                <input v-model="form.remember" type="checkbox" name="remember" />
                Keep me signed in
            </label>

            <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>

        <template #foot>
            New to ICA? <Link :href="route('register')">Create an account</Link>
        </template>
    </AuthShell>
</template>
