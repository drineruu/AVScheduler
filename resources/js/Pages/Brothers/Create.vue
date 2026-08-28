<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import BrotherForm from '@/Components/BrotherForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    trainingRoles: string[];
}>();

const form = useForm({
    name: '',
    is_ms: false,
    can_audio: false,
    can_video: false,
    can_mic: false,
    can_stage: false,
    can_prep: false,
    training_role: 'NONE',
});

function submit(): void {
    form.post(route('brothers.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Add Brother" />

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900">Add Brother</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Enter the brother&apos;s name, qualifications, and training role.
                </p>
            </div>

            <BrotherForm
                :form="form"
                :training-roles="trainingRoles"
                submit-label="Create Brother"
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
