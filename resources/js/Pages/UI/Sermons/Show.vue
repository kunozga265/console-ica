<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import ShareButton from '@/Components/UI/ShareButton.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import BookmarkButton from '@/Components/UI/BookmarkButton.vue';
import SermonMiniCard from '@/Components/UI/SermonMiniCard.vue';
import RichEditor from '@/Components/UI/RichEditor.vue';
import { authorName, copyText, fmtDate } from '@/Components/UI/helpers';
import { storedList, toast } from '@/Components/UI/useMemberActions';

const props = defineProps({
    sermon: { type: Object, required: true },
    // Body split into numbered sentence spans (<span id="n" class="data">).
    renderedBody: { type: String, default: '' },
    highlights: { type: Array, default: () => [] }, // [{ id, spanId }] — signed-in members only
    bookmarks: { type: Array, default: () => [] }, // [{ id, spanId }] — signed-in members only
    note: { type: Object, default: null }, // { id, body } — signed-in members only
    canAnnotate: { type: Boolean, default: false }, // signed in with a linked member profile
    inSeries: { type: Array, default: () => [] }, // the rest of this sermon's series, in order
    previous: { type: Object, default: null },
    next: { type: Object, default: null },
});

const s = computed(() => props.sermon);
const crumbs = computed(() => [{ label: 'Sermons', href: route('ui.sermons.index') }, { label: s.value.title }]);

/* ---- Video: embed YouTube, otherwise link out ---- */
const youtubeId = computed(() => {
    const m = (s.value.videoUrl || '').match(/(?:youtu\.be\/|v=|\/embed\/|\/live\/|\/shorts\/)([\w-]{11})/);
    return m ? m[1] : null;
});

/*
 * ---- Sentence marks: highlights and bookmarks ----
 * Both store a sentence-span number. Members persist to the server; guests
 * keep them on this device.
 */
const useMarks = (kind, serverList) => {
    const localKey = `ica-${kind}-${s.value.id}`;
    const local = ref(storedList.read(localKey).map(String));
    const server = ref(serverList().map((m) => String(m.spanId)));
    watch(serverList, (list) => (server.value = list.map((m) => String(m.spanId))));
    const ids = computed(() => new Set(props.canAnnotate ? server.value : local.value));

    const toggle = (spanId, caption, labels) => {
        const on = !ids.value.has(spanId);
        if (!props.canAnnotate) {
            local.value = on ? [...local.value, spanId] : local.value.filter((x) => x !== spanId);
            storedList.write(localKey, local.value);
            return toast(on ? `${labels[0]} · Sign in to sync` : labels[1]);
        }
        // Optimistic; the redirect back reloads the real list.
        server.value = on ? [...server.value, spanId] : server.value.filter((x) => x !== spanId);
        const opts = { preserveScroll: true, preserveState: true, onSuccess: () => toast(on ? labels[0] : labels[1]) };
        if (on) {
            const payload = kind === 'highlights' ? { highlightId: Number(spanId) } : { captionId: Number(spanId), caption };
            router.post(route(`ui.${kind}.store`, s.value.id), payload, opts);
        } else {
            const existing = serverList().find((m) => String(m.spanId) === spanId);
            if (existing) router.delete(route(`ui.${kind}.destroy`, existing.id), opts);
        }
    };
    return { ids, toggle };
};

const highlights = useMarks('highlights', () => props.highlights);
const lineBookmarks = useMarks('bookmarks', () => props.bookmarks);

const bodyEl = ref(null);
const active = ref(null); // { id, text, x, y }

const paint = () => {
    bodyEl.value?.querySelectorAll('span[id]').forEach((el) => {
        el.classList.toggle('hl', highlights.ids.value.has(el.id));
        el.classList.toggle('bm', lineBookmarks.ids.value.has(el.id));
        el.classList.toggle('active', el.id === active.value?.id);
    });
};
watch([highlights.ids, lineBookmarks.ids, active], () => nextTick(paint));

// Real links in the body (Bible references etc.) open in a new tab; the numbered
// sentence anchors (<a class="data">) stay inert so tapping a line highlights it.
const isExternal = (a) => /^https?:\/\//i.test(a.getAttribute('href') ?? '');
const decorateLinks = () => {
    bodyEl.value?.querySelectorAll('a[href]').forEach((a) => {
        if (!isExternal(a)) return;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        a.classList.add('body-link');
    });
};
watch(() => props.renderedBody, () => nextTick(decorateLinks));

const closePop = () => (active.value = null);

const onBodyClick = (e) => {
    const link = e.target.closest('a');
    if (link && isExternal(link)) return; // let the browser open it (new tab)
    // The spans wrap <a href="n"> anchors (used by the mobile app); never follow them here.
    if (link) e.preventDefault();
    const span = e.target.closest('span[id]');
    if (!span) return closePop();
    const rect = span.getBoundingClientRect();
    active.value = { id: span.id, text: span.textContent.trim(), x: rect.left + rect.width / 2, y: rect.top };
};

const toggleHighlight = () => {
    const { id, text } = active.value;
    closePop();
    highlights.toggle(id, text, ['Highlighted', 'Highlight removed']);
};
const toggleLineBookmark = () => {
    const { id, text } = active.value;
    closePop();
    lineBookmarks.toggle(id, text, ['Line bookmarked', 'Bookmark removed']);
};
const copySentence = () => {
    copyText(active.value.text);
    closePop();
    toast('Copied to clipboard');
};


const onDocDown = (e) => {
    if (active.value && !e.target.closest('.hl-pop') && !e.target.closest('.reader-body span[id]')) closePop();
};
const onKey = (e) => e.key === 'Escape' && closePop();

onMounted(() => {
    nextTick(paint);
    nextTick(decorateLinks);
    document.addEventListener('mousedown', onDocDown);
    document.addEventListener('scroll', closePop, true);
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocDown);
    document.removeEventListener('scroll', closePop, true);
    document.removeEventListener('keydown', onKey);
});

/* ---- My notes (rich text, HTML): members persist to the server, guests to this device ---- */
const noteKey = computed(() => `ica-note-${s.value.id}`);
const readLocalNote = () => {
    try {
        return localStorage.getItem(noteKey.value) || '';
    } catch (e) {
        return '';
    }
};
const savedNote = ref(props.canAnnotate ? (props.note?.body ?? '') : readLocalNote());
watch(() => props.note, (n) => props.canAnnotate && (savedNote.value = n?.body ?? ''));
const draft = ref(savedNote.value);
const saving = ref(false);
const dirty = computed(() => draft.value !== savedNote.value);

const saveNote = () => {
    const body = draft.value;
    if (!props.canAnnotate) {
        try {
            body ? localStorage.setItem(noteKey.value, body) : localStorage.removeItem(noteKey.value);
        } catch (e) { }
        savedNote.value = body;
        toast(body ? 'Note saved on this device · Sign in to sync' : 'Note removed');
        return;
    }
    const opts = {
        preserveScroll: true,
        preserveState: true,
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
        onSuccess: () => toast(body ? 'Note saved' : 'Note removed'),
    };
    if (body) router.post(route('ui.notes.store', s.value.id), { body }, opts);
    else if (props.note) router.delete(route('ui.notes.destroy', props.note.id), opts);
};
</script>

<template>
    <UILayout :show-title="false" :title="s.title" page="sermons" :breadcrumbs="crumbs">
        <div class="panel mb-24">
            <div class="reader">
                <div class="kicker hidden md:flex">
                    <Link class="badge badge-outline" :href="route('ui.sermons.index')">
                        <span style="display: inline-flex; transform: rotate(180deg)">
                            <Icon name="chevright" />
                        </span> All sermons
                    </Link>
                    <Link v-if="s.ministry" class="badge badge-blue"
                        :href="route('ui.sermons.index', { ministry: s.ministry.id }) + '#all'">{{ s.ministry.name }}
                    </Link>
                    <Link v-if="s.series" class="badge badge-gold"
                        :href="route('ui.sermons.index', { series: s.series.slug }) + '#all'">{{ s.series.title }}</Link>
                    <span class="tiny muted">{{ fmtDate(s.publishedAt) }}</span>
                </div>
                <div class="block md:hidden"> <span class="tiny muted">{{ fmtDate(s.publishedAt) }}</span></div>
                <h1 class="sermon-title">{{ s.title }}</h1>
                <p v-if="s.subtitle" class="muted mt-8" style="font-size: 15px">{{ s.subtitle }}</p>
                <div class="block md:hidden">
                    <Link v-if="s.series" class="badge badge-gold"
                        :href="route('ui.sermons.index', { series: s.series.slug }) + '#all'">{{ s.series.title }}</Link>
                </div>
                <div class="byline block md:flex md:justify-between">
                    <div class="flex items-center gap-12 mb-4">

                        <Avatar :person="s.author" />
                        <div class="grow">
                            <div style="font-weight: 600">{{ authorName(s.author) }}</div>
                            <div class="small muted">{{ s.author.title }}</div>
                        </div>
                    </div>
                    <div class="flex md:justify-end">
                        <BookmarkButton :sermon-id="s.id" kind="favorite" />
                        <BookmarkButton :sermon-id="s.id" />
                        <ShareButton :title="s.title" :url="route('ui.sermons.show', s.slug)" />
                    </div>
                </div>

                <div v-if="youtubeId" class="video">
                    <iframe :src="`https://www.youtube-nocookie.com/embed/${youtubeId}`" :title="s.title"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
                <a v-else-if="s.videoUrl" class="btn btn-ghost mb-24" :href="s.videoUrl" target="_blank" rel="noopener">
                    <Icon name="play" /> Watch the message
                </a>

                <div ref="bodyEl" class="reader-body" @click="onBodyClick" v-html="renderedBody"></div>
                <p class="tiny muted mt-16 flex" style="gap: 6px">
                    <Icon name="highlighter" /> Tip: tap any sentence to highlight, bookmark or copy it.
                </p>
            </div>

            <div class="comments">
                <div class="section-head">
                    <h2 style="font-size: 19px">My notes</h2>
                    <div class="spacer"></div>
                    <span class="tiny muted">Private to you</span>
                </div>
                <RichEditor v-model="draft" placeholder="Write what God is saying to you through this message…" />
                <div class="between mt-8">
                    <span v-if="!canAnnotate" class="tiny muted">
                        Saved on this device · <a :href="route('login')"
                            style="color: var(--accent-2); font-weight: 600">Sign in</a> to keep notes across devices
                    </span>
                    <span v-else class="tiny muted">Synced with your ICA account</span>
                    <button class="btn btn-primary btn-sm" :disabled="saving || !dirty" @click="saveNote">
                        {{ saving ? 'Saving…' : 'Save note' }}
                    </button>
                </div>
            </div>

            <section v-if="inSeries.length" class="comments">
                <div class="section-head">
                    <h2 style="font-size: 19px">Sermons in series</h2>
                    <span class="badge badge-gold">{{ s.series.title }}</span>
                    <div class="spacer"></div>
                    <Link class="link" :href="route('ui.sermons.index', { series: s.series.slug }) + '#all'">View series →
                    </Link>
                </div>
                <div class="hrow" style="--w: 280px">
                    <SermonMiniCard v-for="r in inSeries" :key="r.id" :sermon="r" />
                </div>
            </section>

            <nav v-if="previous || next" class="sermon-pager" aria-label="Previous and next sermon">
                <Link v-if="previous" class="pager-link prev" :href="route('ui.sermons.show', previous.slug)">
                    <span class="arrow">
                        <Icon name="chevright" />
                    </span>
                    <span class="grow">
                        <span class="label">Previous sermon</span>
                        <span class="title">{{ previous.title }}</span>
                    </span>
                </Link>
                <span v-else></span>
                <Link v-if="next" class="pager-link next" :href="route('ui.sermons.show', next.slug)">
                    <span class="grow">
                        <span class="label">Next sermon</span>
                        <span class="title">{{ next.title }}</span>
                    </span>
                    <span class="arrow">
                        <Icon name="chevright" />
                    </span>
                </Link>
            </nav>
        </div>

        <div v-if="active" class="hl-pop" :style="{ left: active.x + 'px', top: active.y + 'px' }">
            <button @click="toggleHighlight">
                <Icon name="highlighter" /> {{ highlights.ids.value.has(active.id) ? 'Remove highlight' : 'Highlight' }}
            </button>
            <button @click="toggleLineBookmark">
                <Icon name="bookmark" /> {{ lineBookmarks.ids.value.has(active.id) ? 'Remove bookmark' : 'Bookmark' }}
            </button>
            <button @click="copySentence">
                <Icon name="copy" /> Copy
            </button>
        </div>
    </UILayout>
</template>
