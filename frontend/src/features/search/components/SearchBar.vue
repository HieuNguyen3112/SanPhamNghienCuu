<!-- src/features/search/components/SearchBar.vue -->
<template>
  <div class="flex flex-col gap-2 md:flex-row md:items-center">
    <div class="relative flex-1">
      <span
        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
      >
        <svg
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
          />
        </svg>
      </span>
      <input
        :value="modelValue"
        :placeholder="placeholder"
        :autofocus="autofocus"
        class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        type="text"
        @input="onInput"
        @keyup.enter="onSubmit"
      />
    </div>

    <button
      type="button"
      class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
      :disabled="loading"
      @click="onSubmit"
    >
      <span
        v-if="loading"
        class="mr-2 h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"
      />
      Tra cứu
    </button>
  </div>
</template>

<script setup lang="ts">
interface Props {
  modelValue: string;
  placeholder?: string;
  loading?: boolean;
  autofocus?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: "Nhập tên công trình, tên giảng viên, từ khóa...",
  loading: false,
  autofocus: false,
});

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void;
  (e: "submit"): void;
}>();

function onInput(event: Event) {
  const target = event.target as HTMLInputElement;
  emit("update:modelValue", target.value);
}

function onSubmit() {
  emit("submit");
}
</script>
