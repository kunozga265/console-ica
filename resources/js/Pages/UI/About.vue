<script setup>
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import MinisterCard from '@/Components/UI/MinisterCard.vue';

defineProps({
    stats: { type: Object, default: () => ({ sermons: 0, prayerPoints: 0, cells: 0 }) },
    pastors: { type: Array, default: () => [] },
});

const FEATURES = [
    { icon: 'mic', tone: 'blue', title: 'Sermons', text: 'Read and watch every message, highlight and bookmark lines, and keep private notes.' },
    { icon: 'pray', tone: 'plum', title: 'Prayer points', text: 'Pray along with the church each day and let others know you are praying.' },
    { icon: 'calendar', tone: 'gold', title: 'Events', text: 'See what is coming up in every ministry and tell us if you are attending.' },
    { icon: 'users', tone: 'green', title: 'Cells', text: 'Stay connected to your home cell: meetings, members, attendance and offerings.' },
    { icon: 'check', tone: 'red', title: 'Attendance', text: 'Check in to services by scanning the QR code at the door.' },
    { icon: 'gift', tone: 'brown', title: 'Giving', text: 'Give by bank transfer or mobile money with the church’s account details.' },
];
</script>

<template>
    <UILayout title="About" page="about" :breadcrumbs="[{ label: 'More' }, { label: 'About' }]">
        <template #feature>
            <div class="card card-pad feature mb-24">
                <span class="eyebrow" style="color: #ffdd57">The ICA App</span>
                <h2 style="font-size: 27px; color: #fff; margin-top: 8px; max-width: 26ch">Your church, with you all week.</h2>
                <p class="small mt-12" style="color: rgba(255, 255, 255, 0.78); max-width: 62ch">
                    The ICA App is the online church portal of International Christian Assembly. It brings the Sunday message, daily prayer,
                    church events and your home cell together in one place — on the web and on your phone.
                </p>
                <div class="about-stats">
                    <div><b>{{ stats.sermons.toLocaleString() }}</b><span>sermons</span></div>
                    <div><b>{{ stats.prayerPoints.toLocaleString() }}</b><span>prayer points</span></div>
                    <div><b>{{ stats.cells.toLocaleString() }}</b><span>cells</span></div>
                </div>
            </div>
        </template>

        <div class="panel mb-24">
            <div class="section-head"><h2>What you can do</h2></div>
            <div class="grid cols-3">
                <div v-for="f in FEATURES" :key="f.title" class="card card-pad">
                    <span class="ico" :class="`ico-${f.tone}`" style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center"><Icon :name="f.icon" /></span>
                    <h3 class="mt-12" style="font-size: 16px; color: var(--ink)">{{ f.title }}</h3>
                    <p class="small muted mt-4" style="line-height: 1.55">{{ f.text }}</p>
                </div>
            </div>
        </div>

        <div v-if="pastors.length" class="panel mb-24">
            <div class="section-head"><h2>Our pastors</h2></div>
            <div class="hrow" style="--w: 320px">
                <MinisterCard v-for="m in pastors" :key="m.id" :minister="m" />
            </div>
        </div>

        <div class="panel mb-24">
            <div class="card card-pad flex" style="gap: 16px; justify-content: space-between; flex-wrap: wrap">
                <div>
                    <h3 style="font-size: 17px; color: var(--ink)">Get the ICA App</h3>
                    <p class="small muted mt-4">Sermons, prayer, giving and events in your pocket.</p>
                </div>
                <div class="flex gap-8">
                    <a class="btn btn-primary" href="https://play.google.com/store/apps/details?id=com.icaapp.app" target="_blank" rel="noopener">Google Play</a>
                    <a class="btn btn-ghost" href="https://apps.apple.com/gb/app/ica-app-online-church-portal/id6466726690" target="_blank" rel="noopener">App Store</a>
                </div>
            </div>
        </div>
    </UILayout>
</template>
