<template>
  <div class="min-h-screen bg-slate-50">
    <div class="space-y-4 p-4 md:p-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
        <PageHeader
          title="Tính giờ NCKH cá nhân"
          subtitle="Hệ thống tự tính giờ, giảng viên bổ sung minh chứng và gửi duyệt giờ NCKH lên khoa."
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <HoursModuleTabs
        active-tab="calculate"
        :missing-evidence-count="worksMissingEvidence.length"
      />

      <GuidanceAlert />

      <HoursSummaryCard
        :total-approved-count="totalApprovedCount"
        :selected-count="selectedCount"
        :selected-hours-total="selectedHoursTotal"
      />

      <WorksFilterPanel
        :filter="filter"
        :academic-year-options="academicYearOptions"
        :loading="loadingList"
        :loading-academic-years="loadingAcademicYears"
        @update:filter="applyFilter"
        @reset="resetFilter"
      />

      <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
        <RouterLink
          :to="{ name: 'hours.calculate.evidence_missing' }"
          class="inline-flex items-center gap-2 font-medium text-slate-900 hover:underline"
        >
          Xem công trình chưa có minh chứng ({{ worksMissingEvidence.length }})
        </RouterLink>
        <div class="mt-1 text-xs text-slate-600">
          Trang này chỉ tập trung vào các công trình đang thiếu minh chứng để bạn xử lý nhanh.
        </div>
      </div>

      <ApprovedWorksTable
        :rows="filteredWorks"
        :selectable-ids="selectableIds"
        :selected-ids="selectedIds"
        :selected-count="selectedCount"
        :selected-hours-total="selectedHoursTotal"
        :submitting="submitting"
        :submit-error="submitError"
        :loading="loadingList"
        :error="errorList"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        :total-item-count="totalItemCount"
        @toggle-row="toggleWorkSelection"
        @toggle-select-all="toggleSelectAll"
        @open-detail="openWorkDetail"
        @submit-request="submitRequest"
        @update:currentPageNumber="updateCurrentPageNumber"
        @update:pageSize="updatePageSize"
      />

      <WorkDetailDrawer
        :open="drawerOpen"
        :detail="workDetail"
        :loading="loadingDetail"
        :error="errorDetail"
        :evidence-files="evidenceFiles"
        :evidence-file-types="evidenceFileTypes"
        :selected-evidence-type-id="selectedEvidenceTypeId"
        :selected-evidence-file="selectedEvidenceFile"
        :loading-evidence="loadingEvidence"
        :loading-evidence-types="loadingEvidenceTypes"
        :evidence-error="evidenceError"
        :upload-evidence-error="uploadEvidenceError"
        :uploading-evidence="uploadingEvidence"
        :deleting-evidence-id="deletingEvidenceId"
        :can-upload-evidence="canUploadEvidence"
        @close="closeWorkDetail"
        @update:evidenceTypeId="setSelectedEvidenceTypeId"
        @update:evidenceFile="setSelectedEvidenceFile"
        @upload-evidence="uploadEvidence"
        @delete-evidence="deleteEvidence"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

import HoursModuleTabs from "@/features/scientific/lecturer/personal-hours-declare/components/HoursModuleTabs.vue";
import GuidanceAlert from "@/features/scientific/lecturer/personal-hours-declare/components/GuidanceAlert.vue";
import HoursSummaryCard from "@/features/scientific/lecturer/personal-hours-declare/components/HoursSummaryCard.vue";
import WorksFilterPanel from "@/features/scientific/lecturer/personal-hours-declare/components/WorksFilterPanel.vue";
import ApprovedWorksTable from "@/features/scientific/lecturer/personal-hours-declare/components/ApprovedWorksTable.vue";
import WorkDetailDrawer from "@/features/scientific/lecturer/personal-hours-declare/components/WorkDetailDrawer.vue";

import { useSelectHoursRequest } from "@/features/scientific/lecturer/personal-hours-declare/composables/useSelectHoursRequest";

const {
  filteredWorks,
  worksMissingEvidence,
  selectableIds,
  selectedIds,
  filter,
  academicYearOptions,

  drawerOpen,
  workDetail,

  loadingList,
  errorList,
  loadingDetail,
  errorDetail,

  submitting,
  submitError,

  totalApprovedCount,
  selectedCount,
  selectedHoursTotal,
  currentPageNumber,
  pageSize,
  totalItemCount,

  evidenceFiles,
  evidenceFileTypes,
  selectedEvidenceTypeId,
  selectedEvidenceFile,
  loadingEvidence,
  loadingEvidenceTypes,
  evidenceError,
  uploadEvidenceError,
  uploadingEvidence,
  deletingEvidenceId,
  canUploadEvidence,
  loadingAcademicYears,

  initialize,
  applyFilter,
  resetFilter,
  updateCurrentPageNumber,
  updatePageSize,

  toggleWorkSelection,
  toggleSelectAll,
  openWorkDetail,
  closeWorkDetail,
  submitRequest,

  setSelectedEvidenceTypeId,
  setSelectedEvidenceFile,
  uploadEvidence,
  deleteEvidence,
} = useSelectHoursRequest();

onMounted(() => {
  initialize();
});
</script>
