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
import { computed, ref } from "vue";
import type { ChartConfiguration, ChartOptions } from "chart.js";
import type { HourResearchReportCharts } from "../hourResearchReportTypes";
import { useChartJs } from "@/features/reports/research/useChartJs";

const componentProperties = defineProps<{
  charts: HourResearchReportCharts;
}>();

const researchHoursByFacultyBarChartCanvasElement =
  ref<HTMLCanvasElement | null>(null);
const lecturerDistributionDoughnutChartCanvasElement =
  ref<HTMLCanvasElement | null>(null);
const researchHoursByAcademicYearBarChartCanvasElement =
  ref<HTMLCanvasElement | null>(null);
const lecturerStandardPieChartCanvasElement = ref<HTMLCanvasElement | null>(null);

const chartAnimationOptions = {
  duration: 650,
  easing: "easeOutQuart" as const,
};

function createBarOptions(): ChartOptions<"bar"> {
  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: chartAnimationOptions,
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
}

function createDoughnutOptions(): ChartOptions<"doughnut"> {
  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: chartAnimationOptions,
    cutout: "62%",
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
}

function createPieOptions(): ChartOptions<"pie"> {
  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: chartAnimationOptions,
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
}

const statusDatasetPalette = {
  backgroundColor: [
    "rgba(16, 185, 129, 0.25)",
    "rgba(244, 63, 94, 0.22)",
  ],
  borderColor: ["rgba(16, 185, 129, 0.9)", "rgba(244, 63, 94, 0.9)"],
};

const researchHoursByFacultyChartConfiguration = computed<
  ChartConfiguration<"bar">
>(() => ({
  type: "bar",
  data: {
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
  },
  options: createBarOptions(),
}));

const lecturerDistributionDoughnutChartConfiguration = computed<
  ChartConfiguration<"doughnut">
>(() => ({
  type: "doughnut",
  data: {
    labels: componentProperties.charts.statusDistribution.labels,
    datasets: [
      {
        label: "Số giảng viên",
        backgroundColor: statusDatasetPalette.backgroundColor,
        borderColor: statusDatasetPalette.borderColor,
        borderWidth: 1,
        data: componentProperties.charts.statusDistribution.values,
      },
    ],
  },
  options: createDoughnutOptions(),
}));

const researchHoursByAcademicYearChartConfiguration = computed<
  ChartConfiguration<"bar">
>(() => ({
  type: "bar",
  data: {
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
  },
  options: createBarOptions(),
}));

const lecturerStandardPieChartConfiguration = computed<ChartConfiguration<"pie">>(
  () => ({
    type: "pie",
    data: {
      labels: componentProperties.charts.statusDistribution.labels,
      datasets: [
        {
          label: "Số giảng viên",
          backgroundColor: statusDatasetPalette.backgroundColor,
          borderColor: statusDatasetPalette.borderColor,
          borderWidth: 1,
          data: componentProperties.charts.statusDistribution.values,
        },
      ],
    },
    options: createPieOptions(),
  }),
);

useChartJs(
  researchHoursByFacultyBarChartCanvasElement,
  () => researchHoursByFacultyChartConfiguration.value,
);
useChartJs(
  lecturerDistributionDoughnutChartCanvasElement,
  () => lecturerDistributionDoughnutChartConfiguration.value,
);
useChartJs(
  researchHoursByAcademicYearBarChartCanvasElement,
  () => researchHoursByAcademicYearChartConfiguration.value,
);
useChartJs(
  lecturerStandardPieChartCanvasElement,
  () => lecturerStandardPieChartConfiguration.value,
);
</script>
