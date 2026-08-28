<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import MeetingForm from '@/Components/MeetingForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type Brother = {
    id: number;
    name: string;
};

type Meeting = {
    date: string;
    skip: boolean;
    allow_trainee: boolean;
    busy_brothers: number[];
    type: string;
};

const props = defineProps<{
    meeting: Meeting;
    brothers: Brother[];
    weekendDays: number[];
}>();

const form = useForm({
    date: props.meeting.date,
    skip: props.meeting.skip,
    allow_trainee: props.meeting.allow_trainee,
    busy_brothers: [...props.meeting.busy_brothers],
});

function submit(): void {
    form.put(route('meetings.update', props.meeting.date));
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Meeting ${meeting.date}`" />

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">Edit Meeting</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Update settings for {{ meeting.date }}.
                </p>
            </div>

            <MeetingForm
                :form="form"
                :brothers="brothers"
                :weekend-days="weekendDays"
                :meeting-type="meeting.type"
                submit-label="Save Changes"
                @submit="submit"
            />

            <div class="mt-6">
                <Link
                    :href="route('meetings.index', { month: meeting.date.slice(0, 7) })"
                    class="text-sm text-blue-700 hover:underline"
                >
                    Back to Meetings
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
