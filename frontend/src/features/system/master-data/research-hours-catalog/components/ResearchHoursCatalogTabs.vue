<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="t in tabs"
      :key="t.key"
      type="button"
      :class="[
        'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm transition',
        modelValue === t.key
          ? 'border-slate-200 bg-slate-900/5 font-semibold text-slate-900'
          : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
      ]"
      @click="$emit('update:modelValue', t.key)"
    >
      <component :is="t.icon" class="h-4 w-4" />
      <span>{{ t.label }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { Component } from "vue";

type TabKey = "work_conversion" | "hours_quota" | "academic_year_period";

interface TabItem {
  key: TabKey;
  label: string;
  icon: Component;
}

defineProps<{
  modelValue: TabKey;
  tabs: TabItem[];
}>();

defineEmits<{
  (e: "update:modelValue", v: TabKey): void;
}>();
</script>
