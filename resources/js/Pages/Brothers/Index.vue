<script setup lang="ts">
import AppLayout from '@/Components/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

defineProps<{
    brothers: Brother[];
}>();

const deleteTarget = ref<Brother | null>(null);

function confirmDelete(brother: Brother): void {
    deleteTarget.value = brother;
}

function cancelDelete(): void {
    deleteTarget.value = null;
}

function deleteBrother(): void {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(route('brothers.destroy', deleteTarget.value.id), {
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
        <Head title="Brothers" />

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Brothers</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Manage the brother roster and AV qualifications.
                    </p>
                </div>
                <Link
                    :href="route('brothers.create')"
                    class="inline-flex items-center justify-center rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                >
                    Add Brother
                </Link>
            </div>

            <div v-if="brothers.length === 0" class="mt-6 rounded-md border border-dashed border-slate-300 p-8 text-center text-sm text-slate-600">
                No brothers have been added yet.
            </div>

            <div v-else class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Name</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">MS</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Audio</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Video</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Mic</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Stage</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Preparation</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Training Role</th>
                            <th scope="col" class="px-3 py-2 text-left font-medium text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="brother in brothers" :key="brother.id">
                            <td class="px-3 py-3 font-medium text-slate-900">{{ brother.name }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(brother.is_ms) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(brother.can_audio) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(brother.can_video) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(brother.can_mic) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(brother.can_stage) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ yesNo(brother.can_prep) }}</td>
                            <td class="px-3 py-3 text-slate-700">{{ brother.training_role }}</td>
                            <td class="px-3 py-3">
                                <div class="flex gap-2">
                                    <Link
                                        :href="route('brothers.edit', brother.id)"
                                        class="text-blue-700 hover:underline"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="text-red-700 hover:underline"
                                        @click="confirmDelete(brother)"
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
            title="Delete Brother"
            :message="`Are you sure you want to delete ${deleteTarget?.name ?? 'this brother'}? This action cannot be undone.`"
            confirm-label="Delete Brother"
            @confirm="deleteBrother"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
