<script setup>
import { router } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import BookmarkButton from '@/Components/UI/BookmarkButton.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { authorName, fmtDate, mono, tone } from '@/Components/UI/helpers';

const props = defineProps({
    sermon: { type: Object, required: true },
    lg: { type: Boolean, default: false },
    feature: { type: Boolean, default: false },
});

const open = () => router.visit(route('ui.sermons.show', props.sermon.id));
</script>

<template>
    <article class="scard" :class="{ feature }" @click="open">
        <div>

            
           
            <div class="top">
                <!-- <span class="smon" :class="{ lg }" :style="{ background: tone(sermon.title) }">{{ mono(sermon.title) }}</span> -->
                <div class="grow" style="min-width: 0">
                     <div>  <span class="tiny" :class="{ muted: !feature }">{{ fmtDate(sermon.publishedAt) }}</span></div>
                    <h4 class="sermon-title mb-2 truncate">{{ sermon.title }}</h4>
                    <!-- <div v-if="sermon.subtitle" class="sub">{{ sermon.subtitle }}</div> -->
                      <div class="who text-xs">{{ authorName(sermon.author) }}</div>
                </div>
                <Avatar :person="sermon.author" size="lg" />
                <!-- <BookmarkButton :sermon-id="sermon.id" /> -->
            </div>
        </div>
        <!-- <div class="foot">
            <Avatar :person="sermon.author" size="sm" />
            <div class="who"><b>{{ authorName(sermon.author) }}</b><span>{{ sermon.author.title }}</span></div>
            <span style="margin-left: auto; display: flex; gap: 8px; align-items: center">
              
                <span v-if="sermon.videoUrl" class="badge badge-gold" style="gap: 5px"><Icon name="play" /> Watch</span>
                <span v-else class="badge badge-outline">Read</span>
            </span>
        </div> -->
    </article>
</template>
