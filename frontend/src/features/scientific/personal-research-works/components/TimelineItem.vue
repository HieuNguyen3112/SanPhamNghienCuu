<template>
  <div class="flex items-start gap-3">
    <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="dotClass" />
    <div class="min-w-0">
      <div class="text-sm font-medium text-slate-900">{{ label }}</div>
      <div class="mt-0.5 text-xs text-slate-500">{{ value }}</div>
      <div v-if="note" class="mt-1 text-xs text-slate-600">
        {{ note }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

type TimelineStatus = "pending" | "approved" | "rejected" | null;

const props = withDefaults(
  defineProps<{
    label: string;
    value: string;
    note?: string | null;
    status?: TimelineStatus;
  }>(),
  {
    note: null,
    status: null,
  }
);

const dotClass = computed(() => {
  if (props.status === "approved") return "bg-emerald-500";
  if (props.status === "rejected") return "bg-rose-500";
  if (props.status === "pending") return "bg-amber-500";
  return "bg-slate-400";
});
</script>
