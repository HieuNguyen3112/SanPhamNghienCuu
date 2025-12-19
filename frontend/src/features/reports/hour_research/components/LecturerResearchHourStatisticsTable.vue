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
          Bảng dữ liệu phục vụ đối soát và tra cứu chi tiết theo từng giảng
          viên.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-600">Số dòng / trang</span>
        <select
          class="rounded-lg border-slate-200 bg-white text-sm focus:border-slate-400 focus:ring-0"
          v-model.number="pageSize"
        >
          <option :value="8">8</option>
          <option :value="12">12</option>
          <option :value="20">20</option>
        </select>
      </div>
    </div>

    <div class="overflow-auto">
      <table class="min-w-[900px] w-full border-collapse">
        <thead class="sticky top-0 z-10 bg-slate-50">
          <tr class="border-b border-slate-200">
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Giảng viên
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Khoa
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-semibold text-slate-700"
            >
              Tổng giờ NCKH
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-semibold text-slate-700"
            >
              Giờ chuẩn
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Trạng thái
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Năm học
            </th>
          </tr>
        </thead>

        <tbody v-if="pagedLecturerResearchHourRecords.length > 0">
          <tr
            v-for="lecturerResearchHourRecord in pagedLecturerResearchHourRecords"
            :key="
              lecturerResearchHourRecord.lecturerIdentifier +
              '-' +
              lecturerResearchHourRecord.academicYear
            "
            class="border-b border-slate-100 hover:bg-slate-50"
          >
            <td class="px-4 py-3 text-sm font-medium text-slate-900">
              {{ lecturerResearchHourRecord.lecturerDisplayName }}
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              {{ lecturerResearchHourRecord.facultyDisplayName }}
            </td>
            <td class="px-4 py-3 text-right text-sm text-slate-800">
              {{
                formatIntegerValue(
                  lecturerResearchHourRecord.totalResearchHourCount
                )
              }}
            </td>
            <td class="px-4 py-3 text-right text-sm text-slate-800">
              {{
                formatIntegerValue(
                  lecturerResearchHourRecord.researchHourStandardCount
                )
              }}
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
                :class="getStatusBadgeClassName(lecturerResearchHourRecord)"
              >
                {{ getStatusLabel(lecturerResearchHourRecord) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              {{ lecturerResearchHourRecord.academicYear }}
            </td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="pagedLecturerResearchHourRecords.length === 0"
        class="p-10 text-center"
      >
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

    <div
      class="flex flex-col gap-2 border-t border-slate-200 p-4 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-xs text-slate-600">
        Hiển thị
        <span class="font-semibold text-slate-900">{{
          pagedLecturerResearchHourRecords.length
        }}</span>
        /
        <span class="font-semibold text-slate-900">{{
          lecturerResearchHourRecords.length
        }}</span>
        bản ghi
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          :disabled="currentPageNumber === 1"
          @click="goToPreviousPage"
        >
          Trang trước
        </button>

        <div class="text-sm text-slate-700">
          Trang
          <span class="font-semibold text-slate-900">{{
            currentPageNumber
          }}</span>
          /
          <span class="font-semibold text-slate-900">{{ totalPageCount }}</span>
        </div>

        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          :disabled="currentPageNumber === totalPageCount"
          @click="goToNextPage"
        >
          Trang sau
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { LecturerResearchHourRecord } from "../lecturerResearchHourModels";

const componentProperties = defineProps<{
  lecturerResearchHourRecords: LecturerResearchHourRecord[];
}>();

const currentPageNumber = ref<number>(1);
const pageSize = ref<number>(12);

watch(
  () => componentProperties.lecturerResearchHourRecords,
  () => {
    // Khi bộ lọc thay đổi, quay về trang đầu giúp trải nghiệm nhất quán (không rơi vào trang rỗng).
    currentPageNumber.value = 1;
  },
  { deep: true }
);

const totalPageCount = computed<number>(() => {
  const recordCount = componentProperties.lecturerResearchHourRecords.length;
  return Math.max(1, Math.ceil(recordCount / pageSize.value));
});

const pagedLecturerResearchHourRecords = computed<LecturerResearchHourRecord[]>(
  () => {
    const startIndex = (currentPageNumber.value - 1) * pageSize.value;
    const endIndex = startIndex + pageSize.value;
    return componentProperties.lecturerResearchHourRecords.slice(
      startIndex,
      endIndex
    );
  }
);

function goToPreviousPage(): void {
  currentPageNumber.value = Math.max(1, currentPageNumber.value - 1);
}

function goToNextPage(): void {
  currentPageNumber.value = Math.min(
    totalPageCount.value,
    currentPageNumber.value + 1
  );
}

function formatIntegerValue(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(Math.round(value));
}

function getStatusLabel(
  lecturerResearchHourRecord: LecturerResearchHourRecord
): string {
  return lecturerResearchHourRecord.totalResearchHourCount >=
    lecturerResearchHourRecord.researchHourStandardCount
    ? "Đạt"
    : "Chưa đạt";
}

function getStatusBadgeClassName(
  lecturerResearchHourRecord: LecturerResearchHourRecord
): string {
  const isMeetingResearchHourStandard =
    lecturerResearchHourRecord.totalResearchHourCount >=
    lecturerResearchHourRecord.researchHourStandardCount;

  return isMeetingResearchHourStandard
    ? "border-emerald-200 bg-emerald-50 text-emerald-800"
    : "border-rose-200 bg-rose-50 text-rose-800";
}
</script>
