<script setup>
import { Dialog } from 'primevue';
import AppModal from './AppModal.vue';
import Divider from 'primevue/divider';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <AppModal :show="show" :max-width="maxWidth" @close="close">
        <div class="grid gap-1.5 pt-4 text-center sm:text-left">
            <div class="mx-auto w-full max-w-none">
                <div class="relative py-4 px-10">
                    <div class="absolute right-0">
                        <button @click="close"
                            class="relative inline-flex items-center justify-center whitespace-nowrap rounded-lg border font-medium no-underline ring-offset-background transition-all duration-300 disabled:pointer-events-none disabled:cursor-not-allowed disabled:!opacity-50 border-transparent outline-none focus-within:outline-none focus-visible:ring-2 focus-visible:ring-control-transparent focus-visible:ring-offset-2 focus-within:ring-2 focus-within:ring-control-active focus-within:ring-offset-2 active:ring-2 active:ring-control-active active:ring-offset-2 aria-invalid:!ring-invalid aria-invalid:!ring-2 aria-invalid:!ring-offset-2 hover:bg-base-muted ring-base-muted-active text-base-foreground h-12 gap-2 px-6 py-1 text-md"
                            data-testid="button-default" loading="false" type="button" tabindex="0"
                            aria-label="Close support ticket drawer"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-xicon">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg><!----></button>
                    </div>
                    <h2 class="text-3xl text-center font-medium title-font">
                        <slot name="title" />
                    </h2>

                </div>
            </div>
        </div>

        <div class="mx-auto max-h-[75vh] w-full overflow-auto">
            <div class="py-1 mx-auto w-full max-w-none">
                <div class="mx-auto space-y-4 w-full max-w-[640px] py-6 px-4">
                    <slot name="content" />
                </div>
            </div>
        </div>
<Divider/>
        <div class="mt-auto py-4 flex flex-col gap-2 mx-auto w-full max-w-none " data-testid="drawer-footer">
            <div class="mx-auto w-full max-w-[640px] px-4 flex flex-col sm:flex-row gap-2 justify-end items-center">
                <slot name="footer" />
            </div><!---->
        </div>

       
    </AppModal>
</template>
