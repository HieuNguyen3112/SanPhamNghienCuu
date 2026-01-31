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
          @exportPdfClicked="handleExport('pdf')"
          @exportExcelClicked="handleExport('excel')"
        />
      </div>

      <LecturerFilterPanel
        :filters="filters"
        :faculty-options="filterOptions.faculties"
        :degree-options="filterOptions.degrees"
        :academic-rank-options="filterOptions.academicRanks"
        :gender-options="filterOptions.genders"
        :faculty-locked="isFacultyScope"
        @filtersUpdated="applyFilters"
        @resetRequested="resetFilters"
      />

      <div
        v-if="errorMessage"
        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 shadow-sm"
      >
        {{ errorMessage }}
      </div>

      <LecturerSummaryCards :summary="summary" />

      <LecturerChartSection :charts="charts" />

      <LecturerStatisticsTable
        :rows="table.items"
        :pagination="table.pagination"
        :sort="sort"
        :loading="isLoading"
        @sortChanged="applySort"
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
import { computed, onMounted, ref } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import LecturerFilterPanel from "../components/LecturerFilterPanel.vue";
import LecturerSummaryCards from "../components/LecturerSummaryCards.vue";
import LecturerChartSection from "../components/LecturerChartSection.vue";
import LecturerStatisticsTable from "../components/LecturerStatisticsTable.vue";
import { useUserStore } from "@/app/stores/userStore";
import {
  exportLecturerReportExcel,
  exportLecturerReportPdf,
  fetchLecturerReport,
  fetchLecturerReportFilters,
} from "../api/lecturerReportApi";
import {
  exportFacultyLecturerReportExcel,
  exportFacultyLecturerReportPdf,
  fetchFacultyLecturerReport,
  fetchFacultyLecturerReportFilters,
} from "@/features/faculty/reports/lecturers/services/facultyLecturerReportService";
import type {
  LecturerReportCharts,
  LecturerReportFilters,
  LecturerReportFiltersResponse,
  LecturerReportSummary,
  LecturerReportTable,
  LecturerSortCondition,
} from "../lecturerReportTypes";

const userStore = useUserStore();
const isFacultyScope = computed(
  () => userStore.role === "DEPARTMENT_BOARD"
);

const defaultFilters: LecturerReportFilters = {
  facultyId: "ALL",
  degreeId: "ALL",
  academicRankId: "ALL",
  gender: "ALL",
};

const filters = ref<LecturerReportFilters>({ ...defaultFilters });
const sort = ref<LecturerSortCondition>({
  sortFieldIdentifier: "full_name",
  sortDirection: "asc",
});

const page = ref(1);
const pageSize = ref(12);

const isLoading = ref(false);
const errorMessage = ref("");
const notificationMessage = ref("");
const exporting = ref<"pdf" | "excel" | null>(null);

const filterOptions = ref<LecturerReportFiltersResponse>({
  faculties: [],
  degrees: [],
  academicRanks: [],
  genders: [],
});

const summary = ref<LecturerReportSummary>({
  totalLecturers: 0,
  doctorCount: 0,
  masterCount: 0,
  bachelorCount: 0,
  professorAssociateCount: 0,
});

const charts = ref<LecturerReportCharts>({
  byFaculty: { labels: [], values: [] },
  byDegree: { labels: [], values: [] },
  byAcademicRank: { labels: [], values: [] },
  byGender: { labels: [], values: [] },
});

const table = ref<LecturerReportTable>({
  items: [],
  pagination: { page: 1, perPage: 12, total: 0, lastPage: 1 },
});

const scopeFacultyId = ref<number | null>(null);

function buildQueryParams() {
  const params: Record<string, string | number> = {
    sort: `${sort.value.sortFieldIdentifier}:${sort.value.sortDirection}`,
    page: page.value,
    per_page: pageSize.value,
  };

  if (isFacultyScope.value) {
    if (scopeFacultyId.value) {
      params.faculty_id = scopeFacultyId.value;
    }
  } else if (filters.value.facultyId !== "ALL") {
    params.faculty_id = filters.value.facultyId;
  }

  if (filters.value.degreeId !== "ALL") {
    params.degree_id = filters.value.degreeId;
  }
  if (filters.value.academicRankId !== "ALL") {
    params.academic_rank_id = filters.value.academicRankId;
  }
  if (filters.value.gender !== "ALL") {
    params.gender = filters.value.gender;
  }

  return params;
}

function buildExportParams() {
  const params: Record<string, string | number> = {
    sort: `${sort.value.sortFieldIdentifier}:${sort.value.sortDirection}`,
  };

  if (isFacultyScope.value) {
    if (scopeFacultyId.value) {
      params.faculty_id = scopeFacultyId.value;
    }
  } else if (filters.value.facultyId !== "ALL") {
    params.faculty_id = filters.value.facultyId;
  }

  if (filters.value.degreeId !== "ALL") {
    params.degree_id = filters.value.degreeId;
  }
  if (filters.value.academicRankId !== "ALL") {
    params.academic_rank_id = filters.value.academicRankId;
  }
  if (filters.value.gender !== "ALL") {
    params.gender = filters.value.gender;
  }

  return params;
}

async function loadFilters() {
  try {
    filterOptions.value = isFacultyScope.value
      ? await fetchFacultyLecturerReportFilters()
      : await fetchLecturerReportFilters();

    if (isFacultyScope.value) {
      const scoped = filterOptions.value.faculties[0]?.id ?? null;
      scopeFacultyId.value = scoped;
      if (scoped) {
        filters.value.facultyId = scoped;
      }
    }
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
    const data = isFacultyScope.value
      ? await fetchFacultyLecturerReport(buildQueryParams())
      : await fetchLecturerReport(buildQueryParams());
    summary.value = data.summary;
    charts.value = data.charts;
    table.value = data.table;
  } catch (error) {
    console.error(error);
    errorMessage.value =
      "Không thể tải báo cáo giảng viên. Vui lòng thử lại.";
  } finally {
    isLoading.value = false;
  }
}

function applyFilters(nextFilters: LecturerReportFilters) {
  filters.value = { ...nextFilters };
  if (isFacultyScope.value && scopeFacultyId.value) {
    filters.value.facultyId = scopeFacultyId.value;
  }
  page.value = 1;
  void loadReport();
}

function resetFilters() {
  filters.value = { ...defaultFilters };
  if (isFacultyScope.value && scopeFacultyId.value) {
    filters.value.facultyId = scopeFacultyId.value;
  }
  page.value = 1;
  void loadReport();
}

function applySort(nextSort: LecturerSortCondition) {
  sort.value = { ...nextSort };
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
        ? isFacultyScope.value
          ? await exportFacultyLecturerReportExcel(params)
          : await exportLecturerReportExcel(params)
        : isFacultyScope.value
          ? await exportFacultyLecturerReportPdf(params)
          : await exportLecturerReportPdf(params);
    downloadBlob(result.blob, result.filename);
    notificationMessage.value = "Xuất báo cáo thành công.";
  } catch (error) {
    console.error(error);
    notificationMessage.value =
      "Không thể xuất báo cáo. Vui lòng thử lại.";
  } finally {
    exporting.value = null;
    window.setTimeout(() => {
      notificationMessage.value = "";
    }, 2500);
  }
}

onMounted(async () => {
  await loadFilters();
  await loadReport();
});
</script>
