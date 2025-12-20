<template>
  <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
    <button
      v-for="card in cards"
      :key="card.key"
      type="button"
      class="rounded-xl border bg-white p-3 text-left transition hover:bg-slate-50"
      :class="
        activeTab === card.tab
          ? 'border-slate-900 ring-1 ring-slate-900/10'
          : 'border-slate-200'
      "
      @click="emit('select', card.tab)"
    >
      <div class="text-xs font-medium text-slate-500">{{ card.label }}</div>
      <div class="mt-1 text-2xl font-semibold text-slate-900 tabular-nums">
        {{ card.value }}
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
  PersonalStats,
  PersonalWorkFilterTab,
} from "../contracts/personalResearchWorksContracts";

const props = defineProps<{
  stats: PersonalStats;
  activeTab: PersonalWorkFilterTab;
}>();

const emit = defineEmits<{
  (e: "select", tab: PersonalWorkFilterTab): void;
}>();

const cards = computed(() => {
  return [
    {
      key: "total",
      label: "Tổng công trình",
      value: props.stats.totalCount,
      tab: "all" as const,
    },
    {
      key: "approved",
      label: "Đã duyệt",
      value: props.stats.approvedCount,
      tab: "approved" as const,
    },
    {
      key: "pending",
      label: "Chờ duyệt",
      value: props.stats.pendingCount,
      tab: "pending" as const,
    },
    {
      key: "rejected",
      label: "Bị từ chối",
      value: props.stats.rejectedCount,
      tab: "rejected" as const,
    },
    {
      key: "draft",
      label: "Bản nháp",
      value: props.stats.draftCount,
      tab: "draft" as const,
    },
  ];
});
</script>
