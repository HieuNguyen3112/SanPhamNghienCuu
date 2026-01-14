<template>
  <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div
      class="flex flex-col gap-2 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between"
    >
      <div>
        <h2 class="text-sm font-semibold text-slate-900">
          Danh sách giảng viên
        </h2>
        <p class="mt-1 text-xs text-slate-600">
          Hiển thị {{ pagination.total }} giảng viên theo điều kiện lọc.
        </p>
      </div>
    </div>

    <div class="overflow-auto">
      <table class="min-w-[900px] w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50">
          <tr class="border-b border-slate-200 text-left text-slate-700">
            <th class="px-4 py-3 text-xs font-semibold">Giảng viên</th>
            <th class="px-4 py-3 text-xs font-semibold">Khoa</th>
            <th class="px-4 py-3 text-right text-xs font-semibold">
              Tổng giờ NCKH
            </th>
            <th class="px-4 py-3 text-right text-xs font-semibold">
              Giờ chuẩn
            </th>
            <th class="px-4 py-3 text-xs font-semibold">Trạng thái</th>
            <th class="px-4 py-3 text-xs font-semibold">Năm học</th>
          </tr>
        </thead>

        <tbody v-if="loading">
          <tr>
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
              Đang tải dữ liệu...
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="rows.length > 0">
          <tr
            v-for="row in rows"
            :key="`${row.lecturerId}-${row.academicYearId ?? 'na'}`"
            class="border-b border-slate-100 hover:bg-slate-50"
          >
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ row.lecturerName }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ row.facultyName ?? "Chưa rõ" }}
            </td>
            <td class="px-4 py-3 text-right text-slate-800">
              {{ formatIntegerValue(row.totalHours) }}
            </td>
            <td class="px-4 py-3 text-right text-slate-800">
              {{ formatIntegerValue(row.requiredHours) }}
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
                :class="getStatusBadgeClassName(row.status)"
              >
                {{ getStatusLabel(row.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ row.academicYearCode ?? "-" }}
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!loading && rows.length === 0" class="p-10 text-center">
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-200 bg-slate-50"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-6 w-6 text-slate-600"
            fill="none"
            stroke="currentColor"
          >
            <path
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21 21l-5-5M10 18a8 8 0 1 1 0-16a8 8 0 0 1 0 16z"
            />
          </svg>
        </div>
        <div class="mt-3 text-sm font-semibold text-slate-900">
          Không tìm thấy dữ liệu
        </div>
        <div class="mt-1 text-sm text-slate-600">
          Vui lòng điều chỉnh bộ lọc để xem kết quả phù hợp.
        </div>
      </div>
    </div>

    <div class="border-t border-slate-200 p-4">
      <SharedPaginationControls
        :total-item-count="pagination.total"
        :current-page-number="pagination.page"
        :page-size="pagination.perPage"
        display-mode="FULL"
        :show-record-summary="true"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="giảng viên"
        @update:currentPageNumber="emitComponentEvent('pageChanged', $event)"
        @update:pageSize="emitComponentEvent('pageSizeChanged', $event)"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import type {
  HourResearchReportPagination,
  HourResearchReportRow,
} from "../hourResearchReportTypes";

const componentProperties = defineProps<{
  rows: HourResearchReportRow[];
  pagination: HourResearchReportPagination;
  loading: boolean;
}>();

const emitComponentEvent = defineEmits<{
  (eventName: "pageChanged", page: number): void;
  (eventName: "pageSizeChanged", pageSize: number): void;
}>();

function formatIntegerValue(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(Math.round(value));
}

function getStatusLabel(status: HourResearchReportRow["status"]): string {
  return status === "met" ? "Đạt chuẩn" : "Chưa đạt";
}

function getStatusBadgeClassName(status: HourResearchReportRow["status"]): string {
  return status === "met"
    ? "border-emerald-200 bg-emerald-50 text-emerald-800"
    : "border-rose-200 bg-rose-50 text-rose-800";
}
</script>
