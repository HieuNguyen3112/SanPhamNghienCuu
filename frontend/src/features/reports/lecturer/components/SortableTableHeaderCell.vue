<template>
  <th
    class="border-b border-slate-200 px-4 py-3 font-semibold select-none"
    :class="isRightAligned ? 'text-right' : 'text-left'"
  >
    <button
      type="button"
      class="inline-flex items-center gap-2 hover:text-slate-900"
      :class="isActiveSortField ? 'text-slate-900' : 'text-slate-700'"
      @click="requestSortChange"
    >
      <span>{{ headerTitle }}</span>
      <span class="text-xs text-slate-500">
        {{ sortDirectionIconText }}
      </span>
    </button>
  </th>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
  LecturerSortCondition,
  LecturerSortDirection,
  LecturerSortField,
} from "../lecturerReportTypes";

const componentProperties = defineProps<{
  headerTitle: string;
  sortFieldIdentifier: LecturerSortField;
  currentSortCondition: LecturerSortCondition;
  isRightAligned?: boolean;
}>();

const emitComponentEvent = defineEmits<{
  (
    eventName: "sortRequested",
    requestedSortCondition: LecturerSortCondition
  ): void;
}>();

const isActiveSortField = computed(() => {
  return (
    componentProperties.currentSortCondition.sortFieldIdentifier ===
    componentProperties.sortFieldIdentifier
  );
});

const sortDirectionIconText = computed(() => {
  if (!isActiveSortField.value) return "↕";
  return componentProperties.currentSortCondition.sortDirection === "asc"
    ? "↑"
    : "↓";
});

function requestSortChange() {
  const nextSortDirection: LecturerSortDirection =
    isActiveSortField.value &&
    componentProperties.currentSortCondition.sortDirection === "asc"
      ? "desc"
      : "asc";

  emitComponentEvent("sortRequested", {
    sortFieldIdentifier: componentProperties.sortFieldIdentifier,
    sortDirection: nextSortDirection,
  });
}
</script>
