<template>
  <div class="w-full">
    <div
      class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2"
    >
      <button
        v-for="t in tabs"
        :key="t.key"
        type="button"
        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm transition"
        :class="
          t.key === modelValue
            ? 'border-slate-900 bg-slate-900 text-white'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
        "
        @click="emit('update:modelValue', t.key)"
      >
        <component :is="t.icon" class="h-4 w-4" />
        <span class="font-semibold">{{ t.label }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Component } from "vue";
import type { WorkCatalogTabKey } from "../contracts/workCatalogTabs.contract";

defineProps<{
  modelValue: WorkCatalogTabKey;
  tabs: Array<{ key: WorkCatalogTabKey; label: string; icon: Component }>;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", v: WorkCatalogTabKey): void;
}>();
</script>
