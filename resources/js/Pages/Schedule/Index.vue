<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import ScheduleTable from '@/Components/ScheduleTable.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Brother = {
    id: number;
    name: string;
};

type ScheduleRow = {
    date: string;
    audio: number | null;
    video: number | null;
    mics: number | null;
    stage: number | null;
    preparation: number | null;
};

type Settings = {
    congregation: string;
    address: string;
    title: string;
    include_preparation: boolean;
};

type Warning = {
    date: string;
    role: string;
    message: string;
};

const props = defineProps<{
    schedule: ScheduleRow[];
    brothers: Brother[];
    settings: Settings;
    startDate: string;
    endDate: string;
    hasSavedSchedule: boolean;
    warnings: Warning[];
}>();

const startDateInput = ref(props.startDate);
const endDateInput = ref(props.endDate);

const form = useForm({
    start_date: props.startDate,
    end_date: props.endDate,
});

function applyDateFilter(): void {
    router.get(
        route('schedule.index'),
        { start_date: startDateInput.value, end_date: endDateInput.value },
        { preserveState: true },
    );
}

function generateSchedule(): void {
    form.start_date = startDateInput.value;
    form.end_date = endDateInput.value;
    form.post(route('schedule.generate'));
}
</script>

<template>
    <AppLayout>
        <Head title="Schedule" />

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ settings.title }}</h2>
                    <p v-if="settings.congregation" class="mt-1 text-sm text-slate-600">
                        {{ settings.congregation }}
                    </p>
                    <p v-if="settings.address" class="text-sm text-slate-600">
                        {{ settings.address }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
                        :disabled="form.processing"
                        @click="generateSchedule"
                    >
                        {{ hasSavedSchedule ? 'Regenerate Schedule' : 'Generate Schedule' }}
                    </button>
                    <a
                        :href="route('schedule.pdf', { start_date: startDateInput, end_date: endDateInput })"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Export PDF
                    </a>
                </div>
            </div>

            <form class="mt-6 flex flex-wrap items-end gap-3" @submit.prevent="applyDateFilter">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-slate-700">Start date</label>
                    <input
                        id="start_date"
                        v-model="startDateInput"
                        type="date"
                        class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-slate-700">End date</label>
                    <input
                        id="end_date"
                        v-model="endDateInput"
                        type="date"
                        class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    />
                </div>
                <button
                    type="submit"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Filter
                </button>
            </form>

            <div
                v-if="warnings.length > 0"
                class="mt-6 space-y-2 rounded-md border border-amber-200 bg-amber-50 p-4"
                role="alert"
            >
                <h3 class="text-sm font-semibold text-amber-900">Schedule Warnings</h3>
                <ul class="space-y-1 text-sm text-amber-800">
                    <li v-for="(warning, index) in warnings" :key="`${warning.date}-${warning.role}-${index}`">
                        {{ warning.message }}
                    </li>
                </ul>
            </div>

            <div class="mt-6">
                <ScheduleTable
                    :schedule="schedule"
                    :brothers="brothers"
                    :include-preparation="settings.include_preparation"
                />
            </div>
        </section>
    </AppLayout>
</template>
