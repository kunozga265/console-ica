<script setup>
import { reactive, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import Modal from '@/Components/UI/Modal.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';

defineProps({ options: { type: Array, default: () => [] } });

const { busy, firstError, submit } = useSubmit();
const editing = ref(null);
const form = reactive({ type: 'bank', name: '', account_name: '', account_number: '', branch: '', swift_code: '', instructions: '', sort_order: 0, active: true });
const open = (o = null, type = 'bank') => {
    Object.assign(form, {
        type: o?.type ?? type, name: o?.name ?? '', account_name: o?.account_name ?? '', account_number: o?.account_number ?? '',
        branch: o?.branch ?? '', swift_code: o?.swift_code ?? '', instructions: o?.instructions ?? '', sort_order: o?.sort_order ?? 0, active: o?.active ?? true,
    });
    editing.value = o ?? {};
};
const save = () =>
    editing.value.id
        ? submit('put', route('admin.giving.update', editing.value.id), { ...form }, { onDone: () => (editing.value = null) })
        : submit('post', route('admin.giving.store'), { ...form }, { onDone: () => (editing.value = null) });
const deleting = ref(null);
const destroy = () => submit('delete', route('admin.giving.destroy', deleting.value.id), {}, { onDone: () => (deleting.value = null) });
const toggle = (o) => submit('put', route('admin.giving.update', o.id), { ...o, active: !o.active });
</script>

<template>
    <AdminLayout title="Giving Options" page="giving" crumb="Engagement">
        <div class="between wrap mb-20" style="gap: 12px">
            <p class="muted small">Shown on the site's Give page. Only active options appear.</p>
            <div class="flex gap-8">
                <a class="btn btn-ghost btn-sm" :href="route('ui.give')" target="_blank"><Icon name="eye" /> Preview</a>
                <button class="btn btn-soft btn-sm" @click="open(null, 'mobile')"><Icon name="phone" /> Add mobile money</button>
                <button class="btn btn-primary btn-sm" @click="open(null, 'bank')"><Icon name="bank" /> Add bank account</button>
            </div>
        </div>
        <div v-if="options.length" class="grid cols-2">
            <article v-for="o in options" :key="o.id" class="card card-pad" :class="{ 'is-off': !o.active }">
                <div class="between">
                    <div class="flex" style="gap: 12px">
                        <span class="ico ico-box" :class="o.type === 'bank' ? 'ico-blue' : 'ico-gold'"><Icon :name="o.type === 'bank' ? 'bank' : 'phone'" /></span>
                        <div><h3 style="font-size: 16px">{{ o.name }}</h3><div class="small muted">{{ o.type === 'bank' ? 'Bank transfer' : 'Mobile money' }}</div></div>
                    </div>
                    <span class="badge" :class="o.active ? 'badge-green' : 'badge-outline'">{{ o.active ? 'Active' : 'Hidden' }}</span>
                </div>
                <dl class="kv mt-16">
                    <template v-if="o.account_name"><dt>{{ o.type === 'bank' ? 'Account name' : 'Merchant' }}</dt><dd>{{ o.account_name }}</dd></template>
                    <dt>{{ o.type === 'bank' ? 'Account number' : 'Pay to number' }}</dt><dd>{{ o.account_number }}</dd>
                    <template v-if="o.branch"><dt>Branch</dt><dd>{{ o.branch }}</dd></template>
                    <template v-if="o.swift_code"><dt>Swift / BIC</dt><dd>{{ o.swift_code }}</dd></template>
                </dl>
                <div class="flex gap-8 mt-16">
                    <button class="btn btn-soft btn-sm" @click="open(o)"><Icon name="pencil" /> Edit</button>
                    <button class="btn btn-soft btn-sm" :disabled="busy" @click="toggle(o)">{{ o.active ? 'Hide' : 'Show' }}</button>
                    <button class="btn btn-soft btn-sm btn-icon" aria-label="Delete" @click="deleting = o"><Icon name="trash" /></button>
                </div>
            </article>
        </div>
        <div v-else class="card card-pad empty-state"><Icon name="gift" /><h3>No giving options yet</h3><p class="small muted">The Give page shows “coming soon” until you add one.</p></div>

        <Modal v-if="editing" :title="`${editing.id ? 'Edit' : 'Add'} ${form.type === 'bank' ? 'bank account' : 'mobile money'}`" width="560px" @close="editing = null">
            <div class="segmented mb-12">
                <button :class="{ on: form.type === 'bank' }" @click="form.type = 'bank'">Bank</button>
                <button :class="{ on: form.type === 'mobile' }" @click="form.type = 'mobile'">Mobile money</button>
            </div>
            <div class="form-grid">
                <label class="field"><span>{{ form.type === 'bank' ? 'Bank *' : 'Provider *' }}</span><input v-model="form.name" type="text" :placeholder="form.type === 'bank' ? 'e.g. National Bank of Malawi' : 'e.g. Airtel Money'" /></label>
                <label class="field"><span>{{ form.type === 'bank' ? 'Account name' : 'Merchant name' }}</span><input v-model="form.account_name" type="text" /></label>
                <label class="field"><span>{{ form.type === 'bank' ? 'Account number *' : 'Number to pay *' }}</span><input v-model="form.account_number" type="text" /></label>
                <template v-if="form.type === 'bank'">
                    <label class="field"><span>Branch</span><input v-model="form.branch" type="text" /></label>
                    <label class="field"><span>Swift / BIC</span><input v-model="form.swift_code" type="text" /></label>
                </template>
                <label class="field"><span>Order</span><input v-model="form.sort_order" type="number" min="0" /></label>
            </div>
            <label class="field mt-12"><span>How to give (one step per line, optional)</span><textarea v-model="form.instructions" rows="4"></textarea></label>
            <label class="flex mt-12 small" style="gap: 9px; cursor: pointer"><input v-model="form.active" type="checkbox" class="rowcheck" /> Show on the Give page</label>
            <p v-if="firstError" class="field-error">{{ firstError }}</p>
            <template #footer>
                <button class="btn btn-soft btn-sm" @click="editing = null">Cancel</button>
                <button class="btn btn-primary btn-sm" :disabled="busy || !form.name || !form.account_number" @click="save">{{ busy ? 'Saving…' : 'Save' }}</button>
            </template>
        </Modal>
        <ConfirmDialog v-if="deleting" title="Delete giving option" :message="`Delete ${deleting.name} (${deleting.account_number})?`" @confirm="destroy" @close="deleting = null" />
    </AdminLayout>
</template>
