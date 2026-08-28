<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import MeetingForm from '@/Components/MeetingForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type Brother = {
    id: number;
    name: string;
};

defineProps<{
    brothers: Brother[];
    weekendDays: number[];
}>();

const form = useForm({
    date: '',
    skip: false,
    allow_trainee: true,
    busy_brothers: [] as number[],
});

function submit(): void {
    form.post(route('meetings.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Add Meeting" />

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">Add Meeting</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Create a meeting date with skip, trainee, and busy brother settings.
                </p>
            </div>

            <MeetingForm
                :form="form"
                :brothers="brothers"
                :weekend-days="weekendDays"
                show-date
                submit-label="Create Meeting"
                @submit="submit"
            />

            <div class="mt-6">
                <Link :href="route('meetings.index')" class="text-sm text-blue-700 hover:underline">
                    Back to Meetings
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
