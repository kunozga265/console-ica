<script setup>
import { computed, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Icon from '@/Components/UI/Icon.vue';
import RichEditor from '@/Components/UI/RichEditor.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useSubmit } from '@/Components/Admin/useSubmit';

const props = defineProps({
    sermon: { type: Object, default: null }, // null when creating
    authors: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    ministries: { type: Array, default: () => [] },
});

const pad = (n) => String(n).padStart(2, '0');
const nowLocal = () => {
    const d = new Date();
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const form = reactive({
    title: props.sermon?.title ?? '',
    subtitle: props.sermon?.subtitle ?? '',
    body: props.sermon?.body ?? '',
    authorId: props.sermon?.authorId ?? '',
    seriesId: props.sermon?.seriesId ?? '',
    ministryId: props.sermon?.ministryId ?? props.ministries[0]?.id ?? '',
    videoUrl: props.sermon?.videoUrl ?? '',
    publishedAt: props.sermon?.publishedAt ?? nowLocal(),
});

const { busy, errors, submit } = useSubmit();
const editing = computed(() => !!props.sermon);
const scheduled = computed(() => form.publishedAt && new Date(form.publishedAt) > new Date());

const save = () => {
    const data = { ...form, seriesId: form.seriesId || null, videoUrl: form.videoUrl || null };
    editing.value
        ? submit('put', route('admin.sermons.update', props.sermon.id), data)
        : submit('post', route('admin.sermons.store'), data, { preserveState: false });
};

const deleting = ref(false);
const destroy = () => submit('delete', route('admin.sermons.destroy', props.sermon.id), {}, { onDone: () => (deleting.value = false) });
const restore = () => submit('post', route('admin.sermons.restore', props.sermon.id));
</script>

<template>
    <AdminLayout :title="editing ? 'Edit sermon' : 'New sermon'" page="sermons" crumb="Sermons">
        <template #actions>
            <Link class="btn btn-ghost btn-sm hide-sm" :href="route('admin.sermons.index')">Back</Link>
        </template>

        <div v-if="sermon?.deleted" class="card card-pad notice mb-20">
            <Icon name="info" />
            <div class="grow small">This sermon is deleted — it's hidden from the site and app.</div>
            <button class="btn btn-soft btn-sm" @click="restore"><Icon name="restore" /> Restore</button>
        </div>

        <div class="grid editor-grid">
            <section class="card card-pad">
                <label class="field"><span>Title *</span><input v-model="form.title" type="text" placeholder="e.g. Hidden in Plain Sight" /></label>
                <p v-if="errors.title" class="field-error">{{ errors.title }}</p>
                <label class="field mt-12"><span>Subtitle</span><input v-model="form.subtitle" type="text" placeholder="Optional" /></label>
                <div class="field mt-16">
                    <span>Message *</span>
                    <RichEditor v-model="form.body" placeholder="Write or paste the sermon…" />
                </div>
                <p v-if="errors.body" class="field-error">{{ errors.body }}</p>
            </section>

            <aside class="grid" style="gap: 16px; align-content: start">
                <section class="card card-pad">
                    <h3 class="mb-12" style="font-size: 15px">Publishing</h3>
                    <label class="field">
                        <span>Publish date &amp; time *</span>
                        <input v-model="form.publishedAt" type="datetime-local" />
                    </label>
                    <p class="tiny muted mt-8">
                        <span class="badge" :class="scheduled ? 'badge-blue' : 'badge-green'">{{ scheduled ? 'Scheduled' : 'Published' }}</span>
                        {{ scheduled ? 'Goes live at this time.' : 'Visible on the site now.' }}
                    </p>
                    <p v-if="errors.publishedAt" class="field-error">{{ errors.publishedAt }}</p>
                    <button class="btn btn-primary mt-16" style="width: 100%" :disabled="busy || !form.title || !form.body || !form.authorId" @click="save">
                        {{ busy ? 'Saving…' : editing ? 'Save changes' : 'Create sermon' }}
                    </button>
                    <a v-if="editing && !scheduled && !sermon.deleted" class="btn btn-ghost mt-8" style="width: 100%" :href="route('ui.sermons.show', sermon.id)" target="_blank"><Icon name="eye" /> View on site</a>
                </section>

                <section class="card card-pad">
                    <h3 class="mb-12" style="font-size: 15px">Details</h3>
                    <label class="field">
                        <span>Minister *</span>
                        <select v-model="form.authorId"><option value="" disabled>Choose…</option><option v-for="a in authors" :key="a.id" :value="a.id">{{ a.name }}</option></select>
                    </label>
                    <p v-if="errors.authorId" class="field-error">{{ errors.authorId }}</p>
                    <label class="field mt-12">
                        <span>Series</span>
                        <select v-model="form.seriesId"><option value="">No series</option><option v-for="s in series" :key="s.id" :value="s.id">{{ s.title }}</option></select>
                    </label>
                    <label class="field mt-12">
                        <span>Ministry *</span>
                        <select v-model="form.ministryId"><option v-for="m in ministries" :key="m.id" :value="m.id">{{ m.name }}</option></select>
                    </label>
                    <label class="field mt-12"><span>Video link</span><input v-model="form.videoUrl" type="url" placeholder="https://youtu.be/…" /></label>
                    <p v-if="errors.videoUrl" class="field-error">{{ errors.videoUrl }}</p>
                </section>

                <button v-if="editing && !sermon.deleted" class="btn btn-soft on-neg" @click="deleting = true"><Icon name="trash" /> Delete sermon</button>
            </aside>
        </div>

        <ConfirmDialog v-if="deleting" title="Delete sermon" :message="`Delete “${form.title}”? You can restore it later.`" @confirm="destroy" @close="deleting = false" />
    </AdminLayout>
</template>
