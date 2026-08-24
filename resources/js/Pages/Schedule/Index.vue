<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout>
        <Head title="Schedule" />
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Schedule</h2>
            <p class="mt-2 text-sm text-slate-600">
                Monthly audio/video assignments will appear here once scheduling is implemented.
            </p>
        </section>
    </AppLayout>
</template>
