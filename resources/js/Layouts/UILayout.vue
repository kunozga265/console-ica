<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Icon as Iconify } from '@iconify/vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { toast, useToasts } from '@/Components/UI/useMemberActions';
import { dayNum, fmtDate, monName } from '@/Components/UI/helpers';
import { fileUrl } from '@/Plugins/composables';
import * as SAMPLE from '@/Components/UI/sampleData';
import '../../css/ui.css';

/*
 * Framed-panel shell for the guest/member UI (ported from ica-guest shell.js):
 * left rail (logo · greeting · nav · next cell meeting · promo), top bar,
 * breadcrumbs, rounded content panel.
 *
 * tabs: [{ label, target: '#section' }]  → smooth-scroll + scrollspy
 *       [{ label, href, on }]            → links to other pages
 *       []                               → no pill sub-nav (e.g. Dashboard)
 *
 * breadcrumbs: [{ label, href? }] after the implicit "Home"; defaults to [{ label: title }].
 */
const props = defineProps({
    title: { type: String, required: true },
    showTitle: { type: Boolean, default: true },
    showBreadcrumbs: { type: Boolean, default: true },
    page: { type: String, default: 'dashboard' },
    tabs: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: null },
});

const inertia = usePage();
const user = computed(() => inertia.props.auth?.user ?? null);
const isAdmin = computed(() => !!inertia.props.isAdmin);

const NAV = [
    { id: 'dashboard', name: 'Dashboard', icon: 'grid', route: 'ui.dashboard' },
    { id: 'sermons', name: 'Sermons', icon: 'mic', route: 'ui.sermons.index' },
    { id: 'events', name: 'Events', icon: 'calendar', route: 'ui.events' },
    { id: 'cells', name: 'Cells', icon: 'users', route: 'ui.cells' },
    { id: 'give', name: 'Give', icon: 'gift', route: 'ui.give' },
];
// "More" group; Attendance Sheets is admin-only.
const MORE = computed(() =>
    [
        isAdmin.value && { id: 'attendance', name: 'Attendance Sheets', icon: 'check', route: 'ui.attendance' },
        { id: 'resources', name: 'Resources', icon: 'folder', route: 'ui.resources' },
        { id: 'about', name: 'About', icon: 'info', route: 'ui.about' },
    ].filter(Boolean),
);
const moreOpen = ref(MORE.value.some((m) => m.id === props.page));

// Layout-wide data, shared by HandleInertiaRequests for ui/* requests. The
// sample data only stands in when a prop isn't sent at all; null (e.g. a guest
// has no cell meeting) hides the section.
const shared = (key, fallback) => (key in inertia.props ? inertia.props[key] : fallback);
const liveServices = computed(() => shared('liveServices', SAMPLE.LIVE_SERVICES) ?? []);
const nextCellMeeting = computed(() => shared('nextCellMeeting', SAMPLE.NEXT_CELL_MEETING));
const showCellMeeting = computed(() => !!nextCellMeeting.value);

const crumbs = computed(() => props.breadcrumbs ?? [{ label: props.title }]);

// Salutation, as in the console's AppLayout header.
const greeting = ref('');
const setGreeting = () => {
    const hour = new Date().getHours();
    greeting.value = hour < 12 ? 'Good Morning' : hour < 18 ? 'Good Afternoon' : 'Good Evening';
};
setGreeting();

const toasts = useToasts();
const navOpen = ref(false);
const theme = ref('light');
const scroller = ref(null);
const activeTarget = ref(props.tabs.find((t) => t.target)?.target ?? null);

const scrollTo = (target) => {
    document.querySelector(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    activeTarget.value = target;
};

/* ---- Top-bar dropdowns: live services, notifications, account ---- */
const openMenu = ref(null); // 'live' | 'bell' | 'account' | null
const liveRef = ref(null);
const bellRef = ref(null);
const accountRef = ref(null);
const toggleMenu = (name) => (openMenu.value = openMenu.value === name ? null : name);
const onDocClick = (e) => {
    const el = { live: liveRef, bell: bellRef, account: accountRef }[openMenu.value]?.value;
    if (el && !el.contains(e.target)) openMenu.value = null;
};
const onKey = (e) => {
    if (e.key === 'Escape') openMenu.value = null;
};

// Check in to a live service (members); guests are sent to sign in.
const checkingIn = ref(null);
const checkIn = (svc) => {
    if (!user.value) return (window.location.href = route('login'));
    checkingIn.value = svc.id;
    router.post(route('ui.check-in.store', svc.id), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['liveServices'],
        onSuccess: () => toast(`Checked in to ${svc.name}`),
        onFinish: () => (checkingIn.value = null),
    });
};

const notifications = computed(() => inertia.props.notifications ?? null);
const openBell = () => {
    toggleMenu('bell');
    if (openMenu.value === 'bell' && notifications.value?.unread) {
        router.post(route('ui.notifications.read'), {}, { preserveScroll: true, preserveState: true, only: ['notifications'] });
    }
};

const logout = () => router.post(route('logout'));
const userAvatar = computed(() => ({ id: user.value?.id, name: `${user.value?.first_name ?? ''} ${user.value?.last_name ?? ''}`, avatar: user.value?.avatar }));

let spy = null;

onMounted(async () => {
    document.addEventListener('mousedown', onDocClick);
    document.addEventListener('keydown', onKey);

    try {
        theme.value = localStorage.getItem('ica-theme') || 'light';
    } catch (e) {}

    await nextTick();

    // Cross-page links like "Series" → /ui/sermons#series land on the section.
    if (window.location.hash) {
        document.querySelector(window.location.hash)?.scrollIntoView({ block: 'start' });
    }

    const sections = props.tabs.filter((t) => t.target).map((t) => document.querySelector(t.target)).filter(Boolean);
    if (!sections.length) return;

    spy = new IntersectionObserver(
        (entries) => {
            entries.forEach((en) => {
                if (en.isIntersecting) activeTarget.value = '#' + en.target.id;
            });
        },
        { root: scroller.value, rootMargin: '-8% 0px -72% 0px', threshold: 0 },
    );
    sections.forEach((s) => spy.observe(s));
});

onBeforeUnmount(() => {
    spy?.disconnect();
    document.removeEventListener('mousedown', onDocClick);
    document.removeEventListener('keydown', onKey);
});
</script>

<template>
    <div class="ica-ui frame-page" :class="{ 'nav-open': navOpen }" :data-theme="theme">
        <Head :title="`${title} · ICA Church`" />

        <div class="app-frame">
            <aside class="rail">
                <Link :href="route('ui.dashboard')" class="rail-logo">
                    <img :src="fileUrl('assets/images/ica_logo.jpg')" alt="ICA" />
                    <span>
                        <div class="title">ICA APP</div>
                        <div class="subtitle">Online Church Portal</div>
                    </span>
                </Link>

                <div class="salutation mb-24">
                    <div class="greet"><Iconify icon="material-symbols:person-2-outline-rounded" /> {{ greeting }}</div>
                    <div class="who">{{ user ? `${user.first_name}!` : 'Welcome!' }}</div>
                </div>

                <nav class="gnav">
                    <Link
                        v-for="n in NAV"
                        :key="n.id"
                        :href="route(n.route)"
                        class="gnav-item"
                        :class="{ active: n.id === page }"
                    >
                        <Icon :name="n.icon" /><span>{{ n.name }}</span>
                    </Link>
                    <button class="gnav-item gnav-group" :class="{ open: moreOpen }" :aria-expanded="moreOpen" @click="moreOpen = !moreOpen">
                        <Icon name="more" /><span>More</span><Icon name="chevdown" class="caret" />
                    </button>
                    <div v-show="moreOpen" class="gnav-sub">
                        <Link v-for="m in MORE" :key="m.id" :href="route(m.route)" class="gnav-item" :class="{ active: m.id === page }">
                            <Icon :name="m.icon" /><span>{{ m.name }}</span>
                        </Link>
                    </div>
                    <a v-if="isAdmin" :href="route('admin.dashboard')" class="gnav-item gnav-admin">
                        <Icon name="grid" /><span>Admin</span><Icon name="ext" class="caret" />
                    </a>
                </nav>

                <Link v-if="showCellMeeting" :href="route('ui.cells')" class="rail-meeting">
                    <!-- <div class="label">Next cell meeting</div> -->
                    <div class="flex" style="gap: 12px; align-items: center">
                        <div class="dateblock">
                            <div class="d">{{ dayNum(nextCellMeeting.date) }}</div>
                            <div class="m">{{ monName(nextCellMeeting.date) }}</div>
                        </div>
                        <div class="grow">
                            <div class="t">Next Cell Meeting</div>
                            <!-- <div class="t">{{ nextCellMeeting.cell }}</div> -->
                            <!-- <div class="s">{{ fmtDay(nextCellMeeting.date) }}<template v-if="nextCellMeeting.time"> · {{ nextCellMeeting.time }}</template></div> -->
                            <div v-if="nextCellMeeting.loc" class="s">{{ nextCellMeeting.loc }}</div>
                        </div>
                    </div>
                </Link>

                <div v-if="!user" class="promo">
                    <span class="ico"><Icon name="heart" /></span>
                    <h4>New here?</h4>
                    <p>Plan your first visit and let us welcome you in person.</p>
                    <Link class="btn" :href="route('ui.about')">Plan a visit</Link>
                </div>
            </aside>

            <div class="stage">
                <header class="gbar">
                    <button class="icon-btn rail-ham" aria-label="Menu" @click="navOpen = !navOpen">
                        <Icon name="menu" />
                    </button>
                    <Link :href="route('ui.dashboard')" class="gbar-logo" aria-label="ICA App home">
                        <img :src="fileUrl('assets/images/ica_logo.jpg')" alt="" />
                        <span>ICA APP</span>
                    </Link>
                    <div v-if="tabs.length" class="subnav">
                        <template v-for="t in tabs" :key="t.label">
                            <Link v-if="t.href" :href="t.href" :class="{ on: t.on }">{{ t.label }}</Link>
                            <button v-else :class="{ on: activeTarget === t.target }" @click="scrollTo(t.target)">
                                {{ t.label }}
                            </button>
                        </template>
                    </div>
                    <div class="spacer"></div>

                    <div v-if="liveServices.length" ref="liveRef" class="live-wrap">
                        <button class="btn live-btn" :aria-expanded="openMenu === 'live'" @click="toggleMenu('live')">
                            <span class="live-dot"></span> <span class="hide-sm">Live</span> <span class="count">{{ liveServices.length }}</span>
                            <Icon name="chevdown" />
                        </button>
                        <div v-if="openMenu === 'live'" class="live-menu">
                            <div class="head">
                                <b>Live services</b>
                                <span class="tiny muted">{{ user ? 'Check in to mark your attendance' : 'Sign in to check in' }}</span>
                            </div>
                            <div v-for="svc in liveServices" :key="svc.id" class="list-row compact">
                                <div class="grow">
                                    <div class="t">{{ svc.name }}</div>
                                    <div class="s">{{ [svc.ministry, svc.time && `Started ${svc.time}`].filter(Boolean).join(' · ') }}</div>
                                </div>
                                <span v-if="svc.checked" class="badge badge-green"><Icon name="check" /> Present</span>
                                <button v-else class="btn btn-ghost btn-sm" :disabled="checkingIn === svc.id" @click="checkIn(svc)">Check in</button>
                            </div>
                            <Link v-if="isAdmin" :href="route('ui.attendance')" class="foot">All attendance sheets →</Link>
                        </div>
                    </div>

                    <div v-if="user" ref="bellRef" class="live-wrap">
                        <button class="icon-btn" aria-label="Notifications" :aria-expanded="openMenu === 'bell'" @click="openBell">
                            <Icon name="bell" /><span v-if="notifications?.unread" class="dot"></span>
                        </button>
                        <div v-if="openMenu === 'bell'" class="live-menu bell-menu">
                            <div class="head"><b>Notifications</b></div>
                            <template v-if="notifications?.items.length">
                                <component
                                    :is="n.url ? Link : 'div'"
                                    v-for="n in notifications.items"
                                    :key="n.id"
                                    :href="n.url || undefined"
                                    class="notif"
                                    :class="{ unread: !n.read }"
                                    @click="openMenu = null"
                                >
                                    <div class="t">{{ n.title }}</div>
                                    <div v-if="n.body" class="s clamp-2">{{ n.body }}</div>
                                    <div class="tiny muted mt-4">{{ fmtDate(n.at) }}</div>
                                </component>
                            </template>
                            <p v-else class="small muted" style="padding: 12px 0">You're all caught up.</p>
                        </div>
                    </div>

                    <div v-if="user" ref="accountRef" class="live-wrap">
                        <button class="avatar-btn" aria-label="Account" :aria-expanded="openMenu === 'account'" @click="toggleMenu('account')">
                            <Avatar :person="userAvatar" />
                        </button>
                        <div v-if="openMenu === 'account'" class="live-menu account-menu">
                            <div class="head">
                                <b>{{ user.first_name }} {{ user.last_name }}</b>
                                <span class="tiny muted">{{ user.email }}</span>
                            </div>
                            <Link :href="route('ui.profile')" class="menu-item"><Icon name="person" /> My profile</Link>
                            <Link :href="route('ui.profile') + '#notes'" class="menu-item"><Icon name="comment" /> My notes</Link>
                            <a v-if="isAdmin" :href="route('admin.dashboard')" class="menu-item"><Icon name="grid" /> Admin dashboard</a>
                            <a :href="route('profile.show')" class="menu-item"><Icon name="info" /> Account settings</a>
                            <button class="menu-item" @click="logout"><Icon name="ext" /> Log out</button>
                        </div>
                    </div>

                    <a v-if="!user" :href="route('login')" class="btn login-btn">
                        <Iconify width="20" icon="material-symbols:person" /> <span>Login</span>
                    </a>
                    <a class="store hide-sm" href="https://play.google.com/store/apps/details?id=com.icaapp.app" target="_blank" aria-label="Get it on Google Play">
                        <Iconify width="30" icon="logos:google-play-icon" />
                    </a>
                    <a class="store hide-sm" href="https://apps.apple.com/gb/app/ica-app-online-church-portal/id6466726690" target="_blank" aria-label="Download on the App Store">
                        <Iconify width="30" icon="logos:apple-app-store" />
                    </a>
                </header>

                <h1  class="page-title">{{ showTitle ? title : '' }}</h1>
                <nav v-if="showBreadcrumbs"  class="crumbs-bar" aria-label="Breadcrumb">
                    <Link :href="route('ui.dashboard')">Home</Link>
                    <template v-for="(c, i) in crumbs" :key="i">
                        <Icon name="chevright" />
                        <Link v-if="c.href && i < crumbs.length - 1" :href="c.href">{{ c.label }}</Link>
                        <span v-else :aria-current="i === crumbs.length - 1 ? 'page' : null">{{ c.label }}</span>
                    </template>
                </nav>

                <div ref="scroller" class="scroller">
                    <slot name="feature" />

                    <div class="mb-24">
                        <slot />
                    </div>
                </div>
            </div>
        </div>

        <div class="scrim" @click="navOpen = false"></div>

        <div v-if="toasts.length" class="toast-wrap">
            <div v-for="t in toasts" :key="t.id" class="toast" :style="t.leaving ? { opacity: 0 } : null">
                <Icon name="check" /><span>{{ t.message }}</span>
            </div>
        </div>
    </div>
</template>
