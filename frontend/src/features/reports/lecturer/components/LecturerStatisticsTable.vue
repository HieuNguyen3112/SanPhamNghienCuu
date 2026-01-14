<template>
  <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div
      class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between"
    >
      <div>
        <h3 class="text-sm font-semibold text-slate-900">
          Danh sách giảng viên
        </h3>
        <p class="text-xs text-slate-500">
          Hiển thị {{ pagination.total }} giảng viên theo điều kiện lọc
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <select
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 hover:bg-slate-50"
          :value="sort.sortFieldIdentifier"
          @change="updateSortFieldIdentifier(($event.target as HTMLSelectElement).value)"
        >
          <option value="full_name">Sắp xếp: Họ và tên</option>
          <option value="faculty_name">Sắp xếp: Khoa</option>
          <option value="gender">Sắp xếp: Giới tính</option>
          <option value="degree_name">Sắp xếp: Trình độ</option>
          <option value="academic_rank_name">Sắp xếp: Học hàm</option>
          <option value="seniority_years">Sắp xếp: Thâm niên</option>
        </select>

        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 hover:bg-slate-50"
          @click="toggleSortDirection"
        >
          {{ sort.sortDirection === "asc" ? "Tăng dần" : "Giảm dần" }}
        </button>
      </div>
    </div>

    <div class="max-h-[520px] overflow-auto scrollbar-none">
      <table class="min-w-full text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50">
          <tr class="text-left text-slate-700">
            <SortableTableHeaderCell
              headerTitle="Họ và tên"
              sortFieldIdentifier="full_name"
              :currentSortCondition="sort"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Khoa"
              sortFieldIdentifier="faculty_name"
              :currentSortCondition="sort"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Giới tính"
              sortFieldIdentifier="gender"
              :currentSortCondition="sort"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Trình độ học vấn"
              sortFieldIdentifier="degree_name"
              :currentSortCondition="sort"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Học hàm"
              sortFieldIdentifier="academic_rank_name"
              :currentSortCondition="sort"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Thâm niên (năm)"
              sortFieldIdentifier="seniority_years"
              :currentSortCondition="sort"
              :isRightAligned="true"
              @sortRequested="applyRequestedSortCondition"
            />
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
            :key="row.id"
            class="border-b border-slate-100 hover:bg-slate-50"
          >
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ row.fullName }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ row.faculty.name ?? "Chưa rõ" }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ formatGender(row.gender) }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ row.degree.name ?? "Chưa rõ" }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ row.academicRank.name ?? "Chưa rõ" }}
            </td>
            <td class="px-4 py-3 text-right tabular-nums text-slate-700">
              {{ row.seniorityYears }}
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td colspan="6" class="px-4 py-10">
              <div
                class="flex flex-col items-center justify-center gap-2 text-center"
              >
                <div
                  class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100"
                >
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    class="h-6 w-6 text-slate-600"
                  >
                    <path
                      d="M10 10l4 4m0-4l-4 4"
                      stroke="currentColor"
                      stroke-width="1.5"
                      stroke-linecap="round"
                    />
                    <path
                      d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                      stroke="currentColor"
                      stroke-width="1.5"
                    />
                  </svg>
                </div>
                <p class="text-sm font-medium text-slate-900">
                  Không có dữ liệu phù hợp
                </p>
                <p class="text-xs text-slate-600">
                  Hãy thử nới điều kiện lọc để xem lại danh sách giảng viên.
                </p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
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
  </div>
</template>

<script setup lang="ts">
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import SortableTableHeaderCell from "./SortableTableHeaderCell.vue";
import type {
  LecturerReportPagination,
  LecturerReportRow,
  LecturerSortCondition,
  LecturerSortField,
} from "../lecturerReportTypes";

const componentProperties = defineProps<{
  rows: LecturerReportRow[];
  pagination: LecturerReportPagination;
  sort: LecturerSortCondition;
  loading: boolean;
}>();

const emitComponentEvent = defineEmits<{
  (eventName: "sortChanged", condition: LecturerSortCondition): void;
  (eventName: "pageChanged", page: number): void;
  (eventName: "pageSizeChanged", pageSize: number): void;
}>();

function updateSortFieldIdentifier(sortFieldIdentifier: string) {
  emitComponentEvent("sortChanged", {
    sortFieldIdentifier: sortFieldIdentifier as LecturerSortField,
    sortDirection: componentProperties.sort.sortDirection,
  });
}

function toggleSortDirection() {
  emitComponentEvent("sortChanged", {
    sortFieldIdentifier: componentProperties.sort.sortFieldIdentifier,
    sortDirection: componentProperties.sort.sortDirection === "asc" ? "desc" : "asc",
  });
}

function applyRequestedSortCondition(requestedSortCondition: LecturerSortCondition) {
  emitComponentEvent("sortChanged", requestedSortCondition);
}

function formatGender(gender: string | null) {
  if (!gender) return "Chưa rõ";
  const normalized = gender.toLowerCase();
  if (["male", "nam"].includes(normalized)) return "Nam";
  if (["female", "nu", "nữ"].includes(normalized)) return "Nữ";
  if (["other", "khac", "khác"].includes(normalized)) return "Khác";
  return gender;
}
</script>

<style>
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
</style>
