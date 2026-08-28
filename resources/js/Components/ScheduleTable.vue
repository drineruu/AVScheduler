<script setup lang="ts">
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

const props = defineProps<{
    schedule: ScheduleRow[];
    brothers: Brother[];
    includePreparation: boolean;
}>();

const brotherNames = new Map(props.brothers.map((brother) => [brother.id, brother.name]));

function formatDate(date: string): string {
    const parsed = new Date(`${date}T00:00:00`);

    return parsed.toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function assignmentLabel(brotherId: number | null): string {
    if (brotherId === null) {
        return 'Unassigned';
    }

    return brotherNames.get(brotherId) ?? `#${brotherId}`;
}

function assignmentClass(brotherId: number | null): string {
    return brotherId === null
        ? 'font-medium text-red-700'
        : 'text-slate-700';
}
</script>

<template>
    <div v-if="schedule.length === 0" class="rounded-md border border-dashed border-slate-300 p-8 text-center text-sm text-slate-600">
        No schedule entries to display.
    </div>

    <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Date</th>
                    <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Audio</th>
                    <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Video</th>
                    <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Mics</th>
                    <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Stage</th>
                    <th v-if="includePreparation" scope="col" class="px-3 py-2 text-left font-medium text-slate-700">
                        Preparation
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <tr v-for="row in schedule" :key="row.date">
                    <td class="px-3 py-3 font-medium text-slate-900">{{ formatDate(row.date) }}</td>
                    <td class="px-3 py-3" :class="assignmentClass(row.audio)">{{ assignmentLabel(row.audio) }}</td>
                    <td class="px-3 py-3" :class="assignmentClass(row.video)">{{ assignmentLabel(row.video) }}</td>
                    <td class="px-3 py-3" :class="assignmentClass(row.mics)">{{ assignmentLabel(row.mics) }}</td>
                    <td class="px-3 py-3" :class="assignmentClass(row.stage)">{{ assignmentLabel(row.stage) }}</td>
                    <td
                        v-if="includePreparation"
                        class="px-3 py-3"
                        :class="assignmentClass(row.preparation)"
                    >
                        {{ assignmentLabel(row.preparation) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
