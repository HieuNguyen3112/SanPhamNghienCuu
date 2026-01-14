<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Thống kê giờ nghiên cứu khoa học"
          subtitle="Tổng quan tình hình thực hiện giờ NCKH của giảng viên"
          :show-export-pdf="true"
          :show-export-excel="true"
          @exportPdfClicked="handleExport('pdf')"
          @exportExcelClicked="handleExport('excel')"
        />
      </div>

      <LecturerResearchHourFilterPanel
        :faculty-options="filterOptions.faculties"
        :academic-year-options="filterOptions.academicYears"
        :status-options="filterOptions.statusOptions"
        v-model:selectedFacultyId="selectedFacultyId"
        v-model:selectedAcademicYearId="selectedAcademicYearId"
        v-model:selectedStatus="selectedStatus"
        @resetFilters="resetFilters"
      />

      <div
        v-if="errorMessage"
        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 shadow-sm"
      >
        {{ errorMessage }}
      </div>

      <LecturerResearchHourSummaryCards
        :total-lecturer-count="kpis.lecturerCount"
        :total-research-hour-count="kpis.totalHours"
        :average-research-hours-per-lecturer="kpis.avgHours"
        :lecturer-meeting-research-hour-standard-percentage="kpis.complianceRate"
      />

      <LecturerResearchHourChartSection :charts="charts" />

      <LecturerResearchHourStatisticsTable
        :rows="table.items"
        :pagination="table.pagination"
        :loading="isLoading"
        @pageChanged="changePage"
        @pageSizeChanged="changePageSize"
      />

      <div
        v-if="notificationMessage"
        class="rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-sm"
      >
        {{ notificationMessage }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import LecturerResearchHourFilterPanel from "../components/LecturerResearchHourFilterPanel.vue";
import LecturerResearchHourSummaryCards from "../components/LecturerResearchHourSummaryCards.vue";
import LecturerResearchHourChartSection from "../components/LecturerResearchHourChartSection.vue";
import LecturerResearchHourStatisticsTable from "../components/LecturerResearchHourStatisticsTable.vue";
import {
  exportHourResearchReportExcel,
  exportHourResearchReportPdf,
  fetchHourResearchReport,
  fetchHourResearchReportFilters,
} from "../api/hourResearchReportApi";
import type {
  HourResearchReportCharts,
  HourResearchReportFiltersResponse,
  HourResearchReportKpis,
  HourResearchReportTable,
  HourResearchStatusCode,
} from "../hourResearchReportTypes";

const selectedFacultyId = ref<number | "ALL">("ALL");
const selectedAcademicYearId = ref<number | "ALL">("ALL");
const selectedStatus = ref<HourResearchStatusCode>("all");

const page = ref(1);
const pageSize = ref(12);

const isLoading = ref(false);
const errorMessage = ref("");
const notificationMessage = ref("");
const exporting = ref<"pdf" | "excel" | null>(null);

const filterOptions = ref<HourResearchReportFiltersResponse>({
  faculties: [],
  academicYears: [],
  statusOptions: [],
});

const kpis = ref<HourResearchReportKpis>({
  lecturerCount: 0,
  totalHours: 0,
  avgHours: 0,
  metCount: 0,
  notMetCount: 0,
  complianceRate: 0,
});

const charts = ref<HourResearchReportCharts>({
  hoursByFaculty: { labels: [], values: [] },
  statusDistribution: { labels: [], values: [] },
  hoursByYear: { labels: [], values: [] },
});

const table = ref<HourResearchReportTable>({
  items: [],
  pagination: { page: 1, perPage: 12, total: 0, lastPage: 1 },
});

function buildQueryParams() {
  const params: Record<string, string | number> = {
    page: page.value,
    per_page: pageSize.value,
  };

  if (selectedFacultyId.value !== "ALL") {
    params.faculty_id = selectedFacultyId.value;
  }
  if (selectedAcademicYearId.value !== "ALL") {
    params.academic_year_id = selectedAcademicYearId.value;
  }
  if (selectedStatus.value !== "all") {
    params.status = selectedStatus.value;
  }

  return params;
}

function buildExportParams() {
  const params: Record<string, string | number> = {};

  if (selectedFacultyId.value !== "ALL") {
    params.faculty_id = selectedFacultyId.value;
  }
  if (selectedAcademicYearId.value !== "ALL") {
    params.academic_year_id = selectedAcademicYearId.value;
  }
  if (selectedStatus.value !== "all") {
    params.status = selectedStatus.value;
  }

  return params;
}

async function loadFilters() {
  try {
    filterOptions.value = await fetchHourResearchReportFilters();
  } catch (error) {
    console.error(error);
    errorMessage.value =
      "Không thể tải dữ liệu bộ lọc. Vui lòng thử lại.";
  }
}

async function loadReport() {
  isLoading.value = true;
  errorMessage.value = "";
  try {
    const data = await fetchHourResearchReport(buildQueryParams());
    kpis.value = data.kpis;
    charts.value = data.charts;
    table.value = data.table;
    page.value = data.table.pagination.page;
    pageSize.value = data.table.pagination.perPage;
  } catch (error) {
    console.error(error);
    errorMessage.value =
      "Không thể tải báo cáo giờ NCKH. Vui lòng thử lại.";
  } finally {
    isLoading.value = false;
  }
}

function resetFilters() {
  selectedFacultyId.value = "ALL";
  selectedAcademicYearId.value = "ALL";
  selectedStatus.value = "all";
  page.value = 1;
  void loadReport();
}

function changePage(nextPage: number) {
  page.value = nextPage;
  void loadReport();
}

function changePageSize(nextPageSize: number) {
  pageSize.value = nextPageSize;
  page.value = 1;
  void loadReport();
}

function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  window.URL.revokeObjectURL(url);
}

async function handleExport(type: "pdf" | "excel") {
  if (exporting.value) return;
  exporting.value = type;

  try {
    const params = buildExportParams();
    const result =
      type === "excel"
        ? await exportHourResearchReportExcel(params)
        : await exportHourResearchReportPdf(params);
    downloadBlob(result.blob, result.filename);
    notificationMessage.value = "Xuất báo cáo thành công.";
  } catch (error) {
    console.error(error);
    notificationMessage.value = "Không thể xuất báo cáo. Vui lòng thử lại.";
  } finally {
    exporting.value = null;
    window.setTimeout(() => {
      notificationMessage.value = "";
    }, 2500);
  }
}

watch(
  [selectedFacultyId, selectedAcademicYearId, selectedStatus],
  () => {
    page.value = 1;
    void loadReport();
  }
);

onMounted(async () => {
  await loadFilters();
  await loadReport();
});
</script>
