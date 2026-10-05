<script setup>
import { router } from '@inertiajs/vue3';
import Icon from '@/Components/UI/Icon.vue';
import { fmtDate, mono, tone } from '@/Components/UI/helpers';

const props = defineProps({
    series: { type: Object, required: true },
});

// Opens the sermon list filtered to this series.
const open = () => router.visit(route('ui.sermons.index', { series: props.series.slug }) + '#all');
</script>

<template>
    <article class="scard" @click="open">
        <div class="top">
            <span class="smon" :style="{ background: 'black' }">{{ mono(series.title) }}</span>
            <div class="" style="min-width: 0">
                <h4 class="truncate">{{ series.title }}</h4>
                <div class="sub">{{ [series.duration, `${series.sermonCount} ${series.sermonCount === 1 ? 'message' : 'messages'}`].filter(Boolean).join(' · ') }}</div>
            </div>
        </div>
        <!-- <p v-if="series.description" class="small muted clamp-3" style="line-height: 1.55; margin: 0">{{ series.description }}</p>
        <div class="foot">
            <span class="tiny muted">Since {{ fmtDate(series.firstSermonDate) }}</span>
            <span class="badge badge-gold" style="margin-left: auto">Start series <Icon name="chevright" /></span>
        </div> -->
    </article>
</template>
