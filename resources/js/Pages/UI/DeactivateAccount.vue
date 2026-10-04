<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';

/*
 * Account deletion (app-store requirement):
 *   request → enter your email
 *   sent    → check your inbox for the confirmation link
 *   confirm → (from the emailed link) final "delete my account"
 *   done    → deleted
 */
const props = defineProps({
    stage: { type: String, default: 'request' },
    email: { type: String, default: '' },
    name: { type: String, default: '' },
    minutes: { type: Number, default: 60 },
    deleteUrl: { type: String, default: '' },
});

const page = usePage();
const error = computed(() => page.props.errors?.email);
const email = ref(page.props.auth?.user?.email ?? '');
const busy = ref(false);
const understood = ref(false);

const submit = () => {
    busy.value = true;
    router.post(route('ui.deactivate.request'), { email: email.value }, { onFinish: () => (busy.value = false) });
};
const destroy = () => {
    busy.value = true;
    router.post(props.deleteUrl, {}, { onFinish: () => (busy.value = false) });
};

const DELETED = ['Your account and sign-in', 'Notes, highlights and bookmarks', 'Saved and favourite sermons', 'Prayer and event responses', 'Notifications and app activity'];
</script>

<template>
    <UILayout title="Delete account" page="profile" :breadcrumbs="[{ label: 'Delete account' }]">
        <div class="panel mb-24">
            <div class="deactivate card card-pad">
                <template v-if="stage === 'request'">
                    <span class="ico ico-red deact-ico"><Icon name="trash" /></span>
                    <h2>Delete your account</h2>
                    <p class="muted">
                        Enter the email address you use with the ICA App. We'll email you a link to confirm, and once you confirm, your account and its data are
                        permanently deleted.
                    </p>
                    <form class="mt-16" @submit.prevent="submit">
                        <label class="field">
                            <span>Email address</span>
                            <input v-model="email" type="email" required autocomplete="email" placeholder="you@example.com" />
                        </label>
                        <p v-if="error" class="small mt-8" style="color: var(--danger)">{{ error }}</p>
                        <button class="btn btn-primary mt-16 danger" style="width: 100%" :disabled="busy || !email">{{ busy ? 'Sending…' : 'Send confirmation link' }}</button>
                    </form>
                    <div class="deact-list mt-20">
                        <div class="tiny muted caps mb-8">What gets deleted</div>
                        <div v-for="d in DELETED" :key="d" class="small flex" style="gap: 8px"><Icon name="close" /> {{ d }}</div>
                        <p class="tiny muted mt-12">Your church membership record (kept by the church office for attendance and cells) is not part of your app account. Contact the church office to have it removed too.</p>
                    </div>
                </template>

                <template v-else-if="stage === 'sent'">
                    <span class="ico ico-blue deact-ico"><Icon name="mail" /></span>
                    <h2>Check your email</h2>
                    <p class="muted">
                        If an ICA App account uses <b>{{ email }}</b>, we've sent it a link to confirm the deletion. The link expires in {{ minutes }} minutes.
                    </p>
                    <p class="small muted mt-12">Didn't get it? Check your spam folder, or <Link :href="route('ui.deactivate')" class="link">try again</Link>.</p>
                </template>

                <template v-else-if="stage === 'confirm'">
                    <span class="ico ico-red deact-ico"><Icon name="trash" /></span>
                    <h2>Delete {{ name || 'your account' }}?</h2>
                    <p class="muted">This permanently deletes the ICA App account for <b>{{ email }}</b>. It can't be undone.</p>
                    <div class="deact-list mt-16">
                        <div v-for="d in DELETED" :key="d" class="small flex" style="gap: 8px"><Icon name="close" /> {{ d }}</div>
                    </div>
                    <label class="flex mt-16 small" style="gap: 9px; cursor: pointer">
                        <input v-model="understood" type="checkbox" class="rowcheck" /> I understand this can't be undone
                    </label>
                    <button class="btn btn-primary mt-16 danger" style="width: 100%" :disabled="busy || !understood" @click="destroy">
                        {{ busy ? 'Deleting…' : 'Delete my account permanently' }}
                    </button>
                    <Link :href="route('ui.dashboard')" class="btn btn-ghost mt-8" style="width: 100%">Keep my account</Link>
                </template>

                <template v-else>
                    <span class="ico ico-green deact-ico"><Icon name="check" /></span>
                    <h2>Your account has been deleted</h2>
                    <p class="muted">Your ICA App account and its data have been removed. Thank you for being part of the ICA family.</p>
                    <Link :href="route('ui.dashboard')" class="btn btn-ghost mt-16">Go to the home page</Link>
                </template>
            </div>
        </div>
    </UILayout>
</template>
