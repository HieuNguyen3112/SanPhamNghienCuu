<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs font-medium text-slate-500">{{ title }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ value }}</p>
        <p v-if="subtitle" class="mt-1 text-xs text-slate-500">
          {{ subtitle }}
        </p>
      </div>

      <div class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-600">
        KPI
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
  value: number | string;
  subtitle?: string;
}>();
</script>
