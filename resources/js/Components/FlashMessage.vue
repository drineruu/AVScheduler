<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

type Flash = {
    success?: string | null;
    error?: string | null;
};

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as Flash);
</script>

<template>
    <div v-if="flash.success || flash.error" class="mb-6 space-y-3">
        <div
            v-if="flash.success"
            class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            role="status"
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash.error"
            class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            role="alert"
        >
            {{ flash.error }}
        </div>
    </div>
</template>
