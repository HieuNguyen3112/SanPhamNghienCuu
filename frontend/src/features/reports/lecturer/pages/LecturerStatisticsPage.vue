<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Thống kê giảng viên"
          subtitle="Tổng quan nhân sự giảng dạy trong trường đại học"
          :show-export-pdf="true"
          :show-export-excel="true"
          @exportPdfClicked="showExportNotImplementedMessage('PDF')"
          @exportExcelClicked="showExportNotImplementedMessage('Excel')"
        />
      </div>
      <LecturerFilterPanel
        :lecturerFilterConditions="lecturerFilterConditions"
        :availableDepartmentNames="availableDepartmentNames"
        @lecturerFilterConditionsUpdated="applyLecturerFilterConditions"
        @resetLecturerFilterConditionsRequested="resetLecturerFilterConditions"
      />

      <LecturerSummaryCards
        :totalLecturerCount="totalLecturerCount"
        :numberOfDoctorLecturers="numberOfDoctorLecturers"
        :numberOfMasterLecturers="numberOfMasterLecturers"
        :numberOfBachelorLecturers="numberOfBachelorLecturers"
        :numberOfProfessorAndAssociateProfessorLecturers="
          numberOfProfessorAndAssociateProfessorLecturers
        "
      />

      <LecturerChartSection
        :filteredLecturerRecords="filteredLecturerRecords"
      />
      <LecturerStatisticsTable
        :filteredLecturerRecords="filteredLecturerRecords"
      />

      <div
        v-if="temporaryNotificationMessage"
        class="rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-sm"
      >
        {{ temporaryNotificationMessage }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";

import PageHeader from "@/shared/components/layout/PageHeader.vue";

import LecturerFilterPanel from "../components/LecturerFilterPanel.vue";
import LecturerSummaryCards from "../components/LecturerSummaryCards.vue";
import LecturerChartSection from "../components/LecturerChartSection.vue";
import LecturerStatisticsTable from "../components/LecturerStatisticsTable.vue";

import type {
  LecturerFilterConditions,
  LecturerRecord,
} from "../lecturerStatisticsTypes";
import { lecturerMockRecords } from "../lecturerStatisticsMockData";

/**
 * Vì đây là trang quản trị, logic giữ đơn giản: lọc client-side trên mock data.
 * Mục tiêu là UI rõ ràng và dễ bảo trì, không tối ưu backend trong phạm vi yêu cầu.
 */
const lecturerStatisticsResponse = ref<LecturerRecord[]>([]);
const temporaryNotificationMessage = ref("");

const lecturerFilterConditions = ref<LecturerFilterConditions>({
  selectedDepartmentName: "AllDepartments",
  selectedEducationLevelCategory: "AllEducationLevels",
  selectedAcademicRankCategory: "AllAcademicRanks",
  selectedGenderCategory: "AllGenders",
});

const paginationState = ref({
  currentPageNumber: 1,
  rowsPerPageCount: 10,
  totalPageCount: 1,
});

function fetchLecturerStatistics() {
  // Vì đang dùng mock, hàm này mô phỏng “nạp dữ liệu” để cấu trúc code giống dashboard thật
  lecturerStatisticsResponse.value = lecturerMockRecords;
}

const availableDepartmentNames = computed(() => {
  const departmentNameSet = new Set<string>();
  for (const lecturerRecord of lecturerStatisticsResponse.value) {
    departmentNameSet.add(lecturerRecord.departmentName);
  }
  return Array.from(departmentNameSet.values()).sort(
    (firstValue, secondValue) => firstValue.localeCompare(secondValue)
  );
});

const filteredLecturerRecords = computed(() => {
  const currentFilterConditions = lecturerFilterConditions.value;

  return lecturerStatisticsResponse.value.filter((lecturerRecord) => {
    const isDepartmentMatch =
      currentFilterConditions.selectedDepartmentName === "AllDepartments" ||
      lecturerRecord.departmentName ===
        currentFilterConditions.selectedDepartmentName;

    const isEducationMatch =
      currentFilterConditions.selectedEducationLevelCategory ===
        "AllEducationLevels" ||
      lecturerRecord.educationLevelCategory ===
        currentFilterConditions.selectedEducationLevelCategory;

    const isAcademicRankMatch =
      currentFilterConditions.selectedAcademicRankCategory ===
        "AllAcademicRanks" ||
      lecturerRecord.academicRankCategory ===
        currentFilterConditions.selectedAcademicRankCategory;

    const isGenderMatch =
      currentFilterConditions.selectedGenderCategory === "AllGenders" ||
      lecturerRecord.genderCategory ===
        currentFilterConditions.selectedGenderCategory;

    return (
      isDepartmentMatch &&
      isEducationMatch &&
      isAcademicRankMatch &&
      isGenderMatch
    );
  });
});

const totalLecturerCount = computed(() => filteredLecturerRecords.value.length);

const numberOfDoctorLecturers = computed(() => {
  return filteredLecturerRecords.value.filter(
    (lecturerRecord) => lecturerRecord.educationLevelCategory === "Doctor"
  ).length;
});

const numberOfMasterLecturers = computed(() => {
  return filteredLecturerRecords.value.filter(
    (lecturerRecord) => lecturerRecord.educationLevelCategory === "Master"
  ).length;
});

const numberOfBachelorLecturers = computed(() => {
  return filteredLecturerRecords.value.filter(
    (lecturerRecord) => lecturerRecord.educationLevelCategory === "Bachelor"
  ).length;
});

const numberOfProfessorAndAssociateProfessorLecturers = computed(() => {
  return filteredLecturerRecords.value.filter((lecturerRecord) => {
    return (
      lecturerRecord.academicRankCategory === "Professor" ||
      lecturerRecord.academicRankCategory === "AssociateProfessor"
    );
  }).length;
});

function applyLecturerFilterConditions(
  updatedLecturerFilterConditions: LecturerFilterConditions
) {
  lecturerFilterConditions.value = updatedLecturerFilterConditions;

  // Khi thay đổi bộ lọc, quay về trang 1 để tránh “trang rỗng” gây nhầm lẫn cho quản trị viên
  paginationState.value.currentPageNumber = 1;
}

function resetLecturerFilterConditions() {
  lecturerFilterConditions.value = {
    selectedDepartmentName: "AllDepartments",
    selectedEducationLevelCategory: "AllEducationLevels",
    selectedAcademicRankCategory: "AllAcademicRanks",
    selectedGenderCategory: "AllGenders",
  };
  paginationState.value.currentPageNumber = 1;
}

function recalculateTotalPageCount() {
  const totalRowCount = filteredLecturerRecords.value.length;
  const totalPageCount = Math.max(
    1,
    Math.ceil(totalRowCount / paginationState.value.rowsPerPageCount)
  );
  paginationState.value.totalPageCount = totalPageCount;

  if (paginationState.value.currentPageNumber > totalPageCount) {
    paginationState.value.currentPageNumber = totalPageCount;
  }
}

function showExportNotImplementedMessage(exportFormatName: "PDF" | "Excel") {
  temporaryNotificationMessage.value = `Chức năng xuất ${exportFormatName} hiện chỉ là giao diện (UI-only) theo yêu cầu.`;
  window.setTimeout(() => {
    temporaryNotificationMessage.value = "";
  }, 2500);
}

fetchLecturerStatistics();

watch(
  () => filteredLecturerRecords.value.length,
  () => {
    recalculateTotalPageCount();
  },
  { immediate: true }
);
</script>
