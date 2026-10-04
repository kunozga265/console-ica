<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthShell from '@/Components/UI/AuthShell.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => form.post(route('password.email'));
</script>

<template>
    <AuthShell title="Forgot password" heading="Reset your password" sub="Enter your email and we'll send you a link to choose a new password. Signed up with Google? This sets a password too.">
        <div v-if="status" class="auth-status">{{ status }}</div>

        <form class="auth-form" @submit.prevent="submit">
            <label class="field">
                <span>Email</span>
                <input v-model="form.email" type="email" required autofocus autocomplete="username" />
                <span v-if="form.errors.email" class="err">{{ form.errors.email }}</span>
            </label>

            <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Sending…' : 'Email reset link' }}
            </button>
        </form>

        <template #foot>
            Remembered it? <Link :href="route('login')">Back to sign in</Link>
        </template>
    </AuthShell>
</template>
