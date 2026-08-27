<script setup lang="ts">
defineProps<{
    id: string;
    label: string;
    modelValue: string;
    options: string[];
    error?: string;
    required?: boolean;
}>();

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div>
        <label :for="id" class="block text-sm font-medium text-slate-700">
            {{ label }}
            <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
        </label>
        <select
            :id="id"
            :value="modelValue"
            :required="required"
            class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2"
            :class="
                error
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-200'
                    : 'border-slate-300 focus:border-blue-500 focus:ring-blue-200'
            "
            @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option v-for="option in options" :key="option" :value="option">
                {{ option }}
            </option>
        </select>
        <p v-if="error" class="mt-1 text-sm text-red-600" role="alert">
            {{ error }}
        </p>
    </div>
</template>
