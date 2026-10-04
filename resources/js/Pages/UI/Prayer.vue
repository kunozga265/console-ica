<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import UILayout from '@/Layouts/UILayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import PrayingButton from '@/Components/UI/PrayingButton.vue';
import { fmtLongDay } from '@/Components/UI/helpers';

const props = defineProps({
    prayerPoints: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ page: 1, total: 0, hasMore: false }) },
});

const year = (ms) => new Date(ms).getFullYear();
const thisYear = new Date().getFullYear();

const loading = ref(false);
const loadMore = () =>
    router.reload({
        only: ['prayerPoints', 'pagination'],
        data: { page: props.pagination.page + 1 },
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
</script>

<template>
    <UILayout title="Prayer Points" page="prayer" :breadcrumbs="[{ label: 'Prayer Points' }]">
        <div class="panel mb-24">
            <div class="section-head">
                <h2>Prayer points</h2>
                <div class="spacer"></div>
                <span class="small muted">{{ pagination.total }} prayer points</span>
            </div>

            <div v-if="prayerPoints.length" class="grid cols-3 prayer-grid">
                <Link v-for="p in prayerPoints" :key="p.id" :href="route('ui.prayer.show', p.id)" class="card card-pad pp-card pp-link">
                    <div class="d">{{ fmtLongDay(p.date) }}<template v-if="year(p.date) !== thisYear">, {{ year(p.date) }}</template></div>
                    <div class="t">{{ p.title }}</div>
                    <div v-if="p.verses" class="v">{{ p.verses }}</div>
                    <div class="pp-foot">
                        <PrayingButton :prayer="p" />
                        <span class="tiny" style="margin-left: auto; font-weight: 600">Read →</span>
                    </div>
                </Link>
            </div>
            <div v-else class="card card-pad empty-state">
                <Icon name="pray" />
                <h3>No prayer points yet</h3>
            </div>

            <div v-if="pagination.hasMore" class="mt-24" style="text-align: center">
                <button class="btn btn-ghost" :disabled="loading" @click="loadMore">
                    {{ loading ? 'Loading…' : `Load more (${pagination.total - prayerPoints.length} more)` }}
                </button>
            </div>
        </div>
    </UILayout>
</template>
