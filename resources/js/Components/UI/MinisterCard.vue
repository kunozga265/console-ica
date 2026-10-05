<script setup>
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Components/UI/Avatar.vue';
import { authorName } from '@/Components/UI/helpers';

defineProps({
    minister: { type: Object, required: true },
});
</script>

<template>
    <article class="card card-pad">
        <div class="flex" style="gap: 14px">
            <Avatar :person="minister" size="lg" />
            <div class="grow" style="min-width: 0">
                <h3 style="font-size: 17px; line-height: 1.2">
                    {{ authorName(minister) }}
                </h3>
                <div class="small muted">{{ minister.title }}</div>
            </div>
        </div>
        <p v-if="minister.biography" class="small muted mt-12 clamp-3" style="line-height: 1.55">{{ minister.biography }}</p>
        <div class="between mt-16">
            <span class="badge badge-outline">{{ minister.sermonCount }} sermons</span>
            <Link class="small" style="font-weight: 600; color: var(--accent-2)" :href="route('ui.sermons.index', { author: minister.slug }) + '#all'">View sermons →</Link>
        </div>
    </article>
</template>
