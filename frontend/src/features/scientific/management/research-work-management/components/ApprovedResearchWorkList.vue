<template>
  <div class="space-y-3">
    <div
      v-for="item in approvedItems"
      :key="item.activityId"
      class="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
      tabindex="0"
      role="button"
      :title="`Xem chi tiết: ${item.title}`"
      @click="emitOpenDetail(item.activityId)"
      @keydown.enter.prevent="emitOpenDetail(item.activityId)"
      @keydown.space.prevent="emitOpenDetail(item.activityId)"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="truncate text-sm font-semibold text-slate-900">
            {{ item.title }}
          </div>

          <div
            class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500"
          >
            <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
              {{ item.typeName ?? item.kindName }}
            </span>
            <span class="text-slate-300">•</span>
            <span>{{ item.academicYearCode }}</span>
            <span class="text-slate-300">•</span>
            <span>Approved: {{ formatDate(item.approvedAt) }}</span>
          </div>
        </div>

        <!-- bỏ nút Eye -->
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApprovedSummary } from "../lecturerResearchWork.contracts";
interface ApprovedListProps {
  approvedItems: ApprovedSummary[];
}

interface ApprovedListEmits {
  (e: "open-detail", activityId: number): void;
}

defineProps<ApprovedListProps>();
const emit = defineEmits<ApprovedListEmits>();

function emitOpenDetail(activityId: number) {
  emit("open-detail", activityId);
}

function formatDate(iso: string) {
  const date = new Date(iso);
  return date.toLocaleDateString("vi-VN");
}
</script>
