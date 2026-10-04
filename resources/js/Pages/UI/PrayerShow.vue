<script setup>
import { Link } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import ShareButton from '@/Components/UI/ShareButton.vue';
import PrayingButton from '@/Components/UI/PrayingButton.vue';
import { fmtLongDay } from '@/Components/UI/helpers';

const props = defineProps({
    prayer: { type: Object, required: true }, // { id, date, title, verses, body, prayingCount, praying }
    previous: { type: Object, default: null },
    next: { type: Object, default: null },
});

</script>

<template>
    <UILayout :show-title="false" :title="prayer.title" page="prayer" :breadcrumbs="[{ label: 'Prayer Points', href: route('ui.prayer') }, { label: prayer.title }]">
        <div class="panel mb-24">
            <article class="reader">
                <div class="kicker">
                    <Link class="badge badge-outline" :href="route('ui.prayer')">
                        <span style="display: inline-flex; transform: rotate(180deg)"><Icon name="chevright" /></span> All prayer points
                    </Link>
                    <span class="badge badge-plum">{{ fmtLongDay(prayer.date) }}, {{ new Date(prayer.date).getFullYear() }}</span>
                </div>
                <h1 class="sermon-title">{{ prayer.title }}</h1>
                <p v-if="prayer.verses" class="muted mt-8" style="font-size: 16px">{{ prayer.verses }}</p>
                <div class="byline">
                    <PrayingButton :prayer="prayer" :reload="['prayer']" />
                    <span class="small muted grow">
                        {{ prayer.prayingCount ? `${prayer.prayingCount} ${prayer.prayingCount === 1 ? 'person is' : 'people are'} praying` : 'Be the first to pray' }}
                    </span>
                    <ShareButton :title="prayer.title" :url="route('ui.prayer.show', prayer.id)" />
                </div>
                <div class="rich-text prayer-body" v-html="prayer.body"></div>
            </article>
        </div>

        <div v-if="previous || next" class="panel mb-24">
            <nav class="sermon-pager" aria-label="Previous and next prayer point">
                <Link v-if="previous" class="pager-link prev" :href="route('ui.prayer.show', previous.id)">
                    <span class="arrow"><Icon name="chevright" /></span>
                    <span class="grow"><span class="label">Previous</span><span class="title">{{ previous.title }}</span></span>
                </Link>
                <span v-else></span>
                <Link v-if="next" class="pager-link next" :href="route('ui.prayer.show', next.id)">
                    <span class="grow"><span class="label">Next</span><span class="title">{{ next.title }}</span></span>
                    <span class="arrow"><Icon name="chevright" /></span>
                </Link>
            </nav>
        </div>
    </UILayout>
</template>
