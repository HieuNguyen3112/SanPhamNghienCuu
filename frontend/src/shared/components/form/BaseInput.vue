<template>
  <div class="space-y-1">
    <label
      v-if="label"
      :for="id"
      class="block text-sm font-medium text-gray-800"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :autocomplete="autocomplete"
      :disabled="disabled"
      class="w-full rounded-xl border border-white/70 bg-white/80 px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:cursor-not-allowed disabled:opacity-60"
      :class="inputClass"
      :aria-invalid="hasError ? 'true' : 'false'"
      :aria-describedby="describedById"
      @input="onInput"
    />

    <p v-if="hasError" :id="describedById" class="text-xs text-red-500">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

interface BaseInputProps {
  id?: string;
  label?: string;
  modelValue: string;
  type?: string;
  placeholder?: string;
  autocomplete?: string;
  error?: string | null;
  required?: boolean;
  disabled?: boolean;
  inputClass?: string;
}

const props = withDefaults(defineProps<BaseInputProps>(), {
  type: "text",
  autocomplete: "off",
  required: false,
  disabled: false,
  inputClass: "",
});

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void;
}>();

const hasError = computed(() => !!props.error);
const describedById = computed(() =>
  hasError.value && props.id ? `${props.id}-error` : undefined
);

const onInput = (event: Event): void => {
  const target = event.target as HTMLInputElement;
  emit("update:modelValue", target.value);
};
</script>
