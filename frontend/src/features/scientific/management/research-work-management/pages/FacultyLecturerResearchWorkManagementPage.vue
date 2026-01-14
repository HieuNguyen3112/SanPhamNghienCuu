<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Quản lý công trình khoa học theo giảng viên"
          subtitle="Theo dõi công trình khoa học của giảng viên trong khoa"
          :show-export-pdf="true"
          :show-export-excel="true"
          @exportPdfClicked="handleExportPdf"
          @exportExcelClicked="handleExportExcel"
        />
      </div>

      <div
        v-if="exportMessage"
        class="rounded-2xl border px-4 py-3 text-sm"
        :class="exportMessageClass"
      >
        {{ exportMessage.text }}
      </div>

      <div class="space-y-4">
        <LecturerResearchWorkFilterPanel
          :filter="filter"
          :academic-year-options="academicYearOptions"
          @update-filter="updateFilter"
          @reset="resetFilter"
        />

        <LecturerResearchWorkSummaryTable
          :overview-items="overviewItems"
          :pagination="overviewPagination"
          :is-loading="isOverviewLoading"
          :error-message="overviewError"
          @open-lecturer="openLecturerDrawer"
          @update-page="updateOverviewPage"
          @update-page-size="updateOverviewPerPage"
        />
      </div>

      <LecturerApprovedResearchWorkDrawer
        :is-open="isLecturerDrawerOpen"
        :lecturer-overview="selectedLecturerOverview"
        :approved-items="approvedItems"
        :pagination="approvedPagination"
        :is-loading="isApprovedLoading"
        :error-message="approvedError"
        @close="closeLecturerDrawer"
        @open-detail="openDetail"
        @update-page="updateApprovedPage"
        @update-page-size="updateApprovedPerPage"
      />

      <ApprovedResearchWorkDetailDrawer
        :is-open="isDetailDrawerOpen"
        :detail="detail"
        :is-loading="isDetailLoading"
        :error-message="detailError"
        @back="backToList"
        @close="closeLecturerDrawer"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import LecturerResearchWorkFilterPanel from "@/features/scientific/management/research-work-management/components/LecturerResearchWorkFilterPanel.vue";
import LecturerResearchWorkSummaryTable from "@/features/scientific/management/research-work-management/components/LecturerResearchWorkSummaryTable.vue";
import LecturerApprovedResearchWorkDrawer from "@/features/scientific/management/research-work-management/components/LecturerApprovedResearchWorkDrawer.vue";
import ApprovedResearchWorkDetailDrawer from "@/features/scientific/management/research-work-management/components/ApprovedResearchWorkDetailDrawer.vue";

import { createLecturerResearchWorkHttpClient } from "@/features/scientific/management/research-work-management/api/lecturerResearchWork.client";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import { useLecturerResearchWorkManagement } from "@/features/scientific/management/research-work-management/composables/useLecturerResearchWork";
import {
  exportFacultySummaryExcel,
  exportFacultySummaryPdf,
} from "@/features/scientific/management/research-work-management/services/researchWorkExport.service";
import { computed, ref } from "vue";

const client = createLecturerResearchWorkHttpClient({ scope: "faculty" });

const {
  filter,
  academicYearOptions,

  overviewItems,
  overviewPagination,
  isOverviewLoading,
  overviewError,

  selectedLecturerOverview,
  isLecturerDrawerOpen,
  isDetailDrawerOpen,

  approvedItems,
  approvedPagination,
  isApprovedLoading,
  approvedError,

  detail,
  isDetailLoading,
  detailError,

  updateFilter,
  resetFilter,
  openLecturerDrawer,
  closeLecturerDrawer,
  openDetail,
  backToList,
  updateOverviewPage,
  updateOverviewPerPage,
  updateApprovedPage,
  updateApprovedPerPage,
} = useLecturerResearchWorkManagement({ client });

type ExportMessage = { type: "success" | "error" | "info"; text: string };
const exportMessage = ref<ExportMessage | null>(null);
const exporting = ref<"excel" | "pdf" | null>(null);
let exportTimer: number | null = null;

const exportMessageClass = computed(() => {
  if (!exportMessage.value) return "";
  if (exportMessage.value.type === "error") {
    return "border-rose-200 bg-rose-50 text-rose-700";
  }
  if (exportMessage.value.type === "success") {
    return "border-emerald-200 bg-emerald-50 text-emerald-800";
  }
  return "border-slate-200 bg-slate-50 text-slate-700";
});

function showExportMessage(payload: ExportMessage) {
  exportMessage.value = payload;
  if (exportTimer) window.clearTimeout(exportTimer);
  exportTimer = window.setTimeout(() => {
    exportMessage.value = null;
    exportTimer = null;
  }, 2500);
}

function buildExportParams() {
  return {
    academic_year_id: filter.academicYearId ?? null,
    q: filter.lecturerName?.trim() || "",
    count_status: filter.statusMode,
  };
}

function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

function resolveErrorMessage(error: unknown, fallback: string) {
  const maybe = error as { response?: { data?: { message?: string } } };
  return maybe?.response?.data?.message || (error as Error)?.message || fallback;
}

async function handleExportExcel() {
  if (exporting.value) return;
  exporting.value = "excel";
  showExportMessage({ type: "info", text: "Đang xuất Excel..." });
  try {
    const result = await exportFacultySummaryExcel(buildExportParams());
    downloadBlob(result.blob, result.filename);
    showExportMessage({ type: "success", text: "Đã xuất Excel." });
  } catch (error) {
    showExportMessage({
      type: "error",
      text: resolveErrorMessage(error, "Không thể xuất Excel."),
    });
  } finally {
    exporting.value = null;
  }
}

async function handleExportPdf() {
  if (exporting.value) return;
  exporting.value = "pdf";
  showExportMessage({ type: "info", text: "Đang xuất PDF..." });
  try {
    const result = await exportFacultySummaryPdf(buildExportParams());
    downloadBlob(result.blob, result.filename);
    showExportMessage({ type: "success", text: "Đã xuất PDF." });
  } catch (error) {
    showExportMessage({
      type: "error",
      text: resolveErrorMessage(error, "Không thể xuất PDF."),
    });
  } finally {
    exporting.value = null;
  }
}
</script>
