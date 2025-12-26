<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 gap-4 p-4 md:p-6">
      <!-- Title -->
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Thống kê công trình nghiên cứu khoa học"
          subtitle="Tổng quan công trình nghiên cứu khoa học trong trường đại học"
          :show-export-pdf="true"
          :show-export-excel="true"
          exportPdfClicked="showExportNotImplementedMessage('PDF')"
          @exportExcelClicked="showExportNotImplementedMessage('Excel')"
        />
      </div>

      <!-- Filter Bar -->
      <FilterBar
        class="mb-6"
        :years="yearOptions"
        :departments="departments"
        :research-types="researchTypeOptions"
        :lecturers="lecturers"
        v-model:year="filters.year"
        v-model:department-id="filters.departmentId"
        v-model:research-type="filters.researchType"
        v-model:lecturer-id="filters.lecturerId"
        @reset="resetFilters"
      />

      <!-- KPI Cards -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <KpiCard
          title="Tổng công trình"
          :value="kpi.total"
          subtitle="Tổng số công trình theo bộ lọc"
        />
        <KpiCard
          title="ISI / Scopus"
          :value="kpi.isiScopus"
          subtitle="Bài báo quốc tế (ISI/Scopus)"
        />
        <KpiCard
          title="Hội nghị"
          :value="kpi.conference"
          subtitle="Proceedings / Conference papers"
        />
        <KpiCard
          title="Đề tài"
          :value="kpi.project"
          subtitle="Dự án / Nhiệm vụ NCKH"
        />
        <KpiCard
          title="Sách / GT"
          :value="kpi.book"
          subtitle="Books / Textbooks"
        />
      </div>

      <!-- Charts -->
      <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <ChartCard class="xl:col-span-7" title="Công trình theo khoa (Stacked)">
          <DepartmentStackedBarChart
            :labels="chartDept.labels"
            :isi="chartDept.isi"
            :scopus="chartDept.scopus"
            :conference="chartDept.conference"
          />
        </ChartCard>

        <ChartCard class="xl:col-span-5" title="Phân bố theo loại (Donut)">
          <ResearchTypeDonutChart
            :labels="chartType.labels"
            :values="chartType.values"
          />
        </ChartCard>

        <ChartCard class="xl:col-span-12" title="Công trình theo năm (Line)">
          <WorksOverYearsLineChart
            :labels="chartYear.labels"
            :values="chartYear.values"
          />
        </ChartCard>
      </div>

      <!-- Table -->
      <ChartCard title="Danh sách công trình">
        <ResearchTable
          :rows="filteredWorks"
          :departments="departments"
          :lecturers="lecturers"
          @row-click="openDetail"
        />
      </ChartCard>

      <!-- Modal placeholder -->
      <ResearchDetailModal
        :open="detailModal.open"
        :work="detailModal.work"
        :departments="departments"
        :lecturers="lecturers"
        @close="closeDetail"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import FilterBar from "../components/FilterBar.vue";
import KpiCard from "../components/KpiCard.vue";
import ChartCard from "../components/ChartCard.vue";
import ResearchTable from "../components/ResearchTable.vue";
import ResearchDetailModal from "../components/ResearchDetailModal.vue";
import DepartmentStackedBarChart from "..//components/charts/DepartmentStackedBarChart.vue";
import WorksOverYearsLineChart from "..//components/charts/WorksOverYearsLineChart.vue";
import ResearchTypeDonutChart from "..//components/charts/ResearchTypeDonutChart.vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import {
  useResearchMockData,
  type ResearchWork,
  type ResearchType,
} from "../useResearchMockData";

const { departments, lecturers, works, formatResearchTypeLabel } =
  useResearchMockData();

/**
 * Filters (reactive + v-model)
 * Use string 'all' for selects to keep v-model simple.
 */
const filters = reactive({
  year: "all" as string, // 'all' | '2020' | ...
  departmentId: "all" as string, // 'all' | deptId
  researchType: "all" as string, // 'all' | ResearchType
  lecturerId: "all" as string, // 'all' | lecturerId
});
const temporaryNotificationMessage = ref("");
function resetFilters() {
  filters.year = "all";
  filters.departmentId = "all";
  filters.researchType = "all";
  filters.lecturerId = "all";
}

/** Options */
const yearOptions = computed(() => {
  const years = Array.from(new Set(works.value.map((w) => w.year))).sort(
    (a, b) => b - a
  );
  return years.map(String);
});

const researchTypeOptions = computed(() => {
  const allTypes: ResearchType[] = [
    "ISI",
    "SCOPUS",
    "CONFERENCE",
    "PROJECT",
    "BOOK",
  ];
  return allTypes.map((value) => ({
    value,
    label: formatResearchTypeLabel(value),
  }));
});

/** Filtering */
const filteredWorks = computed(() => {
  return works.value.filter((work) => {
    if (filters.year !== "all" && String(work.year) !== filters.year)
      return false;
    if (
      filters.departmentId !== "all" &&
      work.departmentId !== filters.departmentId
    )
      return false;
    if (
      filters.researchType !== "all" &&
      work.type !== (filters.researchType as ResearchType)
    )
      return false;
    if (
      filters.lecturerId !== "all" &&
      !work.lecturerIds.includes(filters.lecturerId)
    )
      return false;
    return true;
  });
});

/** KPI */
const kpi = computed(() => {
  const rows = filteredWorks.value;
  const isi = rows.filter((w) => w.type === "ISI").length;
  const scopus = rows.filter((w) => w.type === "SCOPUS").length;
  const conference = rows.filter((w) => w.type === "CONFERENCE").length;
  const project = rows.filter((w) => w.type === "PROJECT").length;
  const book = rows.filter((w) => w.type === "BOOK").length;

  return {
    total: rows.length,
    isiScopus: isi + scopus,
    conference,
    project,
    book,
  };
});

/** Charts - aggregates */
const chartDept = computed(() => {
  const labelByDeptId = new Map(departments.value.map((d) => [d.id, d.name]));
  const deptIds = departments.value.map((d) => d.id);

  const isiCounts = new Map<string, number>();
  const scopusCounts = new Map<string, number>();
  const confCounts = new Map<string, number>();

  for (const deptId of deptIds) {
    isiCounts.set(deptId, 0);
    scopusCounts.set(deptId, 0);
    confCounts.set(deptId, 0);
  }

  for (const work of filteredWorks.value) {
    if (work.type === "ISI")
      isiCounts.set(
        work.departmentId,
        (isiCounts.get(work.departmentId) ?? 0) + 1
      );
    if (work.type === "SCOPUS")
      scopusCounts.set(
        work.departmentId,
        (scopusCounts.get(work.departmentId) ?? 0) + 1
      );
    if (work.type === "CONFERENCE")
      confCounts.set(
        work.departmentId,
        (confCounts.get(work.departmentId) ?? 0) + 1
      );
  }

  const labels = deptIds.map((id) => labelByDeptId.get(id) ?? id);
  return {
    labels,
    isi: deptIds.map((id) => isiCounts.get(id) ?? 0),
    scopus: deptIds.map((id) => scopusCounts.get(id) ?? 0),
    conference: deptIds.map((id) => confCounts.get(id) ?? 0),
  };
});

const chartYear = computed(() => {
  // Uses filteredWorks, so if Year filter is fixed -> line becomes 1 point (still consistent with filters).
  const years = Array.from(
    new Set(filteredWorks.value.map((w) => w.year))
  ).sort((a, b) => a - b);
  const countByYear = new Map<number, number>(years.map((y) => [y, 0]));

  for (const work of filteredWorks.value) {
    countByYear.set(work.year, (countByYear.get(work.year) ?? 0) + 1);
  }

  return {
    labels: years.map(String),
    values: years.map((y) => countByYear.get(y) ?? 0),
  };
});

const chartType = computed(() => {
  const typeOrder: ResearchType[] = [
    "ISI",
    "SCOPUS",
    "CONFERENCE",
    "PROJECT",
    "BOOK",
  ];
  const countByType = new Map<ResearchType, number>(
    typeOrder.map((t) => [t, 0])
  );

  for (const work of filteredWorks.value) {
    countByType.set(work.type, (countByType.get(work.type) ?? 0) + 1);
  }

  return {
    labels: typeOrder.map(formatResearchTypeLabel),
    values: typeOrder.map((t) => countByType.get(t) ?? 0),
  };
});
function showExportNotImplementedMessage(exportFormatName: "PDF" | "Excel") {
  temporaryNotificationMessage.value = `Chức năng xuất ${exportFormatName} hiện chỉ là giao diện (UI-only) theo yêu cầu.`;
  window.setTimeout(() => {
    temporaryNotificationMessage.value = "";
  }, 2500);
}
/** Modal placeholder */
const detailModal = reactive({
  open: false,
  work: null as ResearchWork | null,
});

function openDetail(work: ResearchWork) {
  detailModal.work = work;
  detailModal.open = true;
}

function closeDetail() {
  detailModal.open = false;
  detailModal.work = null;
}
</script>
