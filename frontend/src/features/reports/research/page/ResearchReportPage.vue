<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 gap-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Thống kê công trình nghiên cứu khoa học"
          subtitle="Tổng quan công trình nghiên cứu khoa học trong trường đại học"
          :show-export-pdf="true"
          :show-export-excel="true"
          :export-pdf-disabled="Boolean(exporting)"
          :export-excel-disabled="Boolean(exporting)"
          @exportPdfClicked="handleExport('pdf')"
          @exportExcelClicked="handleExport('excel')"
        />
      </div>

      <FilterBar
        class="mb-6"
        :years="filterOptions.years"
        :departments="filterOptions.departments"
        :research-types="filterOptions.researchTypes"
        :lecturers="filterOptions.lecturers"
        v-model:year="filters.year"
        v-model:department-id="filters.departmentId"
        v-model:research-type="filters.researchType"
        v-model:lecturer-id="filters.lecturerId"
        @reset="resetFilters"
      />

      <div
        v-if="errorMessage"
        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 shadow-sm"
      >
        {{ errorMessage }}
      </div>

      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <KpiCard
          title="Tổng công trình"
          :value="kpis.totalCount"
          subtitle="Tổng số công trình theo bộ lọc"
        />
        <KpiCard
          title="ISI / Scopus"
          :value="kpis.isiCount + kpis.scopusCount"
          subtitle="Bài báo quốc tế (ISI/Scopus)"
        />
        <KpiCard
          title="Hội nghị"
          :value="kpis.conferenceCount"
          subtitle="Kỷ yếu / Hội nghị"
        />
        <KpiCard
          title="Đề tài"
          :value="kpis.projectCount"
          subtitle="Dự án / Nhiệm vụ NCKH"
        />
        <KpiCard
          title="Sách / GT"
          :value="kpis.bookCount"
          subtitle="Sách / Giáo trình"
        />
      </div>

      <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <ChartCard class="xl:col-span-7" title="Công trình theo khoa (Stacked)">
          <DepartmentStackedBarChart
            :labels="charts.byDepartment.labels"
            :isi="charts.byDepartment.isi"
            :scopus="charts.byDepartment.scopus"
            :conference="charts.byDepartment.conference"
          />
        </ChartCard>

        <ChartCard class="xl:col-span-5" title="Phân bố theo loại (Donut)">
          <ResearchTypeDonutChart
            :labels="charts.distribution.labels"
            :values="charts.distribution.values"
          />
        </ChartCard>

        <ChartCard class="xl:col-span-12" title="Công trình theo năm (Line)">
          <WorksOverYearsLineChart
            :labels="charts.byYear.labels"
            :values="charts.byYear.values"
          />
        </ChartCard>
      </div>

      <ChartCard title="Danh sách công trình">
        <ResearchTable
          :rows="table.items"
          :pagination="table.pagination"
          :sort="sort"
          :loading="isLoading"
          @row-click="openDetail"
          @sortChanged="applySort"
          @pageChanged="changePage"
          @pageSizeChanged="changePageSize"
        />
      </ChartCard>

      <ResearchDetailModal
        :open="detailModal.open"
        :work="detailModal.work"
        @close="closeDetail"
      />

    </div>
  </div>
</template>


<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import FilterBar from "../components/FilterBar.vue";
import KpiCard from "../components/KpiCard.vue";
import ChartCard from "../components/ChartCard.vue";
import ResearchTable from "../components/ResearchTable.vue";
import ResearchDetailModal from "../components/ResearchDetailModal.vue";
import DepartmentStackedBarChart from "../components/charts/DepartmentStackedBarChart.vue";
import WorksOverYearsLineChart from "../components/charts/WorksOverYearsLineChart.vue";
import ResearchTypeDonutChart from "../components/charts/ResearchTypeDonutChart.vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import { useUserStore } from "@/app/stores/userStore";
import { useExportActionFeedback } from "@/shared/composables/useExportActionFeedback";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";
import {
  exportResearchReportExcel,
  exportResearchReportPdf,
  fetchResearchReport,
  fetchResearchReportFilters,
} from "../api/researchReportApi";
import {
  exportFacultyResearchReportExcel,
  exportFacultyResearchReportPdf,
  fetchFacultyResearchReport,
  fetchFacultyResearchReportFilters,
} from "@/features/faculty/reports/research/services/facultyResearchReportService";
import type {
  ResearchReportCharts,
  ResearchReportFilters,
  ResearchReportFiltersResponse,
  ResearchReportKpis,
  ResearchReportRow,
  ResearchReportTable,
  ResearchReportSortCondition,
} from "../researchReportTypes";

const userStore = useUserStore();
const isFacultyScope = computed(() => userStore.role === "DEPARTMENT_BOARD");

const filters = reactive<ResearchReportFilters>({
  year: "all",
  departmentId: "all",
  researchType: "all",
  lecturerId: "all",
});

const sort = ref<ResearchReportSortCondition>({
  sortFieldIdentifier: "year",
  sortDirection: "desc",
});

const page = ref(1);
const pageSize = ref(12);

const isLoading = ref(false);
const errorMessage = ref("");
const { exporting, runExport } = useExportActionFeedback();
const { runPageLoad } = usePageLoadFeedback();

const filterOptions = ref<ResearchReportFiltersResponse>({
  years: [],
  departments: [],
  researchTypes: [],
  lecturers: [],
});

const kpis = ref<ResearchReportKpis>({
  totalCount: 0,
  isiCount: 0,
  scopusCount: 0,
  conferenceCount: 0,
  projectCount: 0,
  bookCount: 0,
});

const charts = ref<ResearchReportCharts>({
  byDepartment: {
    labels: [],
    isi: [],
    scopus: [],
    conference: [],
    project: [],
    book: [],
  },
  distribution: {
    labels: [],
    values: [],
  },
  byYear: {
    labels: [],
    values: [],
  },
});

const table = ref<ResearchReportTable>({
  items: [],
  pagination: { page: 1, perPage: 12, total: 0, lastPage: 1 },
});

function resetFilters() {
  filters.year = "all";
  filters.departmentId = "all";
  filters.researchType = "all";
  filters.lecturerId = "all";
  page.value = 1;
}

function buildQueryParams() {
  const params: Record<string, string | number> = {
    sort: `${sort.value.sortFieldIdentifier}:${sort.value.sortDirection}`,
    page: page.value,
    per_page: pageSize.value,
  };

  if (filters.year !== "all") {
    params.year = Number(filters.year);
  }
  if (filters.departmentId !== "all") {
    params.department_id = Number(filters.departmentId);
  }
  if (filters.researchType !== "all") {
    params.research_type = filters.researchType;
  }
  if (filters.lecturerId !== "all") {
    params.lecturer_id = Number(filters.lecturerId);
  }

  return params;
}

function buildExportParams() {
  const params: Record<string, string | number> = {
    sort: `${sort.value.sortFieldIdentifier}:${sort.value.sortDirection}`,
  };

  if (filters.year !== "all") {
    params.year = Number(filters.year);
  }
  if (filters.departmentId !== "all") {
    params.department_id = Number(filters.departmentId);
  }
  if (filters.researchType !== "all") {
    params.research_type = filters.researchType;
  }
  if (filters.lecturerId !== "all") {
    params.lecturer_id = Number(filters.lecturerId);
  }

  return params;
}

async function loadFilters() {
  try {
    filterOptions.value = isFacultyScope.value
      ? await fetchFacultyResearchReportFilters()
      : await fetchResearchReportFilters();
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
      ? await fetchFacultyResearchReport(buildQueryParams())
      : await fetchResearchReport(buildQueryParams());
    kpis.value = data.kpis;
    charts.value = data.charts;
    table.value = data.table;
    page.value = data.table.pagination.page;
    pageSize.value = data.table.pagination.perPage;
  } catch (error) {
    console.error(error);
    errorMessage.value =
      "Không thể tải báo cáo công trình. Vui lòng thử lại.";
  } finally {
    isLoading.value = false;
  }
}

async function refreshReportWithFeedback() {
  await runPageLoad(() => loadReport(), {
    loading: {
      title: "Đang tải thống kê công trình",
      message: "Hệ thống đang cập nhật dữ liệu thống kê công trình nghiên cứu khoa học...",
    },
  });
}

function applySort(nextSort: ResearchReportSortCondition) {
  sort.value = { ...nextSort };
  page.value = 1;
  void refreshReportWithFeedback();
}

function changePage(nextPage: number) {
  page.value = nextPage;
  void refreshReportWithFeedback();
}

function changePageSize(nextPageSize: number) {
  pageSize.value = nextPageSize;
  page.value = 1;
  void refreshReportWithFeedback();
}

async function handleExport(type: "pdf" | "excel") {
  await runExport(type, async () => {
    const params = buildExportParams();
    return (
      type === "excel"
        ? isFacultyScope.value
          ? await exportFacultyResearchReportExcel(params)
          : await exportResearchReportExcel(params)
        : isFacultyScope.value
          ? await exportFacultyResearchReportPdf(params)
          : await exportResearchReportPdf(params)
    );
  });
}

const detailModal = reactive({
  open: false,
  work: null as ResearchReportRow | null,
});

function openDetail(work: ResearchReportRow) {
  detailModal.work = work;
  detailModal.open = true;
}

function closeDetail() {
  detailModal.open = false;
  detailModal.work = null;
}

watch(
  () => [
    filters.year,
    filters.departmentId,
    filters.researchType,
    filters.lecturerId,
  ],
  () => {
    page.value = 1;
    void refreshReportWithFeedback();
  }
);

onMounted(async () => {
  await runPageLoad(
    async () => {
      await loadFilters();
      await loadReport();
    },
    {
      loading: {
        title: "Đang khởi tạo thống kê công trình",
        message: "Hệ thống đang chuẩn bị dữ liệu thống kê công trình nghiên cứu khoa học...",
      },
    },
  );
});
</script>
