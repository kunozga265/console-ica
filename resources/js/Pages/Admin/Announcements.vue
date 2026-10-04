<script setup>
import { reactive } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import RichEditor from '@/Components/UI/RichEditor.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';
import { fmtDate } from '@/Components/UI/helpers';

const props = defineProps({
    body: { type: String, default: '' },
    active: { type: Boolean, default: false },
    updated: { type: Number, default: null },
});

const form = reactive({ body: props.body, active: props.active });
const { busy, firstError, submit } = useSubmit();
const save = () => submit('put', route('admin.announcements.update'), { ...form });
</script>

<template>
    <AdminLayout title="Announcements" page="announcements" crumb="Engagement">
        <div class="grid editor-grid">
            <section class="card card-pad">
                <div class="field">
                    <span>Announcements</span>
                    <RichEditor v-model="form.body" placeholder="This week at ICA…" />
                </div>
                <p v-if="firstError" class="field-error">{{ firstError }}</p>
            </section>
            <aside class="grid" style="gap: 16px; align-content: start">
                <section class="card card-pad">
                    <h3 class="mb-12" style="font-size: 15px">Visibility</h3>
                    <label class="switch-row">
                        <input v-model="form.active" type="checkbox" class="rowcheck" />
                        <span><b>Show on the site</b><br /><span class="tiny muted">The site and app show these announcements while this is on.</span></span>
                    </label>
                    <button class="btn btn-primary mt-16" style="width: 100%" :disabled="busy" @click="save">{{ busy ? 'Saving…' : 'Save announcements' }}</button>
                    <p v-if="updated" class="tiny muted mt-8">Last updated {{ fmtDate(updated) }}</p>
                </section>
                <section class="card card-pad small muted"><Icon name="info" /> Keep it short — the latest news for this week. Older items can simply be deleted.</section>
            </aside>
        </div>
    </AdminLayout>
</template>
