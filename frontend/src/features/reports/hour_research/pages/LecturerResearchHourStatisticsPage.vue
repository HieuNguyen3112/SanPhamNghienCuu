<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader />
      </div>

      <div class="mt-4 space-y-4">
        <LecturerResearchHourFilterPanel
          v-if="lecturerResearchHourStatisticsResponse"
          :faculty-options="
            lecturerResearchHourStatisticsResponse.facultyOptions
          "
          :academic-year-options="
            lecturerResearchHourStatisticsResponse.academicYearOptions
          "
          v-model:selectedFacultyIdentifier="selectedFacultyIdentifier"
          v-model:selectedAcademicYear="selectedAcademicYear"
          v-model:selectedResearchHourStatusFilterCondition="
            selectedResearchHourStatusFilterCondition
          "
          @resetLecturerResearchHourFilterConditions="
            resetLecturerResearchHourFilterConditions
          "
        />

        <LecturerResearchHourSummaryCards
          :total-lecturer-count="totalLecturerCount"
          :total-research-hour-count="totalResearchHourCount"
          :average-research-hours-per-lecturer="averageResearchHoursPerLecturer"
          :lecturer-meeting-research-hour-standard-percentage="
            lecturerMeetingResearchHourStandardPercentage
          "
        />

        <LecturerResearchHourChartSection
          :lecturer-research-hour-records="filteredLecturerResearchHourRecords"
        />

        <LecturerResearchHourStatisticsTable
          :lecturer-research-hour-records="filteredLecturerResearchHourRecords"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import PageHeader from "../components/PageHeader.vue";
import LecturerResearchHourFilterPanel from "../components/LecturerResearchHourFilterPanel.vue";
import LecturerResearchHourSummaryCards from "../components/LecturerResearchHourSummaryCards.vue";
import LecturerResearchHourChartSection from "../components/LecturerResearchHourChartSection.vue";
import LecturerResearchHourStatisticsTable from "../components/LecturerResearchHourStatisticsTable.vue";

import type {
  LecturerResearchHourRecord,
  LecturerResearchHourStatisticsResponse,
  ResearchHourStatusFilterCondition,
} from "../lecturerResearchHourModels";

/**
 * REQUIRED VARIABLES
 * - Các biến lọc được đặt ở cấp trang để đảm bảo Filter Panel chỉ lo UI,
 *   còn trang chịu trách nhiệm điều phối dữ liệu và trạng thái.
 */
const lecturerResearchHourStatisticsResponse =
  ref<LecturerResearchHourStatisticsResponse | null>(null);

const selectedFacultyIdentifier = ref<string>("ALL_FACULTIES");
const selectedAcademicYear = ref<string>("ALL_ACADEMIC_YEARS");
const selectedResearchHourStatusFilterCondition =
  ref<ResearchHourStatusFilterCondition>("ALL");

const filteredLecturerResearchHourRecords = ref<LecturerResearchHourRecord[]>(
  []
);

/**
 * REQUIRED FUNCTIONS
 * - Giữ logic “đủ đơn giản để đọc” và dễ mở rộng cho demo / đồ án.
 */
function loadLecturerResearchHourStatistics(): void {
  // Vì là demo UI, mock dữ liệu giúp tập trung vào trải nghiệm và cấu trúc màn hình.
  const mockFacultyOptions = [
    {
      facultyIdentifier: "FACULTY_INFORMATION_TECHNOLOGY",
      facultyDisplayName: "Khoa Công nghệ thông tin",
    },
    {
      facultyIdentifier: "FACULTY_ECONOMICS",
      facultyDisplayName: "Khoa Kinh tế",
    },
    {
      facultyIdentifier: "FACULTY_EDUCATION",
      facultyDisplayName: "Khoa Sư phạm",
    },
    { facultyIdentifier: "FACULTY_LAW", facultyDisplayName: "Khoa Luật" },
  ];

  const mockAcademicYearOptions = ["2022-2023", "2023-2024", "2024-2025"];

  const mockLecturerResearchHourRecords: LecturerResearchHourRecord[] = [
    {
      lecturerIdentifier: "LECTURER_001",
      lecturerDisplayName: "Nguyễn Văn An",
      facultyIdentifier: "FACULTY_INFORMATION_TECHNOLOGY",
      facultyDisplayName: "Khoa Công nghệ thông tin",
      academicYear: "2024-2025",
      totalResearchHourCount: 120,
      researchHourStandardCount: 100,
    },
    {
      lecturerIdentifier: "LECTURER_002",
      lecturerDisplayName: "Trần Thị Bình",
      facultyIdentifier: "FACULTY_INFORMATION_TECHNOLOGY",
      facultyDisplayName: "Khoa Công nghệ thông tin",
      academicYear: "2024-2025",
      totalResearchHourCount: 70,
      researchHourStandardCount: 100,
    },
    {
      lecturerIdentifier: "LECTURER_003",
      lecturerDisplayName: "Lê Minh Châu",
      facultyIdentifier: "FACULTY_ECONOMICS",
      facultyDisplayName: "Khoa Kinh tế",
      academicYear: "2024-2025",
      totalResearchHourCount: 95,
      researchHourStandardCount: 90,
    },
    {
      lecturerIdentifier: "LECTURER_004",
      lecturerDisplayName: "Phạm Quốc Dũng",
      facultyIdentifier: "FACULTY_ECONOMICS",
      facultyDisplayName: "Khoa Kinh tế",
      academicYear: "2023-2024",
      totalResearchHourCount: 40,
      researchHourStandardCount: 90,
    },
    {
      lecturerIdentifier: "LECTURER_005",
      lecturerDisplayName: "Võ Thị Hạnh",
      facultyIdentifier: "FACULTY_EDUCATION",
      facultyDisplayName: "Khoa Sư phạm",
      academicYear: "2023-2024",
      totalResearchHourCount: 110,
      researchHourStandardCount: 100,
    },
    {
      lecturerIdentifier: "LECTURER_006",
      lecturerDisplayName: "Đặng Hoàng Khoa",
      facultyIdentifier: "FACULTY_LAW",
      facultyDisplayName: "Khoa Luật",
      academicYear: "2022-2023",
      totalResearchHourCount: 60,
      researchHourStandardCount: 90,
    },
    {
      lecturerIdentifier: "LECTURER_007",
      lecturerDisplayName: "Nguyễn Thị Lan",
      facultyIdentifier: "FACULTY_LAW",
      facultyDisplayName: "Khoa Luật",
      academicYear: "2024-2025",
      totalResearchHourCount: 92,
      researchHourStandardCount: 90,
    },
  ];

  lecturerResearchHourStatisticsResponse.value = {
    facultyOptions: mockFacultyOptions,
    academicYearOptions: mockAcademicYearOptions,
    lecturerResearchHourRecords: mockLecturerResearchHourRecords,
  };

  applyLecturerResearchHourFilterConditions();
}

function calculateLecturerResearchHourStatus(
  totalResearchHourCount: number,
  researchHourStandardCount: number
): ResearchHourStatusFilterCondition {
  return totalResearchHourCount >= researchHourStandardCount
    ? "MEETING_RESEARCH_HOUR_STANDARD"
    : "NOT_MEETING_RESEARCH_HOUR_STANDARD";
}

function applyLecturerResearchHourFilterConditions(): void {
  if (!lecturerResearchHourStatisticsResponse.value) {
    filteredLecturerResearchHourRecords.value = [];
    return;
  }

  const sourceLecturerResearchHourRecords =
    lecturerResearchHourStatisticsResponse.value.lecturerResearchHourRecords;

  const filteredRecords = sourceLecturerResearchHourRecords.filter(
    (lecturerResearchHourRecord) => {
      const isFacultyMatching =
        selectedFacultyIdentifier.value === "ALL_FACULTIES" ||
        lecturerResearchHourRecord.facultyIdentifier ===
          selectedFacultyIdentifier.value;

      const isAcademicYearMatching =
        selectedAcademicYear.value === "ALL_ACADEMIC_YEARS" ||
        lecturerResearchHourRecord.academicYear === selectedAcademicYear.value;

      const calculatedStatus = calculateLecturerResearchHourStatus(
        lecturerResearchHourRecord.totalResearchHourCount,
        lecturerResearchHourRecord.researchHourStandardCount
      );

      const isStatusMatching =
        selectedResearchHourStatusFilterCondition.value === "ALL" ||
        calculatedStatus === selectedResearchHourStatusFilterCondition.value;

      return isFacultyMatching && isAcademicYearMatching && isStatusMatching;
    }
  );

  // Sắp xếp nhẹ để người dùng đọc nhanh: khoa -> giảng viên
  filteredLecturerResearchHourRecords.value = filteredRecords.sort(
    (firstRecord, secondRecord) => {
      const facultyComparison = firstRecord.facultyDisplayName.localeCompare(
        secondRecord.facultyDisplayName
      );
      if (facultyComparison !== 0) return facultyComparison;
      return firstRecord.lecturerDisplayName.localeCompare(
        secondRecord.lecturerDisplayName
      );
    }
  );
}

function resetLecturerResearchHourFilterConditions(): void {
  selectedFacultyIdentifier.value = "ALL_FACULTIES";
  selectedAcademicYear.value = "ALL_ACADEMIC_YEARS";
  selectedResearchHourStatusFilterCondition.value = "ALL";
  applyLecturerResearchHourFilterConditions();
}

watch(
  [
    selectedFacultyIdentifier,
    selectedAcademicYear,
    selectedResearchHourStatusFilterCondition,
  ],
  () => applyLecturerResearchHourFilterConditions()
);

/**
 * REQUIRED KPI VARIABLES
 * - Tính theo “danh sách đã lọc” để đảm bảo người dùng thấy KPI khớp với màn hình hiện tại.
 */
const totalLecturerCount = computed<number>(
  () => filteredLecturerResearchHourRecords.value.length
);

const totalResearchHourCount = computed<number>(() => {
  return filteredLecturerResearchHourRecords.value.reduce(
    (runningTotal, lecturerResearchHourRecord) =>
      runningTotal + lecturerResearchHourRecord.totalResearchHourCount,
    0
  );
});

const averageResearchHoursPerLecturer = computed<number>(() => {
  if (totalLecturerCount.value === 0) return 0;
  return totalResearchHourCount.value / totalLecturerCount.value;
});

const lecturerMeetingResearchHourStandardPercentage = computed<number>(() => {
  if (totalLecturerCount.value === 0) return 0;

  const meetingStandardLecturerCount =
    filteredLecturerResearchHourRecords.value.filter(
      (lecturerResearchHourRecord) => {
        return (
          lecturerResearchHourRecord.totalResearchHourCount >=
          lecturerResearchHourRecord.researchHourStandardCount
        );
      }
    ).length;

  return (meetingStandardLecturerCount / totalLecturerCount.value) * 100;
});

onMounted(() => {
  loadLecturerResearchHourStatistics();
});
</script>
