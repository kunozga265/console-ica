<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { fileUrl } from '@/Plugins/composables';
import { toast, useToasts } from '@/Components/UI/useMemberActions';
import '../../css/admin.css';

/*
 * Admin console shell (ported from the static ica console prototype):
 * grouped sidebar with counts, topbar with breadcrumb + title, theme toggle.
 * Its styling (admin.css, scoped to .ica-admin) is independent of the site.
 */
const props = defineProps({
    title: { type: String, required: true },
    page: { type: String, required: true }, // active nav id
    crumb: { type: String, default: 'Admin' },
});

const inertia = usePage();
const user = computed(() => inertia.props.auth?.user ?? {});
const counts = computed(() => inertia.props.adminCounts ?? {});
const fmt = (n) => (n === undefined || n === null ? null : Number(n).toLocaleString());

const NAV = computed(() => [
    { label: null, items: [{ id: 'dashboard', name: 'Dashboard', icon: 'grid', route: 'admin.dashboard' }] },
    {
        label: 'Content',
        items: [
            { id: 'sermons', name: 'Sermons', icon: 'mic', route: 'admin.sermons.index', count: fmt(counts.value.sermons) },
            { id: 'series', name: 'Series', icon: 'layers', route: 'admin.series.index', count: fmt(counts.value.series) },
            { id: 'ministers', name: 'Ministers', icon: 'person', route: 'admin.ministers.index', count: fmt(counts.value.ministers) },
            { id: 'resources', name: 'Resources', icon: 'folder', route: 'admin.resources.index' },
        ],
    },
    {
        label: 'Community',
        items: [
            { id: 'members', name: 'Members', icon: 'users', route: 'admin.members.index', count: fmt(counts.value.members) },
            { id: 'registers', name: 'Registers', icon: 'check', route: 'admin.registers.index' },
            { id: 'attendance', name: 'Attendance', icon: 'grid', route: 'admin.attendance' },
            { id: 'cells', name: 'Cells', icon: 'shield', route: 'admin.cells.index', count: counts.value.cells ? `${counts.value.cells} new` : null },
            { id: 'events', name: 'Events', icon: 'calendar', route: 'admin.events.index', count: fmt(counts.value.events) },
            { id: 'ministries', name: 'Ministries', icon: 'layers', route: 'admin.ministries.index' },
        ],
    },
    {
        label: 'Engagement',
        items: [
            { id: 'prayer', name: 'Prayer Points', icon: 'pray', route: 'admin.prayer.index', count: fmt(counts.value.prayer) },
            { id: 'announcements', name: 'Announcements', icon: 'bell', route: 'admin.announcements.edit' },
            { id: 'giving', name: 'Giving Options', icon: 'gift', route: 'admin.giving.index' },
            { id: 'devotions', name: 'Devotionals', icon: 'book', soon: true },
            { id: 'favorites', name: 'Favorites', icon: 'heart', route: 'admin.favorites' },
        ],
    },
]);

/* ---- Theme (light/dark), remembered per browser ---- */
const theme = ref('light');
const toggleTheme = () => {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
    try {
        localStorage.setItem('ica-admin-theme', theme.value);
    } catch (e) {}
};

/* ---- Topbar search → sermons ---- */
const q = ref('');
const search = () => q.value.trim() && router.get(route('admin.sermons.index'), { search: q.value.trim() });

/* ---- Flash messages from redirects → toasts ---- */
const toasts = useToasts();
watch(
    () => inertia.props.flash?.success,
    (msg) => msg && toast(msg),
    { immediate: true },
);

const navOpen = ref(false);
const onKey = (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        document.querySelector('.ica-admin .topbar .searchbar input')?.focus();
    }
};
onMounted(() => {
    try {
        theme.value = localStorage.getItem('ica-admin-theme') || 'light';
    } catch (e) {}
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="ica-admin" :class="{ 'nav-open': navOpen }" :data-theme="theme">
        <Head :title="`${title} · ICA Console`" />
        <div class="layout">
            <aside class="sidebar">
                <Link class="brand" :href="route('admin.dashboard')">
                    <img class="brand-logo" :src="fileUrl('assets/images/ica_logo.jpg')" alt="" />
                    <span>
                        <div class="brand-name">ICA <span style="color: var(--accent)">Console</span></div>
                        <div class="brand-sub">Church Portal · Admin</div>
                    </span>
                </Link>

                <nav class="nav-scroll">
                    <div v-for="(g, gi) in NAV" :key="gi" class="nav-group">
                        <div v-if="g.label" class="nav-label">{{ g.label }}</div>
                        <template v-for="it in g.items" :key="it.id">
                            <span v-if="it.soon" class="nav-item is-soon" title="Coming soon — there's no devotional data yet">
                                <Icon :name="it.icon" /><span>{{ it.name }}</span><span class="count">Soon</span>
                            </span>
                            <Link v-else class="nav-item" :class="{ active: it.id === page }" :href="route(it.route)" @click="navOpen = false">
                                <Icon :name="it.icon" /><span>{{ it.name }}</span>
                                <span v-if="it.count" class="count">{{ it.count }}</span>
                            </Link>
                        </template>
                    </div>
                </nav>

                <div class="side-foot">
                    <Link class="nav-item" :class="{ active: page === 'users' }" :href="route('admin.users.index')"><Icon name="settings" /><span>Users &amp; roles</span></Link>
                    <a class="nav-item" :href="route('ui.dashboard')"><Icon name="ext" /><span>Back to site</span></a>
                    <a class="user-chip" :href="route('ui.profile')">
                        <Avatar :person="{ id: user.id, name: `${user.first_name ?? ''} ${user.last_name ?? ''}`, avatar: user.avatar }" />
                        <span class="who"><b>{{ user.first_name }} {{ user.last_name }}</b><span>{{ $page.props.roles?.includes('super') ? 'Super admin' : 'Admin' }}</span></span>
                    </a>
                </div>
            </aside>

            <main class="main">
                <header class="topbar">
                    <button class="icon-btn hamburger" aria-label="Menu" @click="navOpen = !navOpen"><Icon name="menu" /></button>
                    <div class="titles">
                        <div class="crumbs">{{ crumb }} <Icon name="chevright" /> <span style="color: var(--ink-soft)">{{ title }}</span></div>
                        <h1>{{ title }}</h1>
                    </div>
                    <div class="spacer"></div>
                    <form class="searchbar" @submit.prevent="search">
                        <Icon name="search" />
                        <input v-model="q" type="search" placeholder="Search sermons…" />
                        <kbd>⌘K</kbd>
                    </form>
                    <slot name="actions" />
                    <button class="icon-btn" :aria-label="theme === 'dark' ? 'Light theme' : 'Dark theme'" @click="toggleTheme">
                        <Icon :name="theme === 'dark' ? 'sun' : 'moon'" />
                    </button>
                </header>

                <div class="page">
                    <slot />
                </div>
            </main>
        </div>

        <div class="scrim" @click="navOpen = false"></div>

        <div v-if="toasts.length" class="toast-wrap">
            <div v-for="t in toasts" :key="t.id" class="toast" :style="t.leaving ? { opacity: 0 } : null">
                <Icon name="check" /><span>{{ t.message }}</span>
            </div>
        </div>
    </div>
</template>
