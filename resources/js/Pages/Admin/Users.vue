<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Modal from '@/Components/UI/Modal.vue';
import Pager from '@/Components/Admin/Pager.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    users: { type: Array, default: () => [] },
    scopeOptions: { type: Array, default: () => [] }, // [{ key, label, hint }]
    paging: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
});

const page = usePage();
const me = computed(() => page.props.auth?.user?.id);
const scopeError = computed(() => page.props.errors?.scopes);
const { busy, submit } = useSubmit();

const f = ref({ search: props.filters.search ?? '', access: props.filters.access ?? '' });
const apply = () => router.get(route('admin.users.index'), Object.fromEntries(Object.entries(f.value).filter(([, v]) => v)), { preserveState: true, replace: true });
let debounce;
watch(() => f.value.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(apply, 300);
});

const label = (key) => props.scopeOptions.find((o) => o.key === key)?.label ?? key;

/* ---- Scope editor ---- */
const editing = ref(null);
const draft = ref([]);
const edit = (u) => {
    editing.value = u;
    draft.value = [...u.scopes];
};
const toggle = (key) => (draft.value = draft.value.includes(key) ? draft.value.filter((k) => k !== key) : [...draft.value, key]);
const save = () => submit('put', route('admin.users.scopes', editing.value.id), { scopes: draft.value }, { onDone: () => (editing.value = null) });
</script>

<template>
    <AdminLayout title="Users & access" page="users" crumb="Settings">
        <div class="grid stat-grid mb-20 three">
            <div class="stat"><div class="top"><span class="ico ico-blue"><Icon name="users" /></span><span class="label">Accounts</span></div><div class="value">{{ counts.users.toLocaleString() }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-gold"><Icon name="shield" /></span><span class="label">With scopes</span></div><div class="value">{{ counts.scoped }}</div></div>
            <div class="stat"><div class="top"><span class="ico ico-plum"><Icon name="star" /></span><span class="label">Super admins</span></div><div class="value">{{ counts.supers }}</div></div>
        </div>
        <p v-if="scopeError" class="card card-pad notice mb-16"><Icon name="info" /> {{ scopeError }}</p>
        <section class="card">
            <div class="card-head wrap" style="gap: 12px">
                <h3>Accounts</h3>
                <div class="spacer"></div>
                <label class="searchbar" style="max-width: 260px"><Icon name="search" /><input v-model="f.search" type="search" placeholder="Name or email…" /></label>
                <select v-model="f.access" class="chip-select" @change="apply">
                    <option value="">Everyone</option>
                    <option value="scoped">Has scopes</option>
                    <option value="super">Super admins</option>
                </select>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>User</th><th>Member</th><th>Joined</th><th>Can manage</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id">
                            <td><div class="cell-main"><Avatar :person="u" /><div><div class="t">{{ u.name }}</div><div class="s">{{ u.email }}</div></div></div></td>
                            <td class="small muted">{{ u.member || 'Not linked' }}</td>
                            <td class="small muted">{{ u.joined ? fmtDate(u.joined) : '—' }}</td>
                            <td>
                                <span v-if="u.super" class="badge badge-plum">Super admin · everything</span>
                                <div v-else-if="u.scopes.length" class="scope-chips">
                                    <span v-for="s in u.scopes" :key="s" class="badge badge-gold">{{ label(s) }}</span>
                                </div>
                                <span v-else class="small muted">—</span>
                            </td>
                            <td class="row-actions">
                                <button v-if="!u.super && u.id !== me" class="btn btn-ghost btn-sm" @click="edit(u)"><Icon name="shield" /> Access</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-pad" style="border-top: 1px solid var(--line-soft)"><Pager :paging="paging" noun="accounts" /></div>
        </section>

        <Modal v-if="editing" :title="`What can ${editing.name} manage?`" width="560px" @close="editing = null">
            <p class="small muted mb-12">Each scope opens that section of the admin console (and the matching tools in the app). Super admins always have everything.</p>
            <div class="scope-list">
                <label v-for="o in scopeOptions" :key="o.key" class="scope-option" :class="{ on: draft.includes(o.key) }">
                    <input type="checkbox" :checked="draft.includes(o.key)" @change="toggle(o.key)" />
                    <span><b>{{ o.label }}</b><span class="small muted">{{ o.hint }}</span></span>
                </label>
            </div>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="draft = []">Clear all</button>
                <div class="flex gap-8">
                    <button class="btn btn-ghost btn-sm" @click="editing = null">Cancel</button>
                    <button class="btn btn-primary btn-sm" :disabled="busy" @click="save">Save access</button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
