<template>
  <div
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
  >
    <div class="border-b border-slate-200 px-4 py-3">
      <div class="flex items-center justify-between">
        <div class="text-sm font-semibold text-slate-900">
          Tổng quan theo giảng viên
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="p-6">
      <div class="text-sm text-slate-600">Đang tải dữ liệu...</div>
    </div>

    <div v-else-if="errorMessage" class="p-6">
      <div
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ errorMessage }}
      </div>
    </div>

    <div v-else-if="overviewItems.length === 0" class="p-6">
      <div class="text-sm text-slate-600">Không có dữ liệu phù hợp bộ lọc.</div>
    </div>

    <div v-else class="overflow-auto">
      <table class="min-w-full border-separate border-spacing-0">
        <thead class="sticky top-0 z-10 bg-slate-50">
          <tr>
            <th
              class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold text-slate-600"
            >
              Giảng viên
            </th>
            <th
              class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold text-slate-600"
            >
              Khoa
            </th>

            <th
              class="border-b border-slate-200 px-4 py-3 text-right text-xs font-semibold text-slate-600"
            >
              Đã duyệt
            </th>
            <th
              class="border-b border-slate-200 px-4 py-3 text-right text-xs font-semibold text-slate-600"
            >
              Chờ duyệt
            </th>
            <th
              class="border-b border-slate-200 px-4 py-3 text-right text-xs font-semibold text-slate-600"
            >
              Từ chối
            </th>
            <th
              class="border-b border-slate-200 px-4 py-3 text-right text-xs font-semibold text-slate-600"
            >
              Tổng
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="item in pagedItems"
            :key="item.lecturerId"
            class="cursor-pointer hover:bg-slate-50"
            tabindex="0"
            role="button"
            :title="`Xem công trình đã duyệt của ${item.lecturerFullName}`"
            @click="emitOpenLecturerDrawer(item.lecturerId)"
            @keydown.enter.prevent="emitOpenLecturerDrawer(item.lecturerId)"
            @keydown.space.prevent="emitOpenLecturerDrawer(item.lecturerId)"
          >
            <td class="border-b border-slate-100 px-4 py-3">
              <div class="text-sm font-medium text-slate-900">
                {{ item.lecturerFullName }}
              </div>
              <div class="text-xs text-slate-500">
                {{ item.departmentName }}
              </div>
            </td>

            <td
              class="border-b border-slate-100 px-4 py-3 text-sm text-slate-700"
            >
              {{ item.facultyName }}
            </td>

            <td class="border-b border-slate-100 px-4 py-3 text-right text-sm">
              <span class="rounded-md bg-emerald-50 px-2 py-1 text-emerald-700">
                {{ item.approvedCount }}
              </span>
            </td>

            <td class="border-b border-slate-100 px-4 py-3 text-right text-sm">
              <span class="rounded-md bg-amber-50 px-2 py-1 text-amber-700">
                {{ item.pendingCount }}
              </span>
            </td>

            <td class="border-b border-slate-100 px-4 py-3 text-right text-sm">
              <span class="rounded-md bg-rose-50 px-2 py-1 text-rose-700">
                {{ item.rejectedCount }}
              </span>
            </td>
            <td class="border-b border-slate-100 px-4 py-3 text-right text-sm">
              <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
                {{ item.totalCount }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- ✅ Dùng pagination xài chung -->
      <div class="border-t border-slate-200 px-4 py-3">
        <PaginationControl
          :total-item-count="overviewItems.length"
          :current-page-number="currentPageNumber"
          :page-size="pageSize"
          display-mode="FULL"
          :show-record-summary="true"
          record-summary-mode="PAGE_COUNT"
          record-summary-unit-label="giảng viên"
          @update:currentPageNumber="updateCurrentPageNumber"
          @update:pageSize="updatePageSize"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { OverviewItem } from "../lecturerResearchWork.contracts";

// ⚠️ sửa path theo đúng nơi bạn đặt component phân trang dùng chung
import PaginationControl from "@/shared/components/layout/SharedPaginationControls.vue";

interface SummaryTableProps {
  overviewItems: OverviewItem[];
  isLoading: boolean;
  errorMessage: string | null;
}

interface SummaryTableEmits {
  (e: "open-lecturer", lecturerId: number): void;
}

const props = defineProps<SummaryTableProps>();
const emit = defineEmits<SummaryTableEmits>();

const currentPageNumber = ref(1);
const pageSize = ref(8);

const pagedItems = computed(() => {
  const startIndex = (currentPageNumber.value - 1) * pageSize.value;
  return props.overviewItems.slice(startIndex, startIndex + pageSize.value);
});

watch(
  () => props.overviewItems,
  () => {
    currentPageNumber.value = 1;
  }
);

function emitOpenLecturerDrawer(lecturerId: number) {
  emit("open-lecturer", lecturerId);
}

function updateCurrentPageNumber(nextPageNumber: number) {
  currentPageNumber.value = nextPageNumber;
}

function updatePageSize(nextPageSize: number) {
  pageSize.value = nextPageSize;
}
</script>
