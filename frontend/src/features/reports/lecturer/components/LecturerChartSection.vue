<template>
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-900">
          Giảng viên theo khoa
        </h3>
        <p class="text-xs text-slate-500">Số lượng giảng viên theo từng khoa</p>
      </div>
      <div class="h-[300px]">
        <canvas ref="departmentBarChartCanvasElement" />
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-900">Trình độ học vấn</h3>
        <p class="text-xs text-slate-500">Phân bổ trình độ đào tạo</p>
      </div>
      <div class="h-[300px]">
        <canvas ref="educationLevelDonutChartCanvasElement" />
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3">
        <h3 class="text-sm font-semibold text-slate-900">Học hàm</h3>
        <p class="text-xs text-slate-500">Tỷ lệ học hàm trong đội ngũ</p>
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
import type { LecturerRecord } from "../lecturerStatisticsTypes";

const componentProperties = defineProps<{
  filteredLecturerRecords: LecturerRecord[];
}>();

const departmentBarChartCanvasElement = ref<HTMLCanvasElement | null>(null);
const educationLevelDonutChartCanvasElement = ref<HTMLCanvasElement | null>(
  null
);
const academicRankPieChartCanvasElement = ref<HTMLCanvasElement | null>(null);
const genderBarChartCanvasElement = ref<HTMLCanvasElement | null>(null);

let departmentBarChartInstance: Chart<"bar"> | null = null;
let educationLevelDonutChartInstance: Chart<"doughnut"> | null = null;
let academicRankPieChartInstance: Chart<"pie"> | null = null;
let genderBarChartInstance: Chart<"bar"> | null = null;

/**
 * Giữ reference dataset để cập nhật an toàn, tránh index [0] gây TS2532
 * (TypeScript không đảm bảo mảng datasets luôn có phần tử ở vị trí 0).
 */
let departmentBarChartDatasetReference: { data: number[] } | null = null;

let educationLevelDonutChartDatasetReference: { data: number[] } | null = null;

let academicRankPieChartDatasetReference: { data: number[] } | null = null;

let genderBarChartDatasetReference: { data: number[] } | null = null;

const departmentDistribution = computed(() => {
  const departmentCountMap = new Map<string, number>();

  for (const lecturerRecord of componentProperties.filteredLecturerRecords) {
    departmentCountMap.set(
      lecturerRecord.departmentName,
      (departmentCountMap.get(lecturerRecord.departmentName) ?? 0) + 1
    );
  }

  const sortedEntries = Array.from(departmentCountMap.entries()).sort(
    (firstEntry, secondEntry) => firstEntry[0].localeCompare(secondEntry[0])
  );

  return {
    departmentLabels: sortedEntries.map((entry) => entry[0]),
    departmentCounts: sortedEntries.map((entry) => entry[1]),
  };
});

const educationLevelDistribution = computed(() => {
  const numberOfDoctorLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.educationLevelCategory === "Doctor"
    ).length;

  const numberOfMasterLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.educationLevelCategory === "Master"
    ).length;

  const numberOfBachelorLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.educationLevelCategory === "Bachelor"
    ).length;

  return {
    educationLevelLabels: ["Tiến sĩ", "Thạc sĩ", "Đại học"],
    educationLevelCounts: [
      numberOfDoctorLecturers,
      numberOfMasterLecturers,
      numberOfBachelorLecturers,
    ],
  };
});

const academicRankDistribution = computed(() => {
  const numberOfProfessorLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.academicRankCategory === "Professor"
    ).length;

  const numberOfAssociateProfessorLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) =>
        lecturerRecord.academicRankCategory === "AssociateProfessor"
    ).length;

  const numberOfNoneAcademicRankLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.academicRankCategory === "None"
    ).length;

  return {
    academicRankLabels: ["Giáo sư", "Phó Giáo sư", "Không"],
    academicRankCounts: [
      numberOfProfessorLecturers,
      numberOfAssociateProfessorLecturers,
      numberOfNoneAcademicRankLecturers,
    ],
  };
});

const genderDistribution = computed(() => {
  const numberOfMaleLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.genderCategory === "Male"
    ).length;

  const numberOfFemaleLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.genderCategory === "Female"
    ).length;

  const numberOfOtherGenderLecturers =
    componentProperties.filteredLecturerRecords.filter(
      (lecturerRecord) => lecturerRecord.genderCategory === "Other"
    ).length;

  return {
    genderLabels: ["Nam", "Nữ", "Khác"],
    genderCounts: [
      numberOfMaleLecturers,
      numberOfFemaleLecturers,
      numberOfOtherGenderLecturers,
    ],
  };
});

const chartUpdateSignature = computed(() => {
  // Chữ ký nhẹ để kích hoạt update chart khi dữ liệu thay đổi, tránh watch deep trên danh sách lớn
  return [
    departmentDistribution.value.departmentLabels.join("|"),
    departmentDistribution.value.departmentCounts.join("|"),
    educationLevelDistribution.value.educationLevelCounts.join("|"),
    academicRankDistribution.value.academicRankCounts.join("|"),
    genderDistribution.value.genderCounts.join("|"),
  ].join("::");
});

function createOrUpdateDepartmentBarChart() {
  if (!departmentBarChartCanvasElement.value) return;

  if (!departmentBarChartInstance) {
    const createdDataset = {
      label: "Số lượng giảng viên",
      data: departmentDistribution.value.departmentCounts,
      backgroundColor: "rgba(15, 23, 42, 0.20)",
      borderColor: "rgba(15, 23, 42, 0.50)",
      borderWidth: 1,
    };

    departmentBarChartDatasetReference = createdDataset;

    departmentBarChartInstance = new Chart(
      departmentBarChartCanvasElement.value,
      {
        type: "bar",
        data: {
          labels: departmentDistribution.value.departmentLabels,
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

  departmentBarChartInstance.data.labels =
    departmentDistribution.value.departmentLabels;
  if (departmentBarChartDatasetReference) {
    departmentBarChartDatasetReference.data =
      departmentDistribution.value.departmentCounts;
  }
  departmentBarChartInstance.update("none");
}

function createOrUpdateEducationLevelDonutChart() {
  if (!educationLevelDonutChartCanvasElement.value) return;

  if (!educationLevelDonutChartInstance) {
    const createdDataset = {
      label: "Số lượng",
      data: educationLevelDistribution.value.educationLevelCounts,
      backgroundColor: [
        "rgba(79, 70, 229, 0.20)",
        "rgba(2, 132, 199, 0.20)",
        "rgba(100, 116, 139, 0.18)",
      ],
      borderColor: [
        "rgba(79, 70, 229, 0.55)",
        "rgba(2, 132, 199, 0.55)",
        "rgba(100, 116, 139, 0.45)",
      ],
      borderWidth: 1,
    };

    educationLevelDonutChartDatasetReference = createdDataset;

    educationLevelDonutChartInstance = new Chart(
      educationLevelDonutChartCanvasElement.value,
      {
        type: "doughnut",
        data: {
          labels: educationLevelDistribution.value.educationLevelLabels,
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

  educationLevelDonutChartInstance.data.labels =
    educationLevelDistribution.value.educationLevelLabels;
  if (educationLevelDonutChartDatasetReference) {
    educationLevelDonutChartDatasetReference.data =
      educationLevelDistribution.value.educationLevelCounts;
  }
  educationLevelDonutChartInstance.update("none");
}

function createOrUpdateAcademicRankPieChart() {
  if (!academicRankPieChartCanvasElement.value) return;

  if (!academicRankPieChartInstance) {
    const createdDataset = {
      label: "Số lượng",
      data: academicRankDistribution.value.academicRankCounts,
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
          labels: academicRankDistribution.value.academicRankLabels,
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
    academicRankDistribution.value.academicRankLabels;
  if (academicRankPieChartDatasetReference) {
    academicRankPieChartDatasetReference.data =
      academicRankDistribution.value.academicRankCounts;
  }
  academicRankPieChartInstance.update("none");
}

function createOrUpdateGenderBarChart() {
  if (!genderBarChartCanvasElement.value) return;

  if (!genderBarChartInstance) {
    const createdDataset = {
      label: "Số lượng giảng viên",
      data: genderDistribution.value.genderCounts,
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
        labels: genderDistribution.value.genderLabels,
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

  genderBarChartInstance.data.labels = genderDistribution.value.genderLabels;
  if (genderBarChartDatasetReference) {
    genderBarChartDatasetReference.data = genderDistribution.value.genderCounts;
  }
  genderBarChartInstance.update("none");
}

function createOrUpdateAllCharts() {
  createOrUpdateDepartmentBarChart();
  createOrUpdateEducationLevelDonutChart();
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
  departmentBarChartInstance?.destroy();
  educationLevelDonutChartInstance?.destroy();
  academicRankPieChartInstance?.destroy();
  genderBarChartInstance?.destroy();

  departmentBarChartInstance = null;
  educationLevelDonutChartInstance = null;
  academicRankPieChartInstance = null;
  genderBarChartInstance = null;

  departmentBarChartDatasetReference = null;
  educationLevelDonutChartDatasetReference = null;
  academicRankPieChartDatasetReference = null;
  genderBarChartDatasetReference = null;
});
</script>
