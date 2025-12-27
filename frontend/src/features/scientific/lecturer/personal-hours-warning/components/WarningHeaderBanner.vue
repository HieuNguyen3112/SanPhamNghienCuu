<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 md:p-6">
    <div v-if="loading" class="text-sm text-slate-700">
      Đang tải trạng thái…
    </div>

    <div
      v-else-if="error"
      class="rounded-xl border border-rose-200 bg-rose-50 p-4"
    >
      <div class="text-sm font-semibold text-rose-700">
        Không tải được trạng thái
      </div>
      <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
        {{ error }}
      </div>
    </div>

    <div v-else-if="!summary" class="text-sm text-slate-700">
      Chưa có dữ liệu trạng thái.
    </div>

    <div v-else class="flex items-start gap-3">
      <div
        class="flex h-10 w-10 items-center justify-center rounded-2xl ring-1"
        :class="iconToneClass"
      >
        <component :is="iconComponent" class="h-5 w-5" />
      </div>

      <div class="min-w-0 flex-1">
        <div class="text-base font-semibold text-slate-900 md:text-lg">
          {{ headline }}
        </div>

        <div class="mt-1 grid gap-1 text-sm text-slate-700 md:grid-cols-2">
          <div>
            <span class="text-slate-500">Giờ hiện tại:</span>
            <span class="ml-1 font-semibold text-slate-900">
              {{ formatHours(summary.hoursTotal) }} /
              {{ formatHours(summary.requiredHours) }}
            </span>
            <span class="ml-1 text-slate-500"
              >(năm {{ summary.academicYearCode }})</span
            >
          </div>

          <div>
            <span class="text-slate-500">Hạn chót:</span>
            <span class="ml-1 font-semibold text-slate-900">
              {{ formatDateVietnamese(summary.deadlineDate) }}
            </span>
            <span class="ml-2 text-slate-500">
              • {{ formatRelativeDaysFromNow(summary.daysRemaining) }}
            </span>
          </div>

          <div v-if="summary.missingHours > 0" class="md:col-span-2">
            <span class="text-slate-500">Bạn còn thiếu:</span>
            <span class="ml-1 font-semibold" :class="missingToneClass">
              {{ formatHours(summary.missingHours) }} giờ
            </span>
          </div>
        </div>

        <div class="mt-2 text-xs text-slate-500">
          * Hạn chót đang dùng dữ liệu mock (proxy). Khi có backend chuẩn, thay
          bằng deadline kê khai thực tế.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { AlertTriangle, Clock, Info, CheckCircle2 } from "lucide-vue-next";
import type { HoursAlertsSummary } from "../contracts/hoursWarning.contract";
import {
  formatDateVietnamese,
  formatHours,
  formatRelativeDaysFromNow,
} from "../contracts/hoursWarning.contract";

const props = defineProps<{
  summary: HoursAlertsSummary | null;
  loading: boolean;
  error: string | null;
}>();

const isDeadlinePassed = computed(() => {
  if (!props.summary) return false;
  return props.summary.daysRemaining < 0;
});

const isDeadlineSoon = computed(() => {
  if (!props.summary) return false;
  return props.summary.daysRemaining >= 0 && props.summary.daysRemaining <= 10;
});

const isMissingHours = computed(() => {
  if (!props.summary) return false;
  return props.summary.missingHours > 0;
});

const headline = computed(() => {
  if (!props.summary) return "";
  if (isDeadlinePassed.value) return "Đã hết hạn kê khai giờ NCKH";
  if (isDeadlineSoon.value) return "Thời hạn kê khai giờ NCKH sắp kết thúc";
  if (isMissingHours.value) return "Bạn chưa đủ giờ NCKH theo định mức";
  return "Trạng thái giờ NCKH hiện tại ổn";
});

const iconComponent = computed(() => {
  if (isDeadlinePassed.value) return AlertTriangle;
  if (isDeadlineSoon.value) return Clock;
  if (isMissingHours.value) return Info;
  return CheckCircle2;
});

const iconToneClass = computed(() => {
  if (isDeadlinePassed.value) return "bg-rose-50 text-rose-700 ring-rose-200";
  if (isDeadlineSoon.value) return "bg-amber-50 text-amber-700 ring-amber-200";
  if (isMissingHours.value) return "bg-slate-50 text-slate-700 ring-slate-200";
  return "bg-emerald-50 text-emerald-700 ring-emerald-200";
});

const missingToneClass = computed(() => {
  if (isDeadlinePassed.value) return "text-rose-700";
  if (isDeadlineSoon.value) return "text-amber-700";
  return "text-slate-900";
});
</script>
