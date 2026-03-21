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

    <div class="relative">
      <input
        :id="id"
        :type="resolvedType"
        :value="modelValue"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :disabled="disabled"
        class="w-full rounded-xl border border-white/70 bg-white/80 px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:cursor-not-allowed disabled:opacity-60"
        :class="[inputClass, canTogglePassword ? 'pr-12' : '']"
        :aria-invalid="hasError ? 'true' : 'false'"
        :aria-describedby="describedById"
        @input="onInput"
      />

      <button
        v-if="canTogglePassword"
        type="button"
        class="absolute inset-y-0 right-3 flex items-center text-gray-500 transition hover:text-gray-700 focus:outline-none"
        :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
        @click="showPassword = !showPassword"
      >
        <svg
          v-if="showPassword"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          class="h-5 w-5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 3l18 18M10.58 10.58A2 2 0 0013.42 13.42M9.88 5.09A9.77 9.77 0 0112 4.91c4.78 0 8.72 2.87 10 7.09a11.8 11.8 0 01-4.15 5.94M6.1 6.1A11.77 11.77 0 002 12c1.28 4.22 5.22 7.09 10 7.09 1.77 0 3.44-.39 4.9-1.1"
          />
        </svg>

        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          class="h-5 w-5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M2 12s3.64-7 10-7 10 7 10 7-3.64 7-10 7-10-7-10-7z"
          />
          <circle cx="12" cy="12" r="3" />
        </svg>
      </button>
    </div>

    <p v-if="hasError" :id="describedById" class="text-xs text-red-500">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

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

const showPassword = ref(false);
const hasError = computed(() => !!props.error);
const canTogglePassword = computed(() => props.type === "password");
const resolvedType = computed(() =>
  canTogglePassword.value && showPassword.value ? "text" : props.type
);
const describedById = computed(() =>
  hasError.value && props.id ? `${props.id}-error` : undefined
);

const onInput = (event: Event): void => {
  const target = event.target as HTMLInputElement;
  emit("update:modelValue", target.value);
};
</script>
