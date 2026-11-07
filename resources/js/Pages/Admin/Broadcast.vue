<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

defineProps({
    ministries: { type: Array, default: () => [] },
    cells: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] }, // the last 20 sent
});

const { busy, errors, submit } = useSubmit();
const blank = () => ({ title: '', body: '', audience: 'everyone', ministry: null, cell: null });
const f = ref(blank());
const send = () => submit('post', route('admin.broadcasts.store'), f.value, { onDone: () => (f.value = blank()) });
</script>

<template>
    <AdminLayout title="Send notification" page="broadcasts" crumb="Engagement">
        <div class="grid receipts-grid" style="align-items: start">
            <section class="card card-pad">
                <h3 style="font-size: 16px">New notification</h3>
                <p class="small muted mt-4 mb-12">Sent straight to phones with the ICA app. People can turn general notifications off in their settings.</p>
                <label class="field"><span>Title</span><input v-model="f.title" maxlength="80" placeholder="e.g. Service time change this Sunday" /></label>
                <label class="field mt-8"><span>Message</span><textarea v-model="f.body" rows="3" maxlength="240" placeholder="Keep it short — it shows on the lock screen."></textarea></label>
                <div class="tiny muted mt-4">{{ f.body.length }}/240</div>
                <div class="form-grid mt-8">
                    <label class="field"><span>Send to</span>
                        <select v-model="f.audience">
                            <option value="everyone">Everyone</option>
                            <option value="ministry">A ministry's followers</option>
                            <option value="cell">A homecell</option>
                        </select>
                    </label>
                    <label v-if="f.audience === 'ministry'" class="field"><span>Ministry</span>
                        <select v-model="f.ministry"><option v-for="m in ministries" :key="m.id" :value="m.id">{{ m.name }}</option></select>
                    </label>
                    <label v-if="f.audience === 'cell'" class="field"><span>Homecell</span>
                        <select v-model="f.cell"><option v-for="c in cells" :key="c.code" :value="c.code">{{ c.name }}</option></select>
                    </label>
                </div>
                <p v-for="e in [errors.title, errors.body, errors.audience, errors.ministry, errors.cell].filter(Boolean)" :key="e" class="field-error">{{ e }}</p>

                <div class="push-preview mt-16">
                    <div class="tiny muted caps mb-4">Preview</div>
                    <div class="card card-pad" style="display: flex; gap: 10px; align-items: flex-start">
                        <span class="ico ico-blue" style="width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; flex: none"><Icon name="bell" /></span>
                        <div><b class="small">{{ f.title || 'Title' }}</b><div class="small muted">{{ f.body || 'Your message' }}</div></div>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm mt-16" :disabled="busy || !f.title.trim() || !f.body.trim()" @click="send">{{ busy ? 'Sending…' : 'Send notification' }}</button>
            </section>

            <section class="card">
                <div class="card-head"><h3 style="font-size: 16px">Recently sent</h3></div>
                <div v-for="(h, i) in history" :key="i" class="list-row" style="align-items: flex-start">
                    <div class="grow">
                        <div class="t">{{ h.title }}</div>
                        <div class="s" style="white-space: normal">{{ h.body }}</div>
                        <div class="tiny muted mt-4">{{ h.audience }} · {{ h.by }} · {{ fmtDate(h.at) }}</div>
                    </div>
                </div>
                <p v-if="!history.length" class="card-pad small muted">Nothing sent yet.</p>
            </section>
        </div>
    </AdminLayout>
</template>
