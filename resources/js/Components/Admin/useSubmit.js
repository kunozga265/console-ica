import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/*
 * Shared form submission for admin pages: Inertia visit that keeps scroll and
 * state, exposes busy/errors, and calls onDone only when validation passed.
 * Success toasts come from the server's flash message (AdminLayout).
 */
export function useSubmit() {
    const page = usePage();
    const busy = ref(false);
    const errors = computed(() => page.props.errors ?? {});
    const firstError = computed(() => Object.values(errors.value)[0] ?? null);

    const submit = (method, url, data = {}, { onDone, forceFormData = false, preserveState = true } = {}) => {
        busy.value = true;
        router[method](url, data, {
            preserveScroll: true,
            preserveState,
            forceFormData,
            onSuccess: () => {
                if (!Object.keys(page.props.errors ?? {}).length) onDone?.();
            },
            onFinish: () => (busy.value = false),
        });
    };

    return { busy, errors, firstError, submit };
}
