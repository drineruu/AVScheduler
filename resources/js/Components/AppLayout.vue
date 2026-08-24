<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import FlashMessage from './FlashMessage.vue';

type NavItem = {
    label: string;
    name: string;
};

const navItems: NavItem[] = [
    { label: 'Schedule', name: 'schedule.index' },
    { label: 'Meetings', name: 'meetings.index' },
    { label: 'Brothers', name: 'brothers.index' },
    { label: 'Settings', name: 'settings.index' },
];
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                        Congregation AV Support
                    </p>
                    <h1 class="text-xl font-semibold text-slate-900 sm:text-2xl">
                        Audio/Video Scheduler
                    </h1>
                </div>
                <nav aria-label="Main" class="flex flex-wrap gap-2">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="route(item.name)"
                        class="rounded-md px-3 py-2 text-sm font-medium"
                        :class="
                            route().current(item.name)
                                ? 'bg-blue-700 text-white'
                                : 'text-slate-700 hover:bg-slate-100'
                        "
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <FlashMessage />
            <slot />
        </main>
    </div>
</template>
