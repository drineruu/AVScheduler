<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout>
        <Head title="Settings" />
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Settings</h2>
            <p class="mt-2 text-sm text-slate-600">
                Congregation name, address, schedule title, and preparation options will be edited here later.
            </p>
        </section>
    </AppLayout>
</template>
