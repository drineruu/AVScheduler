<script setup lang="ts">
import BusyBrothersSelect from '@/Components/BusyBrothersSelect.vue';
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';
import { meetingTypeFromDate } from '@/utils/meetingType';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

type BrotherOption = {
    id: number;
    name: string;
};

export type MeetingFormData = {
    date: string;
    skip: boolean;
    allow_trainee: boolean;
    busy_brothers: number[];
};

const props = defineProps<{
    form: InertiaForm<MeetingFormData>;
    brothers: BrotherOption[];
    submitLabel: string;
    showDate?: boolean;
    weekendDays: number[];
    meetingType?: string | null;
}>();

defineEmits<{
    submit: [];
}>();

const resolvedMeetingType = computed(() => {
    if (props.meetingType) {
        return props.meetingType;
    }

    return meetingTypeFromDate(props.form.date, props.weekendDays);
});
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <FormInput
            v-if="showDate"
            id="date"
            label="Date"
            type="date"
            required
            :model-value="form.date"
            :error="form.errors.date"
            @update:model-value="form.date = $event"
        />

        <div v-if="resolvedMeetingType" class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <span class="font-medium text-slate-900">Meeting type:</span>
            {{ resolvedMeetingType }}
            <span class="mt-1 block text-xs text-slate-500">
                Derived from the date (Saturday/Sunday = Weekend).
            </span>
        </div>

        <FormCheckbox
            id="skip"
            label="Skip this meeting (no AV assignments)"
            :model-value="form.skip"
            :error="form.errors.skip"
            @update:model-value="form.skip = $event"
        />

        <FormCheckbox
            id="allow_trainee"
            label="Allow trainee assignments"
            :model-value="form.allow_trainee"
            :error="form.errors.allow_trainee"
            @update:model-value="form.allow_trainee = $event"
        />

        <BusyBrothersSelect
            id="busy_brothers"
            label="Busy Brothers"
            :brothers="brothers"
            :model-value="form.busy_brothers"
            :error="form.errors.busy_brothers"
            @update:model-value="form.busy_brothers = $event"
        />

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
                :disabled="form.processing"
            >
                {{ submitLabel }}
            </button>
            <span v-if="form.processing" class="text-sm text-slate-500">Saving...</span>
        </div>
    </form>
</template>
