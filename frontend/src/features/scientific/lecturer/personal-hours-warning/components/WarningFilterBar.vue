<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-3 md:p-4">
    <div class="flex flex-wrap items-center gap-2">
      <button
        v-for="pill in pillList"
        :key="pill.key"
        type="button"
        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold ring-1 transition"
        :class="pillClass(pill.key)"
        @click="emit('change', pill.key)"
      >
        <span>{{ pill.label }}</span>
        <span
          class="rounded-full bg-white/70 px-2 py-0.5 text-xs font-bold text-slate-700"
        >
          {{ pill.count }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { HoursAlertsFilter } from "../contracts/hoursWarning.contract";

const props = defineProps<{
  filterStatus: HoursAlertsFilter;
  counts: { all: number; danger: number; warning: number; done: number };
}>();

const emit = defineEmits<{
  (e: "change", next: HoursAlertsFilter): void;
}>();

const pillList = computed(() => [
  { key: "all" as const, label: "Tất cả", count: props.counts.all },
  { key: "danger" as const, label: "Nguy hiểm", count: props.counts.danger },
  {
    key: "warning" as const,
    label: "Sắp hết hạn",
    count: props.counts.warning,
  },
  { key: "done" as const, label: "Đã xử lý", count: props.counts.done },
]);

function pillClass(key: HoursAlertsFilter) {
  const active = props.filterStatus === key;
  if (active) return "bg-slate-900 text-white ring-slate-900";

  if (key === "danger")
    return "bg-white text-rose-700 ring-rose-200 hover:bg-rose-50";
  if (key === "warning")
    return "bg-white text-amber-700 ring-amber-200 hover:bg-amber-50";
  if (key === "done")
    return "bg-white text-emerald-700 ring-emerald-200 hover:bg-emerald-50";
  return "bg-white text-slate-700 ring-slate-200 hover:bg-slate-50";
}
</script>
