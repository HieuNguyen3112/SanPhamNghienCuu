<template>
  <section class="grid grid-cols-1 gap-3 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">Giờ NCKH theo khoa</h2>
      </div>
      <div class="h-72">
        <canvas ref="researchHoursByFacultyBarChartCanvasElement"></canvas>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">
          Phân bố giảng viên theo mức giờ NCKH
        </h2>
      </div>
      <div class="h-72">
        <canvas ref="lecturerDistributionDoughnutChartCanvasElement"></canvas>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">
          Giờ NCKH theo năm học
        </h2>
      </div>
      <div class="h-72">
        <canvas ref="researchHoursByAcademicYearBarChartCanvasElement"></canvas>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">
          Tỉ lệ giảng viên đạt / chưa đạt chuẩn
        </h2>
      </div>
      <div class="h-72">
        <canvas ref="lecturerStandardPieChartCanvasElement"></canvas>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Chart } from "chart.js/auto";
import type { ChartOptions } from "chart.js";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import type { HourResearchReportCharts } from "../hourResearchReportTypes";

const componentProperties = defineProps<{
  charts: HourResearchReportCharts;
}>();

const researchHoursByFacultyBarChartCanvasElement =
  ref<HTMLCanvasElement | null>(null);
const lecturerDistributionDoughnutChartCanvasElement =
  ref<HTMLCanvasElement | null>(null);
const researchHoursByAcademicYearBarChartCanvasElement =
  ref<HTMLCanvasElement | null>(null);
const lecturerStandardPieChartCanvasElement = ref<HTMLCanvasElement | null>(
  null
);

const researchHoursByFacultyBarChartInstance = ref<Chart | null>(null);
const lecturerDistributionDoughnutChartInstance = ref<Chart | null>(null);
const researchHoursByAcademicYearBarChartInstance = ref<Chart | null>(null);
const lecturerStandardPieChartInstance = ref<Chart | null>(null);

function destroyAllChartInstances(): void {
  researchHoursByFacultyBarChartInstance.value?.destroy();
  lecturerDistributionDoughnutChartInstance.value?.destroy();
  researchHoursByAcademicYearBarChartInstance.value?.destroy();
  lecturerStandardPieChartInstance.value?.destroy();

  researchHoursByFacultyBarChartInstance.value = null;
  lecturerDistributionDoughnutChartInstance.value = null;
  researchHoursByAcademicYearBarChartInstance.value = null;
  lecturerStandardPieChartInstance.value = null;
}

const researchHoursByFacultyChartConfiguration = computed(() => ({
  labels: componentProperties.charts.hoursByFaculty.labels,
  datasets: [
    {
      label: "Tổng giờ NCKH",
      backgroundColor: "rgba(30, 41, 59, 0.85)",
      borderColor: "rgba(30, 41, 59, 1)",
      borderWidth: 1,
      data: componentProperties.charts.hoursByFaculty.values,
    },
  ],
}));

const researchHoursByAcademicYearChartConfiguration = computed(() => ({
  labels: componentProperties.charts.hoursByYear.labels,
  datasets: [
    {
      label: "Tổng giờ NCKH",
      backgroundColor: "rgba(51, 65, 85, 0.85)",
      borderColor: "rgba(51, 65, 85, 1)",
      borderWidth: 1,
      data: componentProperties.charts.hoursByYear.values,
    },
  ],
}));

const lecturerStandardDistributionChartConfiguration = computed(() => ({
  labels: componentProperties.charts.statusDistribution.labels,
  datasets: [
    {
      label: "Số giảng viên",
      backgroundColor: [
        "rgba(16, 185, 129, 0.25)",
        "rgba(244, 63, 94, 0.22)",
      ],
      borderColor: ["rgba(16, 185, 129, 0.9)", "rgba(244, 63, 94, 0.9)"],
      borderWidth: 1,
      data: componentProperties.charts.statusDistribution.values,
    },
  ],
}));

const researchHourBarChartDisplayOptions: ChartOptions<"bar"> = {
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  plugins: {
    legend: {
      position: "bottom",
      labels: {
        boxWidth: 12,
        boxHeight: 12,
        usePointStyle: true,
      },
    },
    tooltip: { enabled: true },
  },
  scales: {
    x: {
      grid: { color: "rgba(148, 163, 184, 0.25)" },
      ticks: { color: "rgba(15, 23, 42, 0.85)" },
    },
    y: {
      beginAtZero: true,
      grid: { color: "rgba(148, 163, 184, 0.25)" },
      ticks: { color: "rgba(15, 23, 42, 0.85)" },
    },
  },
};

const lecturerDistributionDoughnutChartDisplayOptions: ChartOptions<"doughnut"> =
  {
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    plugins: {
      legend: {
        position: "bottom",
        labels: {
          boxWidth: 12,
          boxHeight: 12,
          usePointStyle: true,
        },
      },
      tooltip: { enabled: true },
    },
  };

const lecturerStandardPieChartDisplayOptions: ChartOptions<"pie"> = {
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  plugins: {
    legend: {
      position: "bottom",
      labels: {
        boxWidth: 12,
        boxHeight: 12,
        usePointStyle: true,
      },
    },
    tooltip: { enabled: true },
  },
};

function renderAllCharts(): void {
  destroyAllChartInstances();

  if (researchHoursByFacultyBarChartCanvasElement.value) {
    researchHoursByFacultyBarChartInstance.value = new Chart(
      researchHoursByFacultyBarChartCanvasElement.value,
      {
        type: "bar",
        data: researchHoursByFacultyChartConfiguration.value,
        options: researchHourBarChartDisplayOptions,
      }
    );
  }

  if (lecturerDistributionDoughnutChartCanvasElement.value) {
    lecturerDistributionDoughnutChartInstance.value = new Chart(
      lecturerDistributionDoughnutChartCanvasElement.value,
      {
        type: "doughnut",
        data: lecturerStandardDistributionChartConfiguration.value,
        options: lecturerDistributionDoughnutChartDisplayOptions,
      }
    );
  }

  if (researchHoursByAcademicYearBarChartCanvasElement.value) {
    researchHoursByAcademicYearBarChartInstance.value = new Chart(
      researchHoursByAcademicYearBarChartCanvasElement.value,
      {
        type: "bar",
        data: researchHoursByAcademicYearChartConfiguration.value,
        options: researchHourBarChartDisplayOptions,
      }
    );
  }

  if (lecturerStandardPieChartCanvasElement.value) {
    lecturerStandardPieChartInstance.value = new Chart(
      lecturerStandardPieChartCanvasElement.value,
      {
        type: "pie",
        data: lecturerStandardDistributionChartConfiguration.value,
        options: lecturerStandardPieChartDisplayOptions,
      }
    );
  }
}

onMounted(() => {
  renderAllCharts();
});

watch(
  () => componentProperties.charts,
  () => {
    renderAllCharts();
  },
  { deep: true }
);

onBeforeUnmount(() => {
  destroyAllChartInstances();
});
</script>
