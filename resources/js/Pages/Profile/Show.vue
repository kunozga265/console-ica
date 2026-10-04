<script setup>
import UILayout from '@/Layouts/UILayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

/* Account settings (Jetstream's /user/profile), inside the site layout. */
defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});
</script>

<template>
    <UILayout title="Account settings" page="profile" :breadcrumbs="[{ label: 'Profile', href: route('ui.profile') }, { label: 'Account settings' }]">
        <div class="panel mb-24">
            <div class="account-settings">
                <section v-if="$page.props.jetstream.canUpdateProfileInformation" class="card card-pad">
                    <UpdateProfileInformationForm :user="$page.props.auth.user" />
                </section>
                <section v-if="$page.props.jetstream.canUpdatePassword" class="card card-pad">
                    <UpdatePasswordForm />
                </section>
                <section v-if="$page.props.jetstream.canManageTwoFactorAuthentication" class="card card-pad">
                    <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />
                </section>
                <section class="card card-pad">
                    <LogoutOtherBrowserSessionsForm :sessions="sessions" />
                </section>
                <section v-if="$page.props.jetstream.hasAccountDeletionFeatures" class="card card-pad">
                    <DeleteUserForm />
                </section>
            </div>
        </div>
    </UILayout>
</template>
