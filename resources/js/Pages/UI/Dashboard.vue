<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import FeaturedSermon from '@/Components/UI/FeaturedSermon.vue';
import SermonMiniCard from '@/Components/UI/SermonMiniCard.vue';
import { dayNum, fmtDate, fmtDay, fmtLongDay, monName } from '@/Components/UI/helpers';
import * as SAMPLE from '@/Components/UI/sampleData';

const props = defineProps({
    sermons: { type: Array, default: () => SAMPLE.SERMONS },
    prayerPoints: { type: Array, default: () => SAMPLE.PRAYER_POINTS },
    events: { type: Array, default: () => SAMPLE.EVENTS },
    announcements: { type: Object, default: null }, // { body } rich text when the console's announcements page is active
    birthdays: { type: Array, default: () => SAMPLE.BIRTHDAYS },
});

const latest = computed(() => props.sermons[0] ?? null);
const others = computed(() => props.sermons.slice(1));
const inertia = usePage();
const user = computed(() => inertia.props.auth?.user ?? null);

// Salutation, as in the console's AppLayout header.
const greeting = ref('');
const setGreeting = () => {
    const hour = new Date().getHours();
    greeting.value = hour < 12 ? 'Good Morning' : hour < 18 ? 'Good Afternoon' : 'Good Evening';
};
setGreeting();
</script>

<template>
    <UILayout :show-title="false" :show-breadcrumbs="false" title="Dashboard" page="dashboard">

        <div class="panel block md:hidden">
            <div class="salutation">
                <div class="greet">
                    <Iconify icon="material-symbols:person-2-outline-rounded" /> {{ greeting }}
                </div>
                <div class="who">{{ user ? `${user.first_name}!` : 'Welcome!' }}</div>
            </div>
        </div>


        <!-- Latest sermon -->
        <div class="panel mb-24">
            <section v-if="latest" id="overview" class="section  ">
                <FeaturedSermon :sermon="latest" />
            </section>
        </div>






        <!-- Latest sermons -->
        <div v-if="others.length" class="panel mb-24">

            <section id="latest-sermons" class="section">
                <div class="section-head">
                    <h2>Latest sermons</h2>
                    <div class="spacer"></div>
                    <Link class="link" :href="route('ui.sermons.index')">View all →</Link>
                </div>
                <div class="hrow" style="--w: 300px">
                    <SermonMiniCard v-for="s in others" :key="s.id" :sermon="s" />
                </div>
            </section>
        </div>

        <!-- Prayer points -->
        <div v-if="prayerPoints.length" class="panel mb-24">
            <section id="prayer-points" class="section">
                <div class="section-head">
                    <h2>Prayer points</h2>
                    <div class="spacer"></div>
                    <Link class="link" :href="route('ui.prayer')">All prayer points →</Link>
                </div>
                <div class="hrow" style="--w: 260px">
                    <Link v-for="p in prayerPoints" :key="p.id" :href="route('ui.prayer.show', p.id)"
                        class="card card-pad pp-card pp-link">
                        <div class="d">{{ fmtLongDay(p.date) }}</div>
                        <div class="t line-clamp-2">{{ p.title }}</div>
                        <!-- <div v-if="p.verses" class="v">{{ p.verses }}</div> -->
                    </Link>
                </div>
            </section>
        </div>

        <!-- Upcoming events -->
        <div v-if="events.length" class="panel mb-24">
            <section id="events" class="section">
                <div class="section-head">
                    <h2>Upcoming events</h2>
                    <div class="spacer"></div>
                    <Link class="link" :href="route('ui.events')">Full calendar →</Link>
                </div>
                <div class="hrow" style="--w: 340px">
                    <div v-for="e in events" :key="e.id" class="card card-pad event-card">
                        <div class="flex" style="gap: 14px; align-items: flex-start">
                            <div class="dateblock lg">
                                <div class="d">{{ dayNum(e.date) }}</div>
                                <div class="m">{{ monName(e.date) }}</div>
                            </div>
                            <div class="grow">
                                <div class="flex gap-8 mb-8">
                                    <span v-if="e.tag" class="badge badge-gold">{{ e.tag }}</span>
                                    <span class="tiny muted">{{ fmtDay(e.date) }}<template v-if="e.time"> · {{ e.time
                                    }}</template></span>
                                </div>
                                <h4
                                    style="font-family: var(--font-serif); font-weight: 600; font-size: 17px; line-height: 1.25">
                                    {{ e.title }}</h4>
                                <div v-if="e.loc" class="small muted flex" style="gap: 6px; margin-top: 4px">
                                    <Icon name="map" /> {{ e.loc }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Announcements + birthdays -->
        <div v-if="birthdays.length" class="panel mb-24">
            <section id="announcements" class="section">
                <div class="section-head">
                    <h2>Birthdays this week</h2>
                </div>
                <div class="grid cols-1" style="align-items: start">
                    <!-- <div class="card card-pad">
                    <div v-for="a in announcements" :key="a.id" class="list-row">
                        <span class="ico ico-brown" style="width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; flex: none"><Icon name="bell" /></span>
                        <div class="grow">
                            <div class="t" style="white-space: normal">{{ a.title }}</div>
                            <div class="s">{{ a.body }}</div>
                        </div>
                        <span class="tiny muted hide-sm" style="flex: none">{{ fmtDate(a.date) }}</span>
                    </div>
                </div> -->
                    <div class=" ">
                        <!-- <div class="flex gap-8 mb-12">
                        <span class="ico ico-gold" style="width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center"><Icon name="star" /></span>
                        <h3 style="font-size: 16px">Birthdays this week</h3>
                    </div> -->
                        <div v-for="b in birthdays" :key="b.id ?? b.name" class="list-row">
                            <Avatar :person="b" size="sm" />
                            <div class="grow">
                                <div class="t">{{ b.name }}</div>
                                <!-- <div v-if="b.cell" class="s">{{ b.cell }}</div> -->
                            </div>
                            <span class="badge badge-gold">🎂 {{ fmtDate(b.date) }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </UILayout>
</template>
