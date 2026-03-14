<template>
  <div class="min-h-screen bg-slate-50">
    <div class="space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Minh chứng chưa nộp"
          subtitle="Quản lý các công trình chưa có minh chứng để hoàn tất điều kiện gửi duyệt giờ."
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <HoursModuleTabs
        active-tab="evidence-missing"
        :missing-evidence-count="worksMissingEvidence.length"
      />

      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <div class="text-sm font-semibold text-slate-900">
          Công trình chưa có minh chứng: {{ worksMissingEvidence.length }}
        </div>
        <div class="mt-1 text-xs text-slate-600">
          Tổng giờ dự kiến của danh sách này:
          {{ missingEvidenceHoursTotal }} giờ
        </div>
      </div>

      <WorksFilterPanel
        :filter="filter"
        :academic-year-options="academicYearOptions"
        :loading="loadingList"
        :loading-academic-years="loadingAcademicYears"
        :hide-hours-mode="true"
        @update:filter="applyFilterWithDefaultStatus"
        @reset="resetFilterWithDefaultStatus"
      />

      <EvidenceMissingTable
        :rows="worksMissingEvidence"
        :loading="loadingList"
        :error="errorList"
        @open-detail="openWorkDetail"
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
import { computed, onMounted } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

import HoursModuleTabs from "@/features/scientific/lecturer/personal-hours-declare/components/HoursModuleTabs.vue";
import WorksFilterPanel from "@/features/scientific/lecturer/personal-hours-declare/components/WorksFilterPanel.vue";
import WorkDetailDrawer from "@/features/scientific/lecturer/personal-hours-declare/components/WorkDetailDrawer.vue";
import EvidenceMissingTable from "@/features/scientific/lecturer/personal-hours-declare/components/EvidenceMissingTable.vue";
import {
  formatHours,
  type WorksFilterState,
} from "@/features/scientific/lecturer/personal-hours-declare/contracts/selectHoursRequest.contract";
import { useSelectHoursRequest } from "@/features/scientific/lecturer/personal-hours-declare/composables/useSelectHoursRequest";

const {
  worksMissingEvidence,
  filter,
  academicYearOptions,
  drawerOpen,
  workDetail,
  loadingList,
  errorList,
  loadingDetail,
  errorDetail,
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
  openWorkDetail,
  closeWorkDetail,
  setSelectedEvidenceTypeId,
  setSelectedEvidenceFile,
  uploadEvidence,
  deleteEvidence,
} = useSelectHoursRequest();

const missingEvidenceHoursTotal = computed(() => {
  const total = worksMissingEvidence.value.reduce(
    (sum, work) => sum + (work.effectiveHoursDisplay ?? 0),
    0,
  );
  return formatHours(total);
});

function applyFilterWithDefaultStatus(partial: Partial<WorksFilterState>) {
  applyFilter({
    ...partial,
    hoursMode:
      partial.hoursMode && partial.hoursMode !== "all"
        ? partial.hoursMode
        : "hours_not_submitted",
  });
}

function resetFilterWithDefaultStatus() {
  applyFilter({
    academicYearId: filter.value.academicYearId,
    keyword: "",
    hoursMode: "hours_not_submitted",
  });
}

onMounted(async () => {
  await initialize({
    defaultHoursMode: "hours_not_submitted",
  });
});
</script>
