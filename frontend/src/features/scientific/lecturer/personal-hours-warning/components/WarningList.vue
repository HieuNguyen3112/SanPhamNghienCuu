<template>
  <div class="space-y-3">
    <div
      v-if="loading"
      class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-700"
    >
      Đang tải cảnh báo...
    </div>

    <div
      v-else-if="error"
      class="rounded-2xl border border-rose-200 bg-rose-50 p-4"
    >
      <div class="text-sm font-semibold text-rose-700">
        Không tải được danh sách
      </div>
      <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
        {{ error }}
      </div>
    </div>

    <div
      v-else-if="alerts.length === 0"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-center"
    >
      <div class="text-sm font-semibold text-slate-900">Không có cảnh báo</div>
      <div class="mt-1 text-xs text-slate-500">
        Bạn đang ở trạng thái ổn hoặc chưa có dữ liệu phù hợp.
      </div>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        class="rounded-2xl border border-slate-200 bg-white p-4"
      >
        <div class="flex items-start gap-3">
          <div
            class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-2xl ring-1"
            :class="iconToneClass(alert)"
          >
            <component :is="iconComponent(alert)" class="h-5 w-5" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900">
                  {{ alert.title }}
                </div>
                <div class="mt-1 text-sm text-slate-700">
                  {{ alert.description }}
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ring-1"
                  :class="seenPillClass(alert.statusKey)"
                >
                  <component
                    :is="alert.isSeen ? CheckCircle2 : Info"
                    class="h-4 w-4"
                  />
                  <span class="ml-1">{{ alert.statusLabel }}</span>
                </span>
              </div>
            </div>

            <div
              class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500"
            >
              <div v-if="alert.updatedAt">
                <span class="inline-flex items-center gap-1">
                  <Clock class="h-4 w-4" />
                  Cập nhật: {{ formatDateVietnamese(alert.updatedAt) }}
                </span>
              </div>

              <div v-if="alert.deadlineDate">
                <span class="inline-flex items-center gap-1">
                  <CalendarClock class="h-4 w-4" />
                  Hạn chót: {{ formatDateVietnamese(alert.deadlineDate) }}
                </span>
              </div>
            </div>

            <div
              class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="flex items-center gap-2">
                <button
                  v-if="!alert.isSeen"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                  @click="emit('mark-seen', alert.id)"
                >
                  <CheckCircle2 class="h-4 w-4" />
                  Đánh dấu đã xem
                </button>
                <button
                  v-else
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                  @click="emit('delete-alert', alert.id)"
                >
                  <Trash2 class="h-4 w-4" />
                  Xóa cảnh báo
                </button>
              </div>

              <div class="flex items-center gap-2">
                <RouterLink
                  v-if="alert.ctaTo && alert.ctaLabel && !alert.ctaExternal"
                  :to="alert.ctaTo"
                  class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                  <ArrowRight class="h-4 w-4" />
                  {{ alert.ctaLabel }}
                </RouterLink>

                <a
                  v-else-if="alert.ctaTo && alert.ctaLabel"
                  :href="alert.ctaTo"
                  target="_blank"
                  rel="noreferrer"
                  class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                  <ArrowRight class="h-4 w-4" />
                  {{ alert.ctaLabel }}
                </a>

                <button
                  v-else
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-400"
                  disabled
                >
                  <ArrowRight class="h-4 w-4" />
                  Không có hành động
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HoursAlertItem } from "../contracts/hoursWarning.contract";
import { formatDateVietnamese } from "../contracts/hoursWarning.contract";
import {
  AlertTriangle,
  ArrowRight,
  CalendarClock,
  CheckCircle2,
  Clock,
  Info,
  Trash2,
} from "lucide-vue-next";

defineProps<{
  alerts: HoursAlertItem[];
  loading: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "mark-seen", alertId: string): void;
  (e: "delete-alert", alertId: string): void;
}>();

function iconComponent(alert: HoursAlertItem) {
  if (alert.level === "danger") return AlertTriangle;
  if (alert.level === "warning") return Clock;
  return Info;
}

function iconToneClass(alert: HoursAlertItem) {
  if (alert.level === "danger") return "bg-rose-50 text-rose-700 ring-rose-200";
  if (alert.level === "warning") {
    return "bg-amber-50 text-amber-700 ring-amber-200";
  }
  return "bg-slate-50 text-slate-700 ring-slate-200";
}

function seenPillClass(statusKey: string) {
  return statusKey === "unseen"
    ? "bg-slate-50 text-slate-700 ring-slate-200"
    : "bg-emerald-50 text-emerald-700 ring-emerald-200";
}
</script>
