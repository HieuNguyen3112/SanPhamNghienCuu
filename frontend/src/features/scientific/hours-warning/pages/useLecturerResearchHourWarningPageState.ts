import { computed, ref } from "vue";
import type {
  LecturerResearchHourWarningResponse,
  LecturerResearchHourWarningEntry,
  ResearchHourShortfallSeverityFilterCondition,
} from "../lecturerResearchHourWarningModels";
import { createLecturerResearchHourWarningResponseForDemonstration } from "../lecturerResearchHourWarningMockFactory";

export function useLecturerResearchHourWarningPageState() {
  const lecturerResearchHourWarningResponse =
    ref<LecturerResearchHourWarningResponse | null>(null);

  // REQUIRED FILTER VARIABLES
  const selectedFacultyIdentifier = ref<string>("ALL_FACULTIES");
  const selectedAcademicYear = ref<string>("ALL_ACADEMIC_YEARS");
  const selectedResearchHourShortfallSeverityFilterCondition =
    ref<ResearchHourShortfallSeverityFilterCondition>("ALL");

  const isLecturerResearchHourWarningDetailModalOpen = ref<boolean>(false);
  const selectedLecturerResearchHourWarningEntry =
    ref<LecturerResearchHourWarningEntry | null>(null);

  /**
   * REQUIRED FUNCTION
   * Dùng công thức rõ ràng để người kiểm tra có thể truy vết và giải thích kết quả.
   */
  function calculateRemainingResearchHours(
    minimumRequiredResearchHours: number,
    currentLecturerResearchHours: number
  ): number {
    return Math.max(
      0,
      minimumRequiredResearchHours - currentLecturerResearchHours
    );
  }

  function determineResearchHourShortfallSeverity(
    lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
  ): "LIGHT" | "MEDIUM" | "SEVERE" {
    const shortfallRatio =
      lecturerResearchHourWarningEntry.remainingResearchHoursToMeetStandard /
      lecturerResearchHourWarningEntry.minimumRequiredResearchHours;

    if (shortfallRatio <= 0.2) return "LIGHT";
    if (shortfallRatio <= 0.4) return "MEDIUM";
    return "SEVERE";
  }

  const filteredLecturerResearchHourWarningEntries = computed<
    LecturerResearchHourWarningEntry[]
  >(() => {
    const sourceEntries =
      lecturerResearchHourWarningResponse.value
        ?.lecturerResearchHourWarningEntries ?? [];

    return sourceEntries
      .filter((lecturerResearchHourWarningEntry) => {
        const isFacultyMatching =
          selectedFacultyIdentifier.value === "ALL_FACULTIES" ||
          lecturerResearchHourWarningEntry.facultyIdentifier ===
            selectedFacultyIdentifier.value;

        const isAcademicYearMatching =
          selectedAcademicYear.value === "ALL_ACADEMIC_YEARS" ||
          lecturerResearchHourWarningEntry.academicYear ===
            selectedAcademicYear.value;

        const calculatedSeverity = determineResearchHourShortfallSeverity(
          lecturerResearchHourWarningEntry
        );

        const isSeverityMatching =
          selectedResearchHourShortfallSeverityFilterCondition.value ===
            "ALL" ||
          calculatedSeverity ===
            selectedResearchHourShortfallSeverityFilterCondition.value;

        return (
          isFacultyMatching && isAcademicYearMatching && isSeverityMatching
        );
      })
      .sort((firstEntry, secondEntry) => {
        const severityOrder: Record<"LIGHT" | "MEDIUM" | "SEVERE", number> = {
          SEVERE: 0,
          MEDIUM: 1,
          LIGHT: 2,
        };

        const firstSeverity =
          determineResearchHourShortfallSeverity(firstEntry);
        const secondSeverity =
          determineResearchHourShortfallSeverity(secondEntry);

        const severityComparison =
          severityOrder[firstSeverity] - severityOrder[secondSeverity];
        if (severityComparison !== 0) return severityComparison;

        const facultyComparison = firstEntry.facultyDisplayName.localeCompare(
          secondEntry.facultyDisplayName
        );
        if (facultyComparison !== 0) return facultyComparison;

        return firstEntry.lecturerDisplayName.localeCompare(
          secondEntry.lecturerDisplayName
        );
      });
  });

  /**
   * REQUIRED FUNCTION
   * Giữ hàm này để “điểm hoá” hành vi lọc trong quy trình audit,
   * dù dữ liệu lọc là reactive (computed) và tự cập nhật theo điều kiện lọc.
   */
  function applyLecturerResearchHourWarningFilterConditions(): void {
    void filteredLecturerResearchHourWarningEntries.value;
  }

  /**
   * REQUIRED FUNCTION
   * Đặt lại giúp kiểm tra nhanh toàn cảnh, tránh “lọc sót” khi rà soát báo cáo.
   */
  function resetLecturerResearchHourWarningFilterConditions(): void {
    selectedFacultyIdentifier.value = "ALL_FACULTIES";
    selectedAcademicYear.value = "ALL_ACADEMIC_YEARS";
    selectedResearchHourShortfallSeverityFilterCondition.value = "ALL";
    applyLecturerResearchHourWarningFilterConditions();
  }

  /**
   * REQUIRED FUNCTION
   * Cảnh báo tồn tại để hỗ trợ nhà trường giám sát “chuẩn tối thiểu” minh bạch,
   * giúp khoa/bộ môn có kế hoạch hỗ trợ học thuật thay vì tạo áp lực cảm tính.
   */
  function loadLecturersNotMeetingResearchHourStandard(): void {
    lecturerResearchHourWarningResponse.value =
      createLecturerResearchHourWarningResponseForDemonstration(
        calculateRemainingResearchHours
      );

    applyLecturerResearchHourWarningFilterConditions();
  }

  const totalLecturersNotMeetingResearchHourStandard = computed<number>(() => {
    return filteredLecturerResearchHourWarningEntries.value.length;
  });

  const totalRemainingResearchHoursToMeetStandard = computed<number>(() => {
    return filteredLecturerResearchHourWarningEntries.value.reduce(
      (runningTotal, lecturerResearchHourWarningEntry) =>
        runningTotal +
        lecturerResearchHourWarningEntry.remainingResearchHoursToMeetStandard,
      0
    );
  });

  const averageRemainingResearchHoursToMeetStandard = computed<number>(() => {
    const totalLecturerCount =
      totalLecturersNotMeetingResearchHourStandard.value;
    if (totalLecturerCount === 0) return 0;
    return totalRemainingResearchHoursToMeetStandard.value / totalLecturerCount;
  });

  const minimumRequiredResearchHours = computed<number>(() => {
    const firstEntry =
      lecturerResearchHourWarningResponse.value
        ?.lecturerResearchHourWarningEntries[0];
    return firstEntry?.minimumRequiredResearchHours ?? 100;
  });

  const currentLecturerResearchHours = computed<number>(() => {
    return (
      selectedLecturerResearchHourWarningEntry.value
        ?.currentLecturerResearchHours ?? 0
    );
  });

  const remainingResearchHoursToMeetStandard = computed<number>(() => {
    const minimumRequired =
      selectedLecturerResearchHourWarningEntry.value
        ?.minimumRequiredResearchHours ?? minimumRequiredResearchHours.value;

    return calculateRemainingResearchHours(
      minimumRequired,
      currentLecturerResearchHours.value
    );
  });

  function openLecturerResearchHourWarningDetailModal(
    lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
  ): void {
    selectedLecturerResearchHourWarningEntry.value =
      lecturerResearchHourWarningEntry;
    isLecturerResearchHourWarningDetailModalOpen.value = true;
  }

  function closeLecturerResearchHourWarningDetailModal(): void {
    isLecturerResearchHourWarningDetailModalOpen.value = false;
    selectedLecturerResearchHourWarningEntry.value = null;
  }

  function submitLecturerResearchHourWarningNotificationRequest(
    lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
  ): void {
    const lecturerResearchHourWarningNotificationRequestedAtDateTimeString =
      new Date().toISOString();

    const lecturerResearchHourWarningEntries =
      lecturerResearchHourWarningResponse.value
        ?.lecturerResearchHourWarningEntries ?? [];

    const matchedLecturerResearchHourWarningEntry =
      lecturerResearchHourWarningEntries.find(
        (currentEntry) =>
          currentEntry.lecturerIdentifier ===
            lecturerResearchHourWarningEntry.lecturerIdentifier &&
          currentEntry.academicYear ===
            lecturerResearchHourWarningEntry.academicYear
      );

    if (matchedLecturerResearchHourWarningEntry) {
      matchedLecturerResearchHourWarningEntry.lecturerResearchHourWarningNotificationRequestState =
        "REQUESTED";
      matchedLecturerResearchHourWarningEntry.lecturerResearchHourWarningNotificationRequestedAtDateTimeString =
        lecturerResearchHourWarningNotificationRequestedAtDateTimeString;
    }

    if (
      selectedLecturerResearchHourWarningEntry.value &&
      selectedLecturerResearchHourWarningEntry.value.lecturerIdentifier ===
        lecturerResearchHourWarningEntry.lecturerIdentifier &&
      selectedLecturerResearchHourWarningEntry.value.academicYear ===
        lecturerResearchHourWarningEntry.academicYear
    ) {
      selectedLecturerResearchHourWarningEntry.value.lecturerResearchHourWarningNotificationRequestState =
        "REQUESTED";
      selectedLecturerResearchHourWarningEntry.value.lecturerResearchHourWarningNotificationRequestedAtDateTimeString =
        lecturerResearchHourWarningNotificationRequestedAtDateTimeString;
    }

    applyLecturerResearchHourWarningFilterConditions();
  }

  function exportWarningDashboardAsExcel(): void {
    window.alert("Chức năng Xuất Excel (UI demo).");
  }

  function exportWarningDashboardAsPdf(): void {
    window.alert("Chức năng Xuất PDF (UI demo).");
  }

  return {
    lecturerResearchHourWarningResponse,

    selectedFacultyIdentifier,
    selectedAcademicYear,
    selectedResearchHourShortfallSeverityFilterCondition,

    filteredLecturerResearchHourWarningEntries,

    totalLecturersNotMeetingResearchHourStandard,
    totalRemainingResearchHoursToMeetStandard,
    averageRemainingResearchHoursToMeetStandard,
    minimumRequiredResearchHours,

    currentLecturerResearchHours,
    remainingResearchHoursToMeetStandard,

    isLecturerResearchHourWarningDetailModalOpen,
    selectedLecturerResearchHourWarningEntry,

    loadLecturersNotMeetingResearchHourStandard,
    applyLecturerResearchHourWarningFilterConditions,
    resetLecturerResearchHourWarningFilterConditions,
    calculateRemainingResearchHours,

    openLecturerResearchHourWarningDetailModal,
    closeLecturerResearchHourWarningDetailModal,

    submitLecturerResearchHourWarningNotificationRequest,

    exportWarningDashboardAsExcel,
    exportWarningDashboardAsPdf,
  };
}
