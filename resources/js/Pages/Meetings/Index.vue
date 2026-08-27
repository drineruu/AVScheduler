<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    meetings: Meeting[];
    brothers: Brother[];
    month: string;
}>();

const deleteTarget = ref<Meeting | null>(null);

const brotherNames = computed(() => {
    const map = new Map<number, string>();

    for (const brother of props.brothers) {
        map.set(brother.id, brother.name);
    }

    return map;
});

const monthInput = ref(props.month);

function applyMonthFilter(): void {
    router.get(route('meetings.index'), { month: monthInput.value }, { preserveState: true });
}

function formatDate(date: string): string {
    const parsed = new Date(`${date}T00:00:00`);

    return parsed.toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function busyBrotherLabels(meeting: Meeting): string {
    if (meeting.busy_brothers.length === 0) {
        return 'None';
    }

    return meeting.busy_brothers
        .map((id) => brotherNames.value.get(id) ?? `#${id}`)
        .join(', ');
}

function confirmDelete(meeting: Meeting): void {
    deleteTarget.value = meeting;
}

function cancelDelete(): void {
    deleteTarget.value = null;
}

function deleteMeeting(): void {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(route('meetings.destroy', deleteTarget.value.date), {
        onFinish: () => {
            deleteTarget.value = null;
        },
    });
}

function yesNo(value: boolean): string {
    return value ? 'Yes' : 'No';
}
</script>

<template>
    <AppLayout>
        <Head title="Meetings" />

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Meetings</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Manage meeting dates, skip flags, trainee settings, and busy brothers.
                    </p>
                </div>
                <Link
                    :href="route('meetings.create')"
                    class="inline-flex items-center justify-center rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                >
                    Add Meeting
                </Link>
            </div>

            <form class="mt-6 flex flex-wrap items-end gap-3" @submit.prevent="applyMonthFilter">
                <div>
                    <label for="month" class="block text-sm font-medium text-slate-700">Month</label>
                    <input
                        id="month"
                        v-model="monthInput"
                        type="month"
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
                v-if="meetings.length === 0"
                class="mt-6 rounded-md border border-dashed border-slate-300 p-8 text-center text-sm text-slate-600"
            >
                No meetings found for {{ month }}.
            </div>

            <div v-else class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Date</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Type</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Skip</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Allow Trainee</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Busy Brothers</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="meeting in meetings" :key="meeting.date">
                            <td class="px-3 py-3 font-medium text-slate-900">{{ formatDate(meeting.date) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ meeting.type }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(meeting.skip) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(meeting.allow_trainee) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ busyBrotherLabels(meeting) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex gap-2">
                                    <Link
                                        :href="route('meetings.edit', meeting.date)"
                                        class="text-blue-700 hover:underline"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="text-red-700 hover:underline"
                                        @click="confirmDelete(meeting)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <ConfirmDialog
            :open="deleteTarget !== null"
            title="Delete Meeting"
            :message="`Are you sure you want to delete the meeting on ${deleteTarget ? formatDate(deleteTarget.date) : ''}?`"
            confirm-label="Delete Meeting"
            @confirm="deleteMeeting"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
