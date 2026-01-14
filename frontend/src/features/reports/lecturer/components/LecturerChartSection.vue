<template>
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-900">
          Giảng viên theo khoa
        </h3>
        <p class="text-xs text-slate-500">
          Số lượng giảng viên theo từng khoa
        </p>
      </div>
      <div class="h-[300px]">
        <canvas ref="facultyBarChartCanvasElement" />
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-900">Trình độ học vấn</h3>
        <p class="text-xs text-slate-500">Phân bố trình độ đào tạo</p>
      </div>
      <div class="h-[300px]">
        <canvas ref="degreeDonutChartCanvasElement" />
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-900">Học hàm</h3>
        <p class="text-xs text-slate-500">Tỷ lệ học hàm</p>
      </div>
      <div class="h-[300px]">
        <canvas ref="academicRankPieChartCanvasElement" />
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-900">Giới tính</h3>
        <p class="text-xs text-slate-500">Số lượng giảng viên theo giới tính</p>
      </div>
      <div class="h-[300px]">
        <canvas ref="genderBarChartCanvasElement" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Chart } from "chart.js/auto";
import type { LecturerReportCharts } from "../lecturerReportTypes";

const componentProperties = defineProps<{
  charts: LecturerReportCharts;
}>();

const facultyBarChartCanvasElement = ref<HTMLCanvasElement | null>(null);
const degreeDonutChartCanvasElement = ref<HTMLCanvasElement | null>(null);
const academicRankPieChartCanvasElement = ref<HTMLCanvasElement | null>(null);
const genderBarChartCanvasElement = ref<HTMLCanvasElement | null>(null);

let facultyBarChartInstance: Chart<"bar"> | null = null;
let degreeDonutChartInstance: Chart<"doughnut"> | null = null;
let academicRankPieChartInstance: Chart<"pie"> | null = null;
let genderBarChartInstance: Chart<"bar"> | null = null;

let facultyBarChartDatasetReference: { data: number[] } | null = null;
let degreeDonutChartDatasetReference: { data: number[] } | null = null;
let academicRankPieChartDatasetReference: { data: number[] } | null = null;
let genderBarChartDatasetReference: { data: number[] } | null = null;

const facultyDistribution = computed(() => componentProperties.charts.byFaculty);
const degreeDistribution = computed(() => componentProperties.charts.byDegree);
const academicRankDistribution = computed(
  () => componentProperties.charts.byAcademicRank
);
const genderDistribution = computed(() => componentProperties.charts.byGender);

const chartUpdateSignature = computed(() => {
  return [
    facultyDistribution.value.labels.join("|"),
    facultyDistribution.value.values.join("|"),
    degreeDistribution.value.labels.join("|"),
    degreeDistribution.value.values.join("|"),
    academicRankDistribution.value.labels.join("|"),
    academicRankDistribution.value.values.join("|"),
    genderDistribution.value.labels.join("|"),
    genderDistribution.value.values.join("|"),
  ].join("::");
});

function createOrUpdateFacultyBarChart() {
  if (!facultyBarChartCanvasElement.value) return;

  if (!facultyBarChartInstance) {
    const createdDataset = {
      label: "Số lượng giảng viên",
      data: facultyDistribution.value.values,
      backgroundColor: "rgba(15, 23, 42, 0.20)",
      borderColor: "rgba(15, 23, 42, 0.50)",
      borderWidth: 1,
    };

    facultyBarChartDatasetReference = createdDataset;

    facultyBarChartInstance = new Chart(
      facultyBarChartCanvasElement.value,
      {
        type: "bar",
        data: {
          labels: facultyDistribution.value.labels,
          datasets: [createdDataset],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: false,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { mode: "index", intersect: false },
          },
          scales: {
            x: { grid: { display: false }, ticks: { color: "#334155" } },
            y: { beginAtZero: true, ticks: { color: "#334155" } },
          },
        },
      }
    );

    return;
  }

  facultyBarChartInstance.data.labels = facultyDistribution.value.labels;
  if (facultyBarChartDatasetReference) {
    facultyBarChartDatasetReference.data = facultyDistribution.value.values;
  }
  facultyBarChartInstance.update("none");
}

function createOrUpdateDegreeDonutChart() {
  if (!degreeDonutChartCanvasElement.value) return;

  if (!degreeDonutChartInstance) {
    const createdDataset = {
      label: "Số lượng",
      data: degreeDistribution.value.values,
      backgroundColor: [
        "rgba(79, 70, 229, 0.20)",
        "rgba(2, 132, 199, 0.20)",
        "rgba(100, 116, 139, 0.18)",
        "rgba(234, 179, 8, 0.18)",
      ],
      borderColor: [
        "rgba(79, 70, 229, 0.55)",
        "rgba(2, 132, 199, 0.55)",
        "rgba(100, 116, 139, 0.45)",
        "rgba(234, 179, 8, 0.45)",
      ],
      borderWidth: 1,
    };

    degreeDonutChartDatasetReference = createdDataset;

    degreeDonutChartInstance = new Chart(
      degreeDonutChartCanvasElement.value,
      {
        type: "doughnut",
        data: {
          labels: degreeDistribution.value.labels,
          datasets: [createdDataset],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "62%",
          animation: false,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { enabled: true },
          },
        },
      }
    );

    return;
  }

  degreeDonutChartInstance.data.labels = degreeDistribution.value.labels;
  if (degreeDonutChartDatasetReference) {
    degreeDonutChartDatasetReference.data = degreeDistribution.value.values;
  }
  degreeDonutChartInstance.update("none");
}

function createOrUpdateAcademicRankPieChart() {
  if (!academicRankPieChartCanvasElement.value) return;

  if (!academicRankPieChartInstance) {
    const createdDataset = {
      label: "Số lượng",
      data: academicRankDistribution.value.values,
      backgroundColor: [
        "rgba(245, 158, 11, 0.22)",
        "rgba(20, 184, 166, 0.20)",
        "rgba(148, 163, 184, 0.20)",
      ],
      borderColor: [
        "rgba(245, 158, 11, 0.55)",
        "rgba(20, 184, 166, 0.55)",
        "rgba(148, 163, 184, 0.55)",
      ],
      borderWidth: 1,
    };

    academicRankPieChartDatasetReference = createdDataset;

    academicRankPieChartInstance = new Chart(
      academicRankPieChartCanvasElement.value,
      {
        type: "pie",
        data: {
          labels: academicRankDistribution.value.labels,
          datasets: [createdDataset],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: false,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { enabled: true },
          },
        },
      }
    );

    return;
  }

  academicRankPieChartInstance.data.labels =
    academicRankDistribution.value.labels;
  if (academicRankPieChartDatasetReference) {
    academicRankPieChartDatasetReference.data =
      academicRankDistribution.value.values;
  }
  academicRankPieChartInstance.update("none");
}

function createOrUpdateGenderBarChart() {
  if (!genderBarChartCanvasElement.value) return;

  if (!genderBarChartInstance) {
    const createdDataset = {
      label: "Số lượng giảng viên",
      data: genderDistribution.value.values,
      backgroundColor: [
        "rgba(15, 23, 42, 0.18)",
        "rgba(79, 70, 229, 0.18)",
        "rgba(100, 116, 139, 0.18)",
      ],
      borderColor: [
        "rgba(15, 23, 42, 0.45)",
        "rgba(79, 70, 229, 0.45)",
        "rgba(100, 116, 139, 0.45)",
      ],
      borderWidth: 1,
    };

    genderBarChartDatasetReference = createdDataset;

    genderBarChartInstance = new Chart(genderBarChartCanvasElement.value, {
      type: "bar",
      data: {
        labels: genderDistribution.value.labels,
        datasets: [createdDataset],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          legend: { position: "bottom" },
          tooltip: { mode: "index", intersect: false },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: "#334155" } },
          y: { beginAtZero: true, ticks: { color: "#334155" } },
        },
      },
    });

    return;
  }

  genderBarChartInstance.data.labels = genderDistribution.value.labels;
  if (genderBarChartDatasetReference) {
    genderBarChartDatasetReference.data = genderDistribution.value.values;
  }
  genderBarChartInstance.update("none");
}

function createOrUpdateAllCharts() {
  createOrUpdateFacultyBarChart();
  createOrUpdateDegreeDonutChart();
  createOrUpdateAcademicRankPieChart();
  createOrUpdateGenderBarChart();
}

onMounted(() => {
  createOrUpdateAllCharts();
});

watch(chartUpdateSignature, () => {
  createOrUpdateAllCharts();
});

onBeforeUnmount(() => {
  facultyBarChartInstance?.destroy();
  degreeDonutChartInstance?.destroy();
  academicRankPieChartInstance?.destroy();
  genderBarChartInstance?.destroy();

  facultyBarChartInstance = null;
  degreeDonutChartInstance = null;
  academicRankPieChartInstance = null;
  genderBarChartInstance = null;

  facultyBarChartDatasetReference = null;
  degreeDonutChartDatasetReference = null;
  academicRankPieChartDatasetReference = null;
  genderBarChartDatasetReference = null;
});
</script>
