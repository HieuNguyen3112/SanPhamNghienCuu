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
        scope="faculty"
        :lecturer-overview="selectedLecturerOverview"
        :approved-items="approvedItems"
        :pagination="approvedPagination"
        :is-loading="isApprovedLoading"
        :error-message="approvedError"
        @close="closeLecturerDrawer"
        @open-detail="openDetail"
        @review-work="goToFacultyApproval"
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
import { useExportActionFeedback } from "@/shared/composables/useExportActionFeedback";
import { useRouter } from "vue-router";

const client = createLecturerResearchWorkHttpClient({ scope: "faculty" });
const { runExport } = useExportActionFeedback();
const router = useRouter();

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

function buildExportParams() {
  return {
    academic_year_id: filter.academicYearId ?? null,
    q: filter.lecturerName?.trim() || "",
    count_status: filter.statusMode,
  };
}

async function handleExportExcel() {
  await runExport("excel", () =>
    exportFacultySummaryExcel(buildExportParams()),
  );
}

async function handleExportPdf() {
  await runExport("pdf", () => exportFacultySummaryPdf(buildExportParams()));
}

function goToFacultyApproval(activityId: number) {
  void router.push({
    name: "works.facapprovals",
    query: { activity_id: String(activityId) },
  });
}
</script>
