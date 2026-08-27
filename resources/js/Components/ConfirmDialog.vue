<script setup lang="ts">
defineProps<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
}>();

defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`${title}-title`"
    >
        <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-xl">
            <h2 :id="`${title}-title`" class="text-lg font-semibold text-slate-900">
                {{ title }}
            </h2>
            <p class="mt-2 text-sm text-slate-600">
                {{ message }}
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="$emit('cancel')"
                >
                    {{ cancelLabel ?? 'Cancel' }}
                </button>
                <button
                    type="button"
                    class="rounded-md bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800"
                    @click="$emit('confirm')"
                >
                    {{ confirmLabel ?? 'Delete' }}
                </button>
            </div>
        </div>
    </div>
</template>
