<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <!-- Header -->
      <section
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <div class="flex flex-col gap-1">
          <h1 class="text-lg font-semibold text-slate-900">
            Cảnh báo giảng viên giờ NCKH
          </h1>
          <p class="text-sm text-slate-600">
            Danh sách giảng viên chưa đạt định mức giờ NCKH theo năm học (chỉ
            xem, chỉ gửi cảnh báo).
          </p>
        </div>
      </section>

      <!-- Summary -->
      <LecturerResearchHourWarningSummary
        :summary-statistics="summaryStatistics"
        :loading="loading"
        :error="error"
      />

      <!-- Filter -->
      <LecturerResearchHourWarningFilterPanel
        :filter="filter"
        :loading="loading"
        :faculty-options="facultyOptions"
        :academic-year-options="academicYearOptions"
        :faculty-select-locked="isFacultyLocked"
        @update:filter="patchFilter"
        @reset="resetFilter"
      />

      <!-- Table -->
      <LecturerResearchHourWarningTable
        :rows="filteredEntries"
        :loading="loading"
        :error="error"
        @open-detail="openDetail"
      />

      <!-- Detail modal -->
      <LecturerResearchHourWarningDetailModal
        :open="detailOpen"
        :entry="selectedEntry"
        :submitting="submittingWarning"
        :submit-error="submitWarningError"
        @close="closeDetail"
        @request-warning="requestWarning"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ResearchHourWarningService } from "../services/researchHourWarningService";
import LecturerResearchHourWarningFilterPanel from "../components/LecturerResearchHourWarningFilterPanel.vue";
import LecturerResearchHourWarningTable from "../components/LecturerResearchHourWarningTable.vue";
import LecturerResearchHourWarningDetailModal from "../components/LecturerResearchHourWarningDetailModal.vue";
import LecturerResearchHourWarningSummary from "../components/LecturerResearchHourWarningSummary.vue";
import { useResearchHourWarningManagement } from "../composables/useResearchHourWarningManagement";

const props = defineProps<{
  service: ResearchHourWarningService;
}>();

const {
  filter,
  patchFilter,
  resetFilter,

  loading,
  error,

  facultyOptions,
  academicYearOptions,
  summaryStatistics,

  filteredEntries,
  isFacultyLocked,

  detailOpen,
  selectedEntry,
  openDetail,
  closeDetail,

  submittingWarning,
  submitWarningError,
  requestWarning,
} = useResearchHourWarningManagement(props.service);
</script>
