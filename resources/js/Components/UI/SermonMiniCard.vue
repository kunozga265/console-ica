<script setup>
import { router } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { authorName, fmtDateComma } from '@/Components/UI/helpers';

/* Compact sermon card, after the console Home page's "Latest Sermons" sermon-card:
   location + date, bold one-line title, author. */
const props = defineProps({
    sermon: { type: Object, required: true },
});

const open = () => router.visit(route('ui.sermons.show', props.sermon.id));
</script>

<template>
    <article class="mcard" @click="open">
        <div class="mcard-meta">
            <span class="where"><Icon name="folder" /> Main Church</span>
            <span class="date">{{ fmtDateComma(sermon.publishedAt) }}</span>
        </div>
        <div class="mcard-title" :title="sermon.title">{{ sermon.title }}</div>
        <div class="mcard-author">
            <Avatar :person="sermon.author" size="sm" />
            <div class="who">
                <div class="name">{{ authorName(sermon.author) }}</div>
                <div class="role">{{ sermon.author.title }}</div>
            </div>
        </div>
    </article>
</template>
