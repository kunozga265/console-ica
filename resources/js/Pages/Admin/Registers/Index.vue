<!-- Admin twin of Pages/UI/AttendanceSheets/Index.vue (same data via Admin\RegisterController). -->
<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import RegisterForm from '@/Components/UI/RegisterForm.vue';
import { fmtDate, fmtDay } from '@/Components/UI/helpers';

const props = defineProps({
    active: { type: Array, default: () => [] }, // today's registers
    others: { type: Object, required: true }, // { data, page, lastPage, total }
    filters: { type: Object, default: () => ({}) },
    ministries: { type: Array, default: () => [] },
});

const creating = ref(false);

const search = ref(props.filters.search ?? '');
let debounce;
watch(search, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => router.get(route('admin.registers.index'), v ? { search: v } : {}, { preserveState: true, preserveScroll: true, replace: true }), 300);
});
const goPage = (page) => router.get(route('admin.registers.index'), { ...(search.value ? { search: search.value } : {}), page }, { preserveState: true, preserveScroll: true });
</script>

<template>
    <AdminLayout title="Registers" page="registers" crumb="Community">
        <div class="section-head">
            <h2>Service registers</h2>
            <div class="spacer"></div>
            <label class="searchbar" style="max-width: 260px"><Icon name="search" /><input v-model="search" type="search" placeholder="Search registers…" /></label>
            <button class="btn btn-primary btn-sm" @click="creating = true"><Icon name="plus" /> New register</button>
        </div>

        <section class="section">
            <div class="flex gap-8 mb-12">
                <span class="badge badge-red live"><span class="bullet"></span>Active today</span>
                <span class="small muted">Members can be marked on these</span>
            </div>
            <div v-if="active.length" class="grid cols-2">
                <Link v-for="r in active" :key="r.code" :href="route('admin.registers.show', r.code)" class="card card-pad register-card is-active">
                    <div class="grow" style="min-width: 0">
                        <div class="t">{{ r.name }}</div>
                        <div class="small muted">{{ r.ministry }} · {{ fmtDay(r.date) }} {{ fmtDate(r.date) }}</div>
                    </div>
                    <div class="count"><b>{{ r.attendeeCount }}</b><span>present</span></div>
                </Link>
            </div>
            <div v-else class="card card-pad small muted">No registers are open today.</div>
        </section>

        <section class="section">
            <div class="section-head">
                <h2 style="font-size: 19px">Other registers</h2>
                <div class="spacer"></div>
                <span class="small muted">{{ others.total }} total</span>
            </div>
            <div v-if="others.data.length" class="card">
                <Link v-for="r in others.data" :key="r.code" :href="route('admin.registers.show', r.code)" class="list-row register-row">
                    <div class="grow">
                        <div class="t">{{ r.name }}</div>
                        <div class="s">{{ r.ministry }} · {{ fmtDay(r.date) }} {{ fmtDate(r.date) }}</div>
                    </div>
                    <span class="badge" :class="r.date > Date.now() ? 'badge-blue' : 'badge-outline'">{{ r.date > Date.now() ? 'Upcoming' : 'Closed' }}</span>
                    <span class="badge badge-gold">{{ r.attendeeCount }} present</span>
                    <Icon name="chevright" />
                </Link>
            </div>
            <div v-else class="card card-pad small muted">No registers found.</div>

            <div v-if="others.lastPage > 1" class="pager mt-16">
                <button class="btn btn-ghost btn-sm" :disabled="others.page <= 1" @click="goPage(others.page - 1)">← Newer</button>
                <span class="small muted">Page {{ others.page }} of {{ others.lastPage }}</span>
                <button class="btn btn-ghost btn-sm" :disabled="others.page >= others.lastPage" @click="goPage(others.page + 1)">Older →</button>
            </div>
        </section>

        <RegisterForm v-if="creating" :ministries="ministries" route-prefix="admin.registers" @close="creating = false" />
    </AdminLayout>
</template>
