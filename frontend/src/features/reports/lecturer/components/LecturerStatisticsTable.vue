<template>
  <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <!-- Header -->
    <div
      class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between"
    >
      <div>
        <h3 class="text-sm font-semibold text-slate-900">
          Danh sách giảng viên
        </h3>
        <p class="text-xs text-slate-500">
          Hiển thị {{ totalFilteredLecturerCount }} giảng viên theo điều kiện
          lọc
        </p>
      </div>

      <!-- Sort -->
      <div class="flex flex-wrap items-center gap-2">
        <select
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 hover:bg-slate-50"
          :value="currentSortCondition.sortFieldIdentifier"
          @change="
            updateSortFieldIdentifier(
              ($event.target as HTMLSelectElement).value
            )
          "
        >
          <option value="lecturerFullName">Sắp xếp: Họ và tên</option>
          <option value="departmentName">Sắp xếp: Khoa</option>
          <option value="genderCategory">Sắp xếp: Giới tính</option>
          <option value="educationLevelCategory">Sắp xếp: Trình độ</option>
          <option value="academicRankCategory">Sắp xếp: Học hàm</option>
          <option value="teachingExperienceYears">Sắp xếp: Thâm niên</option>
        </select>

        <button
          type="button"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 hover:bg-slate-50"
          @click="toggleSortDirection"
        >
          {{
            currentSortCondition.sortDirection === "Ascending"
              ? "Tăng dần"
              : "Giảm dần"
          }}
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="max-h-[520px] overflow-auto scrollbar-none">
      <table class="min-w-full text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50">
          <tr class="text-left text-slate-700">
            <SortableTableHeaderCell
              headerTitle="Họ và tên"
              sortFieldIdentifier="lecturerFullName"
              :currentSortCondition="currentSortCondition"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Khoa"
              sortFieldIdentifier="departmentName"
              :currentSortCondition="currentSortCondition"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Giới tính"
              sortFieldIdentifier="genderCategory"
              :currentSortCondition="currentSortCondition"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Trình độ học vấn"
              sortFieldIdentifier="educationLevelCategory"
              :currentSortCondition="currentSortCondition"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Học hàm"
              sortFieldIdentifier="academicRankCategory"
              :currentSortCondition="currentSortCondition"
              @sortRequested="applyRequestedSortCondition"
            />
            <SortableTableHeaderCell
              headerTitle="Thâm niên (năm)"
              sortFieldIdentifier="teachingExperienceYears"
              :currentSortCondition="currentSortCondition"
              :isRightAligned="true"
              @sortRequested="applyRequestedSortCondition"
            />
          </tr>
        </thead>

        <tbody v-if="pagedLecturerRecords.length > 0">
          <tr
            v-for="lecturerRecord in pagedLecturerRecords"
            :key="lecturerRecord.lecturerIdentifier"
            class="border-b border-slate-100 hover:bg-slate-50"
          >
            <td class="px-4 py-3 font-medium text-slate-900">
              {{ lecturerRecord.lecturerFullName }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ lecturerRecord.departmentName }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ getVietnameseGenderLabel(lecturerRecord.genderCategory) }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{
                getVietnameseEducationLevelLabel(
                  lecturerRecord.educationLevelCategory
                )
              }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{
                getVietnameseAcademicRankLabel(
                  lecturerRecord.academicRankCategory
                )
              }}
            </td>
            <td class="px-4 py-3 text-right tabular-nums text-slate-700">
              {{ lecturerRecord.teachingExperienceYears }}
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

    <!-- Footer: ONE place only -->
    <div class="border-t border-slate-200 p-4">
      <SharedPaginationControls
        :total-item-count="filteredLecturerRecords.length"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        :show-record-summary="true"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="giảng viên "
        @update:currentPageNumber="(v) => (currentPageNumber = v)"
        @update:pageSize="(v) => (pageSize = v)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { LecturerRecord } from "../lecturerStatisticsTypes";

import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import SortableTableHeaderCell, {
  type LecturerSortCondition,
  type LecturerSortFieldIdentifier,
} from "./SortableTableHeaderCell.vue";

const componentProperties = defineProps<{
  filteredLecturerRecords: LecturerRecord[];
}>();

const currentSortCondition = ref<LecturerSortCondition>({
  sortFieldIdentifier: "lecturerFullName",
  sortDirection: "Ascending",
});

/** Pagination state (shared style across app) */
const pageSize = ref<number>(12);

const currentPageNumber = ref<number>(1);

const totalFilteredLecturerCount = computed<number>(() => {
  return componentProperties.filteredLecturerRecords.length;
});

const totalPageCount = computed<number>(() => {
  return Math.max(
    1,
    Math.ceil(totalFilteredLecturerCount.value / pageSize.value)
  );
});

watch([totalFilteredLecturerCount, pageSize], () => {
  // Lý do: nếu filter/pageSize làm giảm tổng trang, tránh rơi vào trang trống
  if (currentPageNumber.value > totalPageCount.value) {
    currentPageNumber.value = totalPageCount.value;
  }
  if (currentPageNumber.value < 1) currentPageNumber.value = 1;
});
watch(
  () => componentProperties.filteredLecturerRecords,
  () => {
    currentPageNumber.value = 1;
  }
);

watch(pageSize, () => {
  // tránh rơi trang rỗng khi user đổi page size
  const nextTotal = Math.max(
    1,
    Math.ceil(totalFilteredLecturerCount.value / pageSize.value)
  );
  if (currentPageNumber.value > nextTotal) currentPageNumber.value = nextTotal;
});

watch(
  () => componentProperties.filteredLecturerRecords,
  () => {
    // Lý do: khi filter đổi, quay lại trang 1 để UX “không rỗng”
    currentPageNumber.value = 1;
  }
);

function updateSortFieldIdentifier(sortFieldIdentifier: string) {
  currentSortCondition.value = {
    sortFieldIdentifier: sortFieldIdentifier as LecturerSortFieldIdentifier,
    sortDirection: currentSortCondition.value.sortDirection,
  };
  currentPageNumber.value = 1;
}

function toggleSortDirection() {
  currentSortCondition.value = {
    ...currentSortCondition.value,
    sortDirection:
      currentSortCondition.value.sortDirection === "Ascending"
        ? "Descending"
        : "Ascending",
  };
  currentPageNumber.value = 1;
}

function applyRequestedSortCondition(
  requestedSortCondition: LecturerSortCondition
) {
  currentSortCondition.value = requestedSortCondition;
  currentPageNumber.value = 1;
}

const sortedLecturerRecords = computed<LecturerRecord[]>(() => {
  const copiedLecturerRecords = [
    ...componentProperties.filteredLecturerRecords,
  ];
  const { sortFieldIdentifier, sortDirection } = currentSortCondition.value;

  copiedLecturerRecords.sort((firstLecturerRecord, secondLecturerRecord) => {
    const firstValue = firstLecturerRecord[sortFieldIdentifier];
    const secondValue = secondLecturerRecord[sortFieldIdentifier];

    const compareResult =
      typeof firstValue === "number" && typeof secondValue === "number"
        ? firstValue - secondValue
        : String(firstValue).localeCompare(String(secondValue), "vi");

    return sortDirection === "Ascending" ? compareResult : -compareResult;
  });

  return copiedLecturerRecords;
});

const pagedLecturerRecords = computed<LecturerRecord[]>(() => {
  const startIndex = (currentPageNumber.value - 1) * pageSize.value;
  return sortedLecturerRecords.value.slice(
    startIndex,
    startIndex + pageSize.value
  );
});

// const recordRangeText = computed<string>(() => {
//   if (totalFilteredLecturerCount.value === 0) return "0";
//   const startIndex = (currentPageNumber.value - 1) * pageSize.value + 1;
//   const endIndex = Math.min(
//     currentPageNumber.value * pageSize.value,
//     totalFilteredLecturerCount.value
//   );
//   return `${startIndex}–${endIndex}`;
// });

/** Labels */
function getVietnameseGenderLabel(
  genderCategory: LecturerRecord["genderCategory"]
) {
  if (genderCategory === "Male") return "Nam";
  if (genderCategory === "Female") return "Nữ";
  return "Khác";
}

function getVietnameseEducationLevelLabel(
  educationLevelCategory: LecturerRecord["educationLevelCategory"]
) {
  if (educationLevelCategory === "Doctor") return "Tiến sĩ";
  if (educationLevelCategory === "Master") return "Thạc sĩ";
  return "Đại học";
}

function getVietnameseAcademicRankLabel(
  academicRankCategory: LecturerRecord["academicRankCategory"]
) {
  if (academicRankCategory === "Professor") return "Giáo sư";
  if (academicRankCategory === "AssociateProfessor") return "Phó Giáo sư";
  return "Không";
}
</script>
<style>
.scrollbar-none {
  -ms-overflow-style: none; /* IE/Edge */
  scrollbar-width: none; /* Firefox */
}
.scrollbar-none::-webkit-scrollbar {
  display: none; /* Chrome/Safari */
}
</style>
