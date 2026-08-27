<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import BrotherForm from '@/Components/BrotherForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type Brother = {
    id: number;
    name: string;
    is_ms: boolean;
    can_audio: boolean;
    can_video: boolean;
    can_mic: boolean;
    can_stage: boolean;
    can_prep: boolean;
    training_role: string;
};

const props = defineProps<{
    brother: Brother;
    trainingRoles: string[];
}>();

const form = useForm({
    name: props.brother.name,
    is_ms: props.brother.is_ms,
    can_audio: props.brother.can_audio,
    can_video: props.brother.can_video,
    can_mic: props.brother.can_mic,
    can_stage: props.brother.can_stage,
    can_prep: props.brother.can_prep,
    training_role: props.brother.training_role,
});

function submit(): void {
    form.put(route('brothers.update', props.brother.id));
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit ${brother.name}`" />

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">Edit Brother</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Update qualifications and training role for {{ brother.name }}.
                </p>
            </div>

            <BrotherForm
                :form="form"
                :training-roles="trainingRoles"
                submit-label="Save Changes"
                @submit="submit"
            />

            <div class="mt-6">
                <Link :href="route('brothers.index')" class="text-sm text-blue-700 hover:underline">
                    Back to Brothers
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
