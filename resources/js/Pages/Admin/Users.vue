<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Pager from '@/Components/Admin/Pager.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    users: { type: Array, default: () => [] },
    paging: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
});

const page = usePage();
const me = computed(() => page.props.auth?.user?.id);
const roleError = computed(() => page.props.errors?.role);
const { submit } = useSubmit();

const f = ref({ search: props.filters.search ?? '', role: props.filters.role ?? '' });
const apply = () => router.get(route('admin.users.index'), Object.fromEntries(Object.entries(f.value).filter(([, v]) => v)), { preserveState: true, replace: true });
let debounce;
watch(() => f.value.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(apply, 300);
});

const roleOf = (u) => (u.roles.includes('super') ? 'super' : u.roles.includes('admin') ? 'admin' : 'member');
const changing = ref(null);
const confirmChange = () => submit('post', route('admin.users.role', changing.value.id), {}, { onDone: () => (changing.value = null) });
</script>

<template>
    <AdminLayout title="Users & roles" page="users" crumb="Settings">
        <div class="grid stat-grid mb-20 three">
            <div class="stat"><div class="top"><span class="ico ico-blue"><Icon name="users" /></span><span class="label">Accounts</span></div><div class="value">{{ counts.users.toLocaleString() }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-gold"><Icon name="shield" /></span><span class="label">Admins</span></div><div class="value">{{ counts.admins }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-plum"><Icon name="star" /></span><span class="label">Super admins</span></div><div class="value">{{ counts.supers }}</div></div>
        </div>
        <p v-if="roleError" class="card card-pad notice mb-16"><Icon name="info" /> {{ roleError }}</p>
        <section class="card">
            <div class="card-head wrap" style="gap: 12px">
                <h3>Accounts</h3>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 260px"><Icon name="search" /><input v-model="f.search" type="search" placeholder="Name or email…" /></label>
                <select v-model="f.role" class="chip-select" @change="apply"><option value="">Everyone</option><option value="admin">Admins</option><option value="super">Super admins</option></select>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>User</th><th>Member</th><th>Joined</th><th>Role</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id">
                            <td><div class="cell-main"><Avatar :person="u" /><div><div class="t">{{ u.name }}</div><div class="s">{{ u.email }}</div></div></div></td>
                            <td class="small muted">{{ u.member || 'Not linked' }}</td>
                            <td class="small muted">{{ u.joined ? fmtDate(u.joined) : '—' }}</td>
                            <td>
                                <span class="badge" :class="{ super: 'badge-plum', admin: 'badge-gold', member: 'badge-outline' }[roleOf(u)]">
                                    {{ { super: 'Super admin', admin: 'Admin', member: 'Member' }[roleOf(u)] }}
                                </span>
                            </td>
                            <td class="row-actions">
                                <button v-if="roleOf(u) !== 'super' && u.id !== me" class="btn btn-sm" :class="roleOf(u) === 'admin' ? 'btn-soft on-neg' : 'btn-ghost'" @click="changing = u">
                                    {{ roleOf(u) === 'admin' ? 'Revoke admin' : 'Make admin' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-pad" style="border-top: 1px solid var(--line-soft)"><Pager :paging="paging" noun="accounts" /></div>
        </section>
        <ConfirmDialog
            v-if="changing"
            :title="roleOf(changing) === 'admin' ? 'Revoke admin rights' : 'Grant admin rights'"
            :message="roleOf(changing) === 'admin' ? `${changing.name} will lose access to the admin console and attendance sheets.` : `${changing.name} will be able to manage all content, members, registers and cells.`"
            :confirm-label="roleOf(changing) === 'admin' ? 'Revoke' : 'Make admin'"
            :danger="roleOf(changing) === 'admin'"
            @confirm="confirmChange"
            @close="changing = null"
        />
    </AdminLayout>
</template>
