<template>
  <div class="min-h-screen bg-slate-50">
    <div class="container mx-auto p-4 md:p-6">
      <!-- PAGE HEADER -->
      <section
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <div
          class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
        >
          <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
              Cảnh báo giảng viên thiếu giờ nghiên cứu khoa học
            </h1>
            <p class="mt-1 text-sm text-slate-600">
              Danh sách giảng viên chưa đạt chuẩn giờ NCKH theo quy định
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
              @click="exportWarningDashboardAsExcel"
            >
              <svg
                viewBox="0 0 24 24"
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
              >
                <path
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4 4h16v16H4z"
                />
                <path
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M8 8h8M8 12h8M8 16h8"
                />
              </svg>
              Xuất Excel
            </button>

            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
              @click="exportWarningDashboardAsPdf"
            >
              <svg
                viewBox="0 0 24 24"
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
              >
                <path
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"
                />
                <path
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M14 3v4a2 2 0 0 0 2 2h4"
                />
              </svg>
              Xuất PDF
            </button>
          </div>
        </div>
      </section>

      <div class="mt-4 space-y-4">
        <!-- WARNING SUMMARY -->
        <LecturerResearchHourWarningSummary
          :total-lecturers-not-meeting-research-hour-standard="
            totalLecturersNotMeetingResearchHourStandard
          "
          :total-remaining-research-hours-to-meet-standard="
            totalRemainingResearchHoursToMeetStandard
          "
          :average-remaining-research-hours-to-meet-standard="
            averageRemainingResearchHoursToMeetStandard
          "
          :minimum-required-research-hours="minimumRequiredResearchHours"
        />

        <!-- FILTER PANEL -->
        <LecturerResearchHourWarningFilterPanel
          v-if="lecturerResearchHourWarningResponse"
          :faculty-options="lecturerResearchHourWarningResponse.facultyOptions"
          :academic-year-options="
            lecturerResearchHourWarningResponse.academicYearOptions
          "
          v-model:selectedFacultyIdentifier="selectedFacultyIdentifier"
          v-model:selectedAcademicYear="selectedAcademicYear"
          v-model:selectedResearchHourShortfallSeverityFilterCondition="
            selectedResearchHourShortfallSeverityFilterCondition
          "
          @resetLecturerResearchHourWarningFilterConditions="
            resetLecturerResearchHourWarningFilterConditions
          "
        />

        <!-- WARNING TABLE -->
        <LecturerResearchHourWarningTable
          :lecturer-research-hour-warning-entries="
            filteredLecturerResearchHourWarningEntries
          "
          @lecturerResearchHourWarningEntrySelected="
            openLecturerResearchHourWarningDetailModal
          "
        />
      </div>
    </div>

    <!-- DETAIL MODAL -->
    <LecturerResearchHourWarningDetailModal
      :is-open="isLecturerResearchHourWarningDetailModalOpen"
      :selected-lecturer-research-hour-warning-entry="
        selectedLecturerResearchHourWarningEntry
      "
      :minimum-required-research-hours="minimumRequiredResearchHours"
      :current-lecturer-research-hours="currentLecturerResearchHours"
      :remaining-research-hours-to-meet-standard="
        remainingResearchHoursToMeetStandard
      "
      @close="closeLecturerResearchHourWarningDetailModal"
      @submit-lecturer-research-hour-warning-notification-request="
        submitLecturerResearchHourWarningNotificationRequest
      "
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import LecturerResearchHourWarningSummary from "../components/LecturerResearchHourWarningSummary.vue";
import LecturerResearchHourWarningFilterPanel from "../components/LecturerResearchHourWarningFilterPanel.vue";
import LecturerResearchHourWarningTable from "../components/LecturerResearchHourWarningTable.vue";
import LecturerResearchHourWarningDetailModal from "../components/LecturerResearchHourWarningDetailModal.vue";

import { useLecturerResearchHourWarningPageState } from "./useLecturerResearchHourWarningPageState";

const {
  lecturerResearchHourWarningResponse,

  selectedFacultyIdentifier,
  selectedAcademicYear,
  selectedResearchHourShortfallSeverityFilterCondition,

  filteredLecturerResearchHourWarningEntries,

  totalLecturersNotMeetingResearchHourStandard,
  totalRemainingResearchHoursToMeetStandard,
  averageRemainingResearchHoursToMeetStandard,
  minimumRequiredResearchHours,

  currentLecturerResearchHours,
  remainingResearchHoursToMeetStandard,

  isLecturerResearchHourWarningDetailModalOpen,
  selectedLecturerResearchHourWarningEntry,

  loadLecturersNotMeetingResearchHourStandard,
  resetLecturerResearchHourWarningFilterConditions,

  openLecturerResearchHourWarningDetailModal,
  closeLecturerResearchHourWarningDetailModal,

  submitLecturerResearchHourWarningNotificationRequest,

  exportWarningDashboardAsExcel,
  exportWarningDashboardAsPdf,
} = useLecturerResearchHourWarningPageState();

onMounted(() => {
  loadLecturersNotMeetingResearchHourStandard();
});
</script>
