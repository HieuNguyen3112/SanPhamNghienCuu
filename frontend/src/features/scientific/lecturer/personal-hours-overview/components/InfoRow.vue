// File: src/features/lecturer-hours-overview/components/InfoRow.vue
<template>
  <div :class="wrapperClass">
    <div class="text-xs font-semibold text-slate-500">{{ label }}</div>
    <div class="mt-0.5 text-sm text-slate-900">{{ value }}</div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string;
    value: string;
    wrapperClass?: string;
  }>(),
  {
    wrapperClass: "",
  }
);
</script>
