<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthShell from '@/Components/UI/AuthShell.vue';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthShell title="Reset password" heading="Choose a new password">
        <form class="auth-form" @submit.prevent="submit">
            <label class="field">
                <span>Email</span>
                <input v-model="form.email" type="email" required autocomplete="username" />
                <span v-if="form.errors.email" class="err">{{ form.errors.email }}</span>
            </label>
            <label class="field">
                <span>New password</span>
                <input v-model="form.password" type="password" required autofocus autocomplete="new-password" />
                <span v-if="form.errors.password" class="err">{{ form.errors.password }}</span>
            </label>
            <label class="field">
                <span>Confirm password</span>
                <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
            </label>

            <button class="btn btn-primary btn-block" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving…' : 'Reset password' }}
            </button>
        </form>

        <template #foot>
            <Link :href="route('login')">Back to sign in</Link>
        </template>
    </AuthShell>
</template>
