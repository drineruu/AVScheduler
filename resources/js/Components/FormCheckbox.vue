<script setup lang="ts">
defineProps<{
    id: string;
    label: string;
    modelValue: boolean;
    error?: string;
}>();

defineEmits<{
    'update:modelValue': [value: boolean];
}>();
</script>

<template>
    <div>
        <label :for="id" class="flex cursor-pointer items-center gap-3 text-sm text-slate-700">
            <input
                :id="id"
                type="checkbox"
                class="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500"
                :checked="modelValue"
                @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
            />
            <span>{{ label }}</span>
        </label>
        <p v-if="error" class="mt-1 text-sm text-red-600" role="alert">
            {{ error }}
        </p>
    </div>
</template>
