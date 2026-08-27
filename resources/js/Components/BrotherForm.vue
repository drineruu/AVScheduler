<script setup lang="ts">
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import type { InertiaForm } from '@inertiajs/vue3';

export type BrotherFormData = {
    name: string;
    is_ms: boolean;
    can_audio: boolean;
    can_video: boolean;
    can_mic: boolean;
    can_stage: boolean;
    can_prep: boolean;
    training_role: string;
};

defineProps<{
    form: InertiaForm<BrotherFormData>;
    trainingRoles: string[];
    submitLabel: string;
}>();

defineEmits<{
    submit: [];
}>();
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <FormInput
            id="name"
            label="Name"
            required
            :model-value="form.name"
            :error="form.errors.name"
            @update:model-value="form.name = $event"
        />

        <fieldset class="space-y-3 rounded-md border border-slate-200 p-4">
            <legend class="px-1 text-sm font-medium text-slate-900">Qualifications</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <FormCheckbox
                    id="is_ms"
                    label="Ministerial Servant (MS)"
                    :model-value="form.is_ms"
                    :error="form.errors.is_ms"
                    @update:model-value="form.is_ms = $event"
                />
                <FormCheckbox
                    id="can_audio"
                    label="Audio"
                    :model-value="form.can_audio"
                    :error="form.errors.can_audio"
                    @update:model-value="form.can_audio = $event"
                />
                <FormCheckbox
                    id="can_video"
                    label="Video"
                    :model-value="form.can_video"
                    :error="form.errors.can_video"
                    @update:model-value="form.can_video = $event"
                />
                <FormCheckbox
                    id="can_mic"
                    label="Microphone"
                    :model-value="form.can_mic"
                    :error="form.errors.can_mic"
                    @update:model-value="form.can_mic = $event"
                />
                <FormCheckbox
                    id="can_stage"
                    label="Stage"
                    :model-value="form.can_stage"
                    :error="form.errors.can_stage"
                    @update:model-value="form.can_stage = $event"
                />
                <FormCheckbox
                    id="can_prep"
                    label="Preparation"
                    :model-value="form.can_prep"
                    :error="form.errors.can_prep"
                    @update:model-value="form.can_prep = $event"
                />
            </div>
        </fieldset>

        <FormSelect
            id="training_role"
            label="Training Role"
            required
            :options="trainingRoles"
            :model-value="form.training_role"
            :error="form.errors.training_role"
            @update:model-value="form.training_role = $event"
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
