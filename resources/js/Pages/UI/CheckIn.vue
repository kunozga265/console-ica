<script setup>
import { onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import { fmtDate, fmtDay } from '@/Components/UI/helpers';

/*
 * Landing page for a register's QR code. When the register is open and the
 * member isn't checked in yet, it records attendance straight away (with the
 * phone's location if the member allows it).
 */
const props = defineProps({
    register: { type: Object, required: true }, // { code, name, ministry, date }
    status: { type: String, required: true }, // 'ready' | 'checked-in' | 'inactive' | 'no-member'
});

const working = ref(false);

const submit = (coords = {}) =>
    router.post(route('ui.check-in.store', props.register.code), coords, {
        preserveScroll: true,
        onFinish: () => (working.value = false),
    });

onMounted(() => {
    if (props.status !== 'ready') return;
    working.value = true;
    if (!navigator.geolocation) return submit();
    navigator.geolocation.getCurrentPosition(
        (p) => submit({ latitude: p.coords.latitude, longitude: p.coords.longitude }),
        () => submit(),
        { timeout: 5000, maximumAge: 60000 },
    );
});
</script>

<template>
    <UILayout title="Check in" page="dashboard" :breadcrumbs="[{ label: 'Check in' }]">
        <div class="panel mb-24">
            <div class="checkin card card-pad">
                <div class="checkin-icon" :class="status === 'checked-in' ? 'ok' : status === 'ready' ? 'wait' : 'warn'">
                    <Icon :name="status === 'checked-in' ? 'check' : status === 'ready' ? 'clock' : 'info'" />
                </div>
                <template v-if="status === 'checked-in'">
                    <h2>You're checked in!</h2>
                    <p class="muted">Welcome to <b>{{ register.name }}</b>. Your attendance has been recorded.</p>
                </template>
                <template v-else-if="status === 'ready'">
                    <h2>Checking you in…</h2>
                    <p class="muted">Recording your attendance for <b>{{ register.name }}</b>.</p>
                </template>
                <template v-else-if="status === 'inactive'">
                    <h2>This register isn't open</h2>
                    <p class="muted"><b>{{ register.name }}</b> is for {{ fmtDay(register.date) }} {{ fmtDate(register.date) }}. Check-in only works on the day of the service.</p>
                </template>
                <template v-else>
                    <h2>We couldn't find your member profile</h2>
                    <p class="muted">Your account isn't linked to a church member yet, so attendance can't be recorded. Please speak to an usher.</p>
                </template>
                <div class="small muted mt-8">{{ register.ministry }} · {{ fmtDay(register.date) }} {{ fmtDate(register.date) }}</div>
                <Link :href="route('ui.dashboard')" class="btn btn-ghost mt-20">Go to dashboard</Link>
            </div>
        </div>
    </UILayout>
</template>
