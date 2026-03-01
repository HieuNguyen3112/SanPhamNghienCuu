<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
        <PageHeader
          title="Quản lý giờ nghiên cứu khoa học theo giảng viên"
          subtitle="Theo dõi tình hình thực hiện giờ NCKH của giảng viên trong khoa"
          :year-options="yearOptions"
          :selected-year-id="filter.yearId ?? undefined"
          @change-year="applyFilter({ yearId: $event })"
          @exportPdfClicked="onExportPdf"
          @exportExcelClicked="onExportExcel"
        />
      </div>

      <div class="mt-4">
        <HoursFilterPanel
          :filter="filter"
          :year-options="yearOptions"
          @apply-filter="applyFilter"
          @reset="resetFilter"
        />
      </div>

      <div class="mt-4">
        <HoursKpiOverview
          :total-lecturers="totalLecturers"
          :hit-count="hitCount"
          :miss-count="missCount"
          :hit-rate="hitRate"
        />
      </div>

      <div class="mt-4">
        <LecturerHoursTable
          :overview="overview"
          :loading="loadingOverview"
          :error="errorOverview"
          :current-page-number="currentPageNumber"
          :page-size="pageSize"
          :total-item-count="totalItemCount"
          @view="openDrawer"
          @update:currentPageNumber="updatePage"
          @update:pageSize="updatePageSize"
        />
      </div>

      <LecturerHoursDrawer
        :open="drawerOpen"
        :lecturer="selectedLecturerOverview"
        :academic-year-code="selectedAcademicYearCode"
        :detail-rows="detailRows"
        :loading-detail="loadingDetail"
        :error-detail="errorDetail"
        @close="closeDrawer"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import HoursFilterPanel from "@/features/scientific/management/lecturer-hours-management/components/HoursFilterPanel.vue";
import HoursKpiOverview from "@/features/scientific/management/lecturer-hours-management/components/HoursKpiOverview.vue";
import LecturerHoursTable from "@/features/scientific/management/lecturer-hours-management/components/LecturerHoursTable.vue";
import LecturerHoursDrawer from "@/features/scientific/management/lecturer-hours-management/components/LecturerHoursDrawer.vue";
import { useLecturerHoursManagement } from "@/features/scientific/management/lecturer-hours-management/composables/useLecturerHoursManagement";
import {
  exportFacultyHoursExcel,
  exportFacultyHoursPdf,
} from "@/features/scientific/management/lecturer-hours-management/services/facultyLecturerHoursExport.service";
import { facultyLecturerHoursService } from "@/features/scientific/management/lecturer-hours-management/services/facultyLecturerHoursService";
import { useExportActionFeedback } from "@/shared/composables/useExportActionFeedback";

const { runExport } = useExportActionFeedback();

const {
  filter,
  overview,
  yearOptions,
  selectedLecturerOverview,
  drawerOpen,
  detailRows,
  totalLecturers,
  hitCount,
  missCount,
  hitRate,
  currentPageNumber,
  pageSize,
  totalItemCount,
  loadingOverview,
  loadingDetail,
  errorOverview,
  errorDetail,
  loadOverview,
  applyFilter,
  resetFilter,
  openDrawer,
  closeDrawer,
  updatePage,
  updatePageSize,
} = useLecturerHoursManagement({
  initialYearId: null,
  initialFacultyId: null,
  service: facultyLecturerHoursService,
});

const selectedAcademicYearCode = computed(() => {
  return yearOptions.value.find((y) => y.id === filter.yearId)?.code ?? "";
});

function buildExportParams() {
  return {
    academic_year_id: filter.yearId ?? null,
    kpi_status: filter.kpiStatus,
    q: filter.keyword,
  };
}

async function onExportExcel() {
  await runExport("excel", () => exportFacultyHoursExcel(buildExportParams()));
}

async function onExportPdf() {
  await runExport("pdf", () => exportFacultyHoursPdf(buildExportParams()));
}

onMounted(() => {
  void loadOverview();
});
</script>
