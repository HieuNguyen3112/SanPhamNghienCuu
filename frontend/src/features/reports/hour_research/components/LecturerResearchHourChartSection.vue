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
          Tỷ lệ giảng viên đạt / chưa đạt chuẩn
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
import type { LecturerResearchHourRecord } from "../lecturerResearchHourModels";

const componentProperties = defineProps<{
  lecturerResearchHourRecords: LecturerResearchHourRecord[];
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

function calculateMeetingStandardCounts(
  records: LecturerResearchHourRecord[]
): {
  meetingResearchHourStandardLecturerCount: number;
  notMeetingResearchHourStandardLecturerCount: number;
} {
  let meetingResearchHourStandardLecturerCount = 0;
  let notMeetingResearchHourStandardLecturerCount = 0;

  for (const lecturerResearchHourRecord of records) {
    const isMeetingResearchHourStandard =
      lecturerResearchHourRecord.totalResearchHourCount >=
      lecturerResearchHourRecord.researchHourStandardCount;

    if (isMeetingResearchHourStandard) {
      meetingResearchHourStandardLecturerCount += 1;
    } else {
      notMeetingResearchHourStandardLecturerCount += 1;
    }
  }

  return {
    meetingResearchHourStandardLecturerCount,
    notMeetingResearchHourStandardLecturerCount,
  };
}

const researchHoursByFacultyChartConfiguration = computed(() => {
  const researchHourTotalByFacultyIdentifier = new Map<
    string,
    { facultyDisplayName: string; totalResearchHourCount: number }
  >();

  for (const lecturerResearchHourRecord of componentProperties.lecturerResearchHourRecords) {
    const existingFacultyAggregate = researchHourTotalByFacultyIdentifier.get(
      lecturerResearchHourRecord.facultyIdentifier
    );
    if (!existingFacultyAggregate) {
      researchHourTotalByFacultyIdentifier.set(
        lecturerResearchHourRecord.facultyIdentifier,
        {
          facultyDisplayName: lecturerResearchHourRecord.facultyDisplayName,
          totalResearchHourCount:
            lecturerResearchHourRecord.totalResearchHourCount,
        }
      );
      continue;
    }

    existingFacultyAggregate.totalResearchHourCount +=
      lecturerResearchHourRecord.totalResearchHourCount;
  }

  const facultyAggregates = Array.from(
    researchHourTotalByFacultyIdentifier.values()
  ).sort(
    (firstAggregate, secondAggregate) =>
      secondAggregate.totalResearchHourCount -
      firstAggregate.totalResearchHourCount
  );

  return {
    labels: facultyAggregates.map(
      (facultyAggregate) => facultyAggregate.facultyDisplayName
    ),
    datasets: [
      {
        label: "Tổng giờ NCKH",
        backgroundColor: "rgba(30, 41, 59, 0.85)",
        borderColor: "rgba(30, 41, 59, 1)",
        borderWidth: 1,
        data: facultyAggregates.map(
          (facultyAggregate) => facultyAggregate.totalResearchHourCount
        ),
      },
    ],
  };
});

const researchHoursByAcademicYearChartConfiguration = computed(() => {
  const researchHourTotalByAcademicYear = new Map<string, number>();

  for (const lecturerResearchHourRecord of componentProperties.lecturerResearchHourRecords) {
    const existingAcademicYearTotal =
      researchHourTotalByAcademicYear.get(
        lecturerResearchHourRecord.academicYear
      ) ?? 0;
    researchHourTotalByAcademicYear.set(
      lecturerResearchHourRecord.academicYear,
      existingAcademicYearTotal +
        lecturerResearchHourRecord.totalResearchHourCount
    );
  }

  const academicYearAggregates = Array.from(
    researchHourTotalByAcademicYear.entries()
  ).sort((firstEntry, secondEntry) =>
    firstEntry[0].localeCompare(secondEntry[0])
  );

  return {
    labels: academicYearAggregates.map(([academicYear]) => academicYear),
    datasets: [
      {
        label: "Tổng giờ NCKH",
        backgroundColor: "rgba(51, 65, 85, 0.85)",
        borderColor: "rgba(51, 65, 85, 1)",
        borderWidth: 1,
        data: academicYearAggregates.map(
          ([, totalResearchHourCount]) => totalResearchHourCount
        ),
      },
    ],
  };
});

const lecturerStandardDistributionChartConfiguration = computed(() => {
  const {
    meetingResearchHourStandardLecturerCount,
    notMeetingResearchHourStandardLecturerCount,
  } = calculateMeetingStandardCounts(
    componentProperties.lecturerResearchHourRecords
  );

  return {
    labels: ["Đạt chuẩn", "Chưa đạt chuẩn"],
    datasets: [
      {
        label: "Số giảng viên",
        backgroundColor: [
          "rgba(16, 185, 129, 0.25)",
          "rgba(244, 63, 94, 0.22)",
        ],
        borderColor: ["rgba(16, 185, 129, 0.9)", "rgba(244, 63, 94, 0.9)"],
        borderWidth: 1,
        data: [
          meetingResearchHourStandardLecturerCount,
          notMeetingResearchHourStandardLecturerCount,
        ],
      },
    ],
  };
});

const researchHourBarChartDisplayOptions: ChartOptions<"bar"> = {
  responsive: true,
  maintainAspectRatio: false,

  // Chart.js yêu cầu đúng literal `false` để tắt animation, tránh bị TS widen thành boolean
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
  () => componentProperties.lecturerResearchHourRecords,
  () => {
    // Khi điều kiện lọc thay đổi, việc vẽ lại giúp biểu đồ luôn phản ánh đúng “trạng thái hiện tại”
    // mà không cần tối ưu hóa phức tạp cho bản demo.
    renderAllCharts();
  },
  { deep: true }
);

onBeforeUnmount(() => {
  destroyAllChartInstances();
});
</script>
