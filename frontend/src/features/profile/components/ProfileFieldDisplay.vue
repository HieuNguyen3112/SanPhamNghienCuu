<template>
  <div :class="customClass">
    <p class="text-xs uppercase tracking-wide text-slate-400">
      {{ label }}
    </p>
    <p class="mt-1 text-sm text-slate-800">
      {{ value || "—" }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  label: string;
  value: string;
  customClass?: string;
}

defineProps<Props>();
</script>
