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
        :missing-evidence-count="missingEvidenceCount"
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
          Xem công trình chưa có minh chứng ({{ missingEvidenceCount }})
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
        :submit-item-errors-by-activity-id="submitItemErrorsByActivityId"
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
        :evidence-file-input-reset-key="evidenceFileInputResetKey"
        :loading-evidence="loadingEvidence"
        :loading-evidence-types="loadingEvidenceTypes"
        :evidence-error="evidenceError"
        :evidence-type-error="evidenceTypeError"
        :evidence-file-error="evidenceFileError"
        :upload-request-error="uploadRequestError"
        :duplicate-evidence-warning="duplicateEvidenceWarning"
        :uploading-evidence="uploadingEvidence"
        :deleting-evidence-id="deletingEvidenceId"
        :drawer-actions-locked="drawerActionsLocked"
        :can-upload-evidence="canUploadEvidence"
        :delete-evidence-confirm-open="deleteEvidenceConfirmOpen"
        :delete-evidence-confirm-message="deleteEvidenceConfirmMessage"
        @close="closeWorkDetail"
        @update:evidenceTypeId="setSelectedEvidenceTypeId"
        @update:evidenceFile="setSelectedEvidenceFile"
        @upload-evidence="uploadEvidence"
        @request-delete-evidence="requestDeleteEvidence"
        @confirm-delete-evidence="confirmDeleteEvidence"
        @cancel-delete-evidence="cancelDeleteEvidence"
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
  missingEvidenceCount,
  selectableIds,
  selectedIds,
  filter,
  academicYearOptions,
  submitItemErrorsByActivityId,

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
  evidenceFileInputResetKey,
  loadingEvidence,
  loadingEvidenceTypes,
  evidenceError,
  evidenceTypeError,
  evidenceFileError,
  uploadRequestError,
  duplicateEvidenceWarning,
  uploadingEvidence,
  deletingEvidenceId,
  drawerActionsLocked,
  canUploadEvidence,
  deleteEvidenceConfirmOpen,
  deleteEvidenceConfirmMessage,
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
  requestDeleteEvidence,
  cancelDeleteEvidence,
  confirmDeleteEvidence,
} = useSelectHoursRequest();

onMounted(() => {
  void initialize();
});
</script>
