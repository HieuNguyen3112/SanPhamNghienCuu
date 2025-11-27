<!-- src/features/declarations/components/WorkTypeBadge.vue -->
<template>
  <span
    class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
    :class="badgeClass"
  >
    <span class="mr-1 h-1.5 w-1.5 rounded-full" :class="dotClass" />
    {{ label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { WorkType } from "../types";

interface Props {
  label?: string;
  type: WorkType;
}

const props = defineProps<Props>();

const badgeClass = computed(() => {
  switch (props.type) {
    case "ARTICLE":
      return "border-sky-200 bg-sky-50 text-sky-700";
    case "PROJECT":
      return "border-emerald-200 bg-emerald-50 text-emerald-700";
    case "BOOK":
      return "border-amber-200 bg-amber-50 text-amber-700";
    case "OTHER":
    default:
      return "border-slate-200 bg-slate-50 text-slate-600";
  }
});

const dotClass = computed(() => {
  switch (props.type) {
    case "ARTICLE":
      return "bg-sky-500";
    case "PROJECT":
      return "bg-emerald-500";
    case "BOOK":
      return "bg-amber-500";
    case "OTHER":
    default:
      return "bg-slate-400";
  }
});
</script>
