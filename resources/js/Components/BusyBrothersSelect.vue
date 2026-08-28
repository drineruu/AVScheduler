<script setup lang="ts">
type BrotherOption = {
    id: number;
    name: string;
};

defineProps<{
    id: string;
    label: string;
    modelValue: number[];
    brothers: BrotherOption[];
    error?: string;
}>();

defineEmits<{
    'update:modelValue': [value: number[]];
}>();

function toggleBrother(current: number[], brotherId: number, checked: boolean): number[] {
    if (checked) {
        return [...current, brotherId].sort((a, b) => a - b);
    }

    return current.filter((id) => id !== brotherId);
}
</script>

<template>
    <div>
        <p :id="`${id}-label`" class="block text-sm font-medium text-slate-700">
            {{ label }}
        </p>
        <div
            class="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-md border border-slate-300 p-3"
            role="group"
            :aria-labelledby="`${id}-label`"
        >
            <p v-if="brothers.length === 0" class="text-sm text-slate-500">
                No brothers available. Add brothers first.
            </p>
            <label
                v-for="brother in brothers"
                :key="brother.id"
                :for="`${id}-${brother.id}`"
                class="flex cursor-pointer items-center gap-3 text-sm text-slate-700"
            >
                <input
                    :id="`${id}-${brother.id}`"
                    type="checkbox"
                    class="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500"
                    :checked="modelValue.includes(brother.id)"
                    @change="
                        $emit(
                            'update:modelValue',
                            toggleBrother(modelValue, brother.id, ($event.target as HTMLInputElement).checked),
                        )
                    "
                />
                <span>{{ brother.name }}</span>
            </label>
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600" role="alert">
            {{ error }}
        </p>
    </div>
</template>
