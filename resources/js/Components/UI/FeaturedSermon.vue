<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import BookmarkButton from '@/Components/UI/BookmarkButton.vue';
import { authorName, bodyHtml, fmtDateComma } from '@/Components/UI/helpers';

/* Latest-sermon hero, after the console Home page's event-detail card:
   serif title, author, faded excerpt and a "Read More" link. */
defineProps({
    sermon: { type: Object, required: true },
});
</script>

<template>
    <article class=" b-0 fsermon">
        <div class="fsermon-head">
            <div class="grow">
                <div class="flex gap-8 mb-12 wrap">
                    <!-- <span class="badge badge-blue">Latest sermon</span> -->
                    <span v-if="sermon.series" class="badge badge-outline">{{ sermon.series.title }}</span>
                    <span class="tiny muted">{{ fmtDateComma(sermon.publishedAt) }}</span>
                </div>
                <h2 class="fsermon-title">{{ sermon.title }}</h2>
                <div v-if="sermon.subtitle" class="fsermon-sub">{{ sermon.subtitle }}</div>
            </div>
            <BookmarkButton :sermon-id="sermon.id" />
        </div>

        <div class="fsermon-author ">
            <Avatar :person="sermon.author" size="lg" />
            <div>
                <div class="name">{{ authorName(sermon.author) }}</div>
                <div class="role">{{ sermon.author.title }}</div>
            </div>
        </div>

        <div class="hidden md:block fsermon-excerpt border-t pt-4" v-html="bodyHtml(sermon.body)"></div>

        <div class="flex">
            <Link :href="route('ui.sermons.show', sermon.slug)" class="btn btn-light">
                Read More <Icon name="chevright" />
            </Link>
            <span v-if="sermon.videoUrl" class="badge badge-gold" style="margin-left: auto"><Icon name="play" /> Watch</span>
        </div>
    </article>
</template>
