// File:
src/features/lecturer-hours-overview/components/HoursBatchHistorySection.vue
<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-center gap-2">
      <List class="h-4 w-4 text-slate-700" />
      <div class="text-sm font-semibold text-slate-900">
        Các đợt yêu cầu xét duyệt giờ NCKH
      </div>
    </div>

    <div
      v-if="loading"
      class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
    >
      Đang tải lịch sử…
    </div>

    <div
      v-else-if="error"
      class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div v-else-if="batches.length === 0" class="mt-4 text-sm text-slate-600">
      Chưa có đợt xét duyệt nào.
      <div class="mt-2 text-xs text-slate-500">
        TODO(BE): schema hiện chưa có “batch/đợt”. Cần endpoint/DTO derived hoặc
        thêm bảng hour_approval_batches.
      </div>
    </div>

    <div v-else class="mt-4 overflow-hidden rounded-xl border border-slate-200">
      <div class="max-h-[520px] overflow-auto">
        <table class="min-w-[980px] w-full text-left text-sm">
          <thead
            class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"
          >
            <tr>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">Đợt</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">Năm học</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">
                Trạng thái
              </th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">Ngày gửi</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">
                Ngày duyệt
              </th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3 text-right">
                Tổng giờ
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="batch in batches"
              :key="batch.batchId"
              class="cursor-pointer hover:bg-slate-50"
              @click="emit('open-detail', batch.batchId)"
            >
              <td class="px-4 py-3 font-semibold text-slate-900">
                {{ batch.batchName }}
              </td>
              <td class="px-4 py-3 text-slate-700">
                {{ batch.academicYearCode }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                  :class="statusBadgeClass(batch.status)"
                >
                  {{ statusLabel(batch.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-700">
                {{ formatDate(batch.submittedAt) }}
              </td>
              <td class="px-4 py-3 text-slate-700">
                {{ batch.decidedAt ? formatDate(batch.decidedAt) : "–" }}
              </td>
              <td
                class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900"
              >
                {{ batch.totalHours }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <BatchDetailDrawer
      :open="detailOpen"
      :detail="detail"
      :loading="loadingDetail"
      :error="errorDetail"
      @close="emit('close-detail')"
    />
  </section>
</template>

<script setup lang="ts">
import { List } from "lucide-vue-next";
import BatchDetailDrawer from "./BatchDetailDrawer.vue";
import type {
  HoursApprovalBatchDetail,
  HoursApprovalBatchSummary,
  HoursBatchStatus,
} from "../contracts/HoursOverviewContracts";

defineProps<{
  batches: HoursApprovalBatchSummary[];
  loading: boolean;
  error: string | null;

  detailOpen: boolean;
  detail: HoursApprovalBatchDetail | null;
  loadingDetail: boolean;
  errorDetail: string | null;
}>();

const emit = defineEmits<{
  (e: "open-detail", batchId: number): void;
  (e: "close-detail"): void;
}>();

function formatDate(value: string): string {
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleDateString();
}

function statusLabel(status: HoursBatchStatus): string {
  if (status === "approved") return "Đã duyệt";
  if (status === "pending") return "Chờ duyệt";
  return "Từ chối";
}

function statusBadgeClass(status: HoursBatchStatus): string {
  switch (status) {
    case "approved":
      return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
    case "pending":
      return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    case "rejected":
      return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
    default:
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
  }
}
</script>
