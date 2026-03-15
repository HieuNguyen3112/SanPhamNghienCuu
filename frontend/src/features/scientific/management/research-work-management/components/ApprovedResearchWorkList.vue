<template>
  <div class="space-y-3">
    <div
      v-for="item in approvedItems"
      :key="item.activityId"
      class="rounded-xl border border-slate-200 bg-white p-4"
      :class="
        canOpenDetail(item)
          ? 'cursor-pointer hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300'
          : 'cursor-default'
      "
      :tabindex="canOpenDetail(item) ? 0 : undefined"
      :role="canOpenDetail(item) ? 'button' : undefined"
      :title="canOpenDetail(item) ? `Xem chi tiết: ${item.title}` : undefined"
      @click="canOpenDetail(item) ? emitOpenDetail(item.activityId) : undefined"
      @keydown.enter.prevent="
        canOpenDetail(item) ? emitOpenDetail(item.activityId) : undefined
      "
      @keydown.space.prevent="
        canOpenDetail(item) ? emitOpenDetail(item.activityId) : undefined
      "
    >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="truncate text-sm font-semibold text-slate-900">
            {{ item.title }}
          </div>

          <div
            class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500"
          >
            <span
              class="rounded-md px-2 py-1 font-medium"
              :class="statusBadgeClass(item.statusCode)"
            >
              {{ item.statusName }}
            </span>
            <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
              {{ item.typeName ?? item.kindName }}
            </span>
            <span class="text-slate-300">•</span>
            <span>{{ item.academicYearCode }}</span>
            <span class="text-slate-300">•</span>
            <span
              >{{ statusDateLabel(item.statusCode) }}:
              {{ formatDate(resolveDisplayDate(item)) }}</span
            >
          </div>
        </div>

        <div v-if="canReviewWork(item)" class="shrink-0">
          <button
            type="button"
            class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100"
            @click.stop="emitReviewWork(item.activityId)"
          >
            Xét duyệt
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApprovedSummary } from "../lecturerResearchWork.contracts";
import { parseBackendDateTime } from "../../shared/utils/backendDateTime";
interface ApprovedListProps {
  approvedItems: ApprovedSummary[];
  scope: "faculty" | "university";
}

interface ApprovedListEmits {
  (e: "open-detail", activityId: number): void;
  (e: "review-work", activityId: number): void;
}

const props = defineProps<ApprovedListProps>();
const emit = defineEmits<ApprovedListEmits>();

function emitOpenDetail(activityId: number) {
  emit("open-detail", activityId);
}

function emitReviewWork(activityId: number) {
  emit("review-work", activityId);
}

function canOpenDetail(item: ApprovedSummary): boolean {
  return item.statusCode === "approved";
}

function canReviewWork(item: ApprovedSummary): boolean {
  return (
    props.scope === "faculty" && item.statusCode === "pending_faculty_review"
  );
}

function resolveDisplayDate(item: ApprovedSummary): string | null {
  if (item.statusCode === "approved") return item.approvedAt;
  return item.submittedAt ?? item.approvedAt;
}

function statusDateLabel(statusCode: string): string {
  if (statusCode === "approved") return "Đã duyệt";
  if (statusCode === "rejected") return "Ngày xử lý";
  return "Ngày gửi";
}

function statusBadgeClass(statusCode: string): string {
  if (statusCode === "approved") {
    return "bg-emerald-50 text-emerald-700";
  }
  if (statusCode === "rejected") {
    return "bg-rose-50 text-rose-700";
  }
  return "bg-amber-50 text-amber-700";
}

function formatDate(iso: string | null) {
  if (!iso) return "—";
  const date = parseBackendDateTime(iso);
  if (!date) return iso;
  return date.toLocaleDateString("vi-VN");
}
</script>
