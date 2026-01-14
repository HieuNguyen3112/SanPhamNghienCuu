<!-- File: src/features/.../components/LecturerResearchHourWarningSummary.vue -->
<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-center gap-2">
      <Bell class="h-5 w-5 text-slate-700" />
      <div class="text-sm font-semibold text-slate-900">Tổng quan nhanh</div>
    </div>

    <div v-if="loading" class="mt-3 text-sm text-slate-700">Đang tải...</div>
    <div v-else-if="error" class="mt-3 text-sm text-rose-700">{{ error }}</div>

    <div v-else-if="summaryStatistics" class="mt-3 grid gap-3 md:grid-cols-4">
      <div
        class="group rounded-xl border border-amber-100 bg-amber-50/60 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-xs font-medium text-slate-700">
              Quy mô cần theo dõi
            </div>
            <div
              class="mt-1 text-lg font-semibold tracking-tight text-slate-900"
            >
              {{
                formatInteger(
                  summaryStatistics.totalLecturersNotMeetingStandard
                )
              }}
            </div>
            <div class="mt-1 text-sm text-slate-700">
              Số giảng viên chưa đạt chuẩn
            </div>
          </div>

          <div
            class="rounded-lg border border-amber-100 bg-white/70 p-2 text-amber-900 group-hover:bg-white"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
            >
              <path
                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 7a4 4 0 1 1-8 0a4 4 0 0 1 8 0z"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>
      </div>

      <div
        class="group rounded-xl border border-amber-100 bg-amber-50/60 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-xs font-medium text-slate-700">
              Khoảng cách so với chuẩn
            </div>
            <div
              class="mt-1 text-lg font-semibold tracking-tight text-slate-900"
            >
              {{
                formatHours(summaryStatistics.totalRemainingHoursToMeetStandard)
              }}
            </div>
            <div class="mt-1 text-sm text-slate-700">
              Tổng số giờ NCKH cần thiếu
            </div>
          </div>

          <div
            class="rounded-lg border border-amber-100 bg-white/70 p-2 text-amber-900 group-hover:bg-white"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
            >
              <path
                d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0a9 9 0 0 1 18 0z"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>
      </div>

      <div
        class="group rounded-xl border border-amber-100 bg-amber-50/60 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-xs font-medium text-slate-700">
              Mức độ trung bình
            </div>
            <div
              class="mt-1 text-lg font-semibold tracking-tight text-slate-900"
            >
              {{
                formatDecimal(
                  summaryStatistics.averageRemainingHoursToMeetStandard
                )
              }}
            </div>
            <div class="mt-1 text-sm text-slate-700">
              Giờ thiếu trung bình / giảng viên
            </div>
          </div>

          <div
            class="rounded-lg border border-amber-100 bg-white/70 p-2 text-amber-900 group-hover:bg-white"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
            >
              <path
                d="M4 19V5M8 17V9M12 19v-6M16 17v-8M20 19V7"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>
      </div>

      <div
        class="group rounded-xl border border-amber-100 bg-amber-50/60 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-xs font-medium text-slate-700">
              Chuẩn giờ đối soát
            </div>
            <div
              class="mt-1 text-lg font-semibold tracking-tight text-slate-900"
            >
              {{ formatInteger(summaryStatistics.minimumRequiredHours) }} giờ
            </div>
            <div class="mt-1 text-sm text-slate-700">
              Chuẩn giờ NCKH hiện hành
            </div>
          </div>

          <div
            class="rounded-lg border border-amber-100 bg-white/70 p-2 text-amber-900 group-hover:bg-white"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
            >
              <path
                d="M20 6L9 17l-5-5"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="mt-3 text-sm text-slate-600">Không có dữ liệu.</div>
  </section>
</template>

<script setup lang="ts">
import { Bell } from "lucide-vue-next";
import type { LecturerResearchHourSummaryStatistics } from "../contracts/lecturerResearchHourWarning.contract";
import { formatHours } from "../contracts/lecturerResearchHourWarning.contract";

defineProps<{
  summaryStatistics: LecturerResearchHourSummaryStatistics | null;
  loading: boolean;
  error: string | null;
}>();

function formatInteger(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(Math.round(value));
}

function formatDecimal(value: number): string {
  return new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 2 }).format(
    value
  );
}
</script>
