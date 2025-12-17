<template>
  <div :class="customClass">
    <label
      class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
    >
      {{ label }}
    </label>
    <textarea
      rows="2"
      :value="modelValue"
      :placeholder="placeholder"
      class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
      @input="onInput"
    />
  </div>
</template>

<script setup lang="ts">
interface Props {
  modelValue: string;
  label: string;
  placeholder?: string;
  customClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: "",
  customClass: "",
});

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void;
}>();

function onInput(e: Event) {
  emit("update:modelValue", (e.target as HTMLTextAreaElement).value);
}
</script>
