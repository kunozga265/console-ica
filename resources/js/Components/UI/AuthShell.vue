<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import { fileUrl } from '@/Plugins/composables';
import '../../../css/ui.css';

/*
 * Frame for sign-in, sign-up and onboarding pages: brand panel + form card,
 * styled by the site's design system (.ica-ui) rather than Jetstream's Tailwind cards.
 */
defineProps({
    title: { type: String, required: true },
    heading: { type: String, required: true },
    sub: { type: String, default: '' },
    step: { type: String, default: '' }, // e.g. "Step 2 of 2"
});

const page = usePage();
const checkIn = computed(() => page.props.authIntent === 'check-in');

const theme = ref('light');
onMounted(() => {
    try {
        theme.value = localStorage.getItem('ica-theme') || 'light';
    } catch {}
});
</script>

<template>
    <Head :title="title" />
    <div class="ica-ui auth-page" :data-theme="theme">
        <aside class="auth-brand">
            <Link :href="route('ui.dashboard')" class="auth-logo">
                <img :src="fileUrl('assets/images/ica_logo.jpg')" alt="" />
                <span>ICA</span>
            </Link>
            <div class="auth-brand-copy">
                <span class="eyebrow">International Christian Assembly</span>
                <h1>Grow in the Word, together.</h1>
                <p>Sermons, prayer, your cell and church life — all in one place.</p>
            </div>
            <ul class="auth-perks">
                <li><Icon name="check" /> Check in to services in seconds</li>
                <li><Icon name="bookmark" /> Save sermons, highlights and notes</li>
                <li><Icon name="users" /> Stay close to your cell</li>
            </ul>
        </aside>

        <main class="auth-main">
            <Link :href="route('ui.dashboard')" class="auth-logo auth-logo-sm">
                <img :src="fileUrl('assets/images/ica_logo.jpg')" alt="" />
                <span>ICA</span>
            </Link>

            <div class="auth-card card">
                <div v-if="checkIn" class="auth-intent">
                    <Icon name="calendar" />
                    <span v-if="page.props.auth?.user">Finish this step and we'll take you straight back to check-in.</span>
                    <span v-else>Sign in to record your attendance — you'll go straight back to check-in.</span>
                </div>

                <span v-if="step" class="eyebrow">{{ step }}</span>
                <h2 class="auth-heading">{{ heading }}</h2>
                <p v-if="sub" class="small muted mt-4">{{ sub }}</p>

                <div class="mt-20"><slot /></div>
            </div>

            <div class="auth-foot small muted"><slot name="foot" /></div>
        </main>
    </div>
</template>
