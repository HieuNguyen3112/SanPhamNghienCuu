import { computed, ref, watch } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalScopeIdentifier,
  ResearchWorkApprovalStatus,
  ResearchWorkType,
} from "../models/researchWorkApprovalModels";
import { useResearchWorkApprovalDisplayMapping } from "./useResearchWorkApprovalDisplayMapping";
import { toBackendDateTimeTimestamp } from "../../../shared/utils/backendDateTime";

export function useResearchWorkApprovalFiltering(parameters: {
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;
  researchWorkApprovalListRef: { value: ResearchWorkApprovalEntry[] };

  isDepartmentFilterVisible: boolean;
  forcedDepartmentIdentifier: string | null; // faculty scope dùng để khóa phạm vi.
}) {
  const academicYearOptions = ref<string[]>([]);

  const departmentOptions = ref<
    { departmentIdentifier: string; departmentDisplayName: string }[]
  >([]);

  const researchWorkTypeOptions = ref<ResearchWorkType[]>([
    "JOURNAL_ARTICLE",
    "RESEARCH_PROJECT",
    "CONFERENCE_PROCEEDING",
    "BOOK_CHAPTER",
    "STUDENT_SUPERVISION",
  ]);

  const displayMapping = useResearchWorkApprovalDisplayMapping({
    approvalScopeIdentifier: parameters.approvalScopeIdentifier,
  });

  const approvalStatusOptionList = computed(() =>
    displayMapping.getApprovalStatusOptionList(),
  );

  const selectedAcademicYear = ref<string>("ALL_ACADEMIC_YEARS");
  const defaultAcademicYear = ref<string>("ALL_ACADEMIC_YEARS");
  const selectedDepartmentIdentifier = ref<string>(
    parameters.forcedDepartmentIdentifier ?? "ALL_DEPARTMENTS",
  );
  const selectedResearchWorkType = ref<
    ResearchWorkType | "ALL_RESEARCH_WORK_TYPES"
  >("ALL_RESEARCH_WORK_TYPES");
  const selectedApprovalStatus = ref<
    ResearchWorkApprovalStatus | "ALL_APPROVAL_STATUSES"
  >("ALL_APPROVAL_STATUSES");
  const selectedLecturerOrResearchWorkKeyword = ref<string>("");

  const filteredResearchWorkApprovalList = ref<ResearchWorkApprovalEntry[]>([]);

  function applyResearchWorkFilterConditions(): void {
    const normalizedKeywordValue = selectedLecturerOrResearchWorkKeyword.value
      .trim()
      .toLocaleLowerCase();

    const pendingStatusValue = displayMapping.getPendingApprovalStatusValue();

    const nextFilteredList =
      parameters.researchWorkApprovalListRef.value.filter((entry) => {
        const effectiveDepartmentIdentifier =
          parameters.isDepartmentFilterVisible
            ? selectedDepartmentIdentifier.value
            : parameters.forcedDepartmentIdentifier;

        const isDepartmentMatching =
          !effectiveDepartmentIdentifier ||
          effectiveDepartmentIdentifier === "ALL_DEPARTMENTS" ||
          entry.facultyIdentifier === effectiveDepartmentIdentifier;

        const isAcademicYearMatching =
          selectedAcademicYear.value === "ALL_ACADEMIC_YEARS" ||
          entry.academicYear === selectedAcademicYear.value;

        const isResearchWorkTypeMatching =
          selectedResearchWorkType.value === "ALL_RESEARCH_WORK_TYPES" ||
          entry.researchWorkType === selectedResearchWorkType.value;

        const isApprovalStatusMatching =
          selectedApprovalStatus.value === "ALL_APPROVAL_STATUSES" ||
          entry.approvalStatus === selectedApprovalStatus.value;

        const isKeywordMatching =
          normalizedKeywordValue.length === 0 ||
          entry.researchWorkTitle
            .toLocaleLowerCase()
            .includes(normalizedKeywordValue) ||
          entry.submittingLecturerDisplayName
            .toLocaleLowerCase()
            .includes(normalizedKeywordValue) ||
          entry.facultyDisplayName
            .toLocaleLowerCase()
            .includes(normalizedKeywordValue) ||
          entry.researchWorkAuthorList.some((author) =>
            author.authorDisplayName
              .toLocaleLowerCase()
              .includes(normalizedKeywordValue),
          );

        return (
          isDepartmentMatching &&
          isAcademicYearMatching &&
          isResearchWorkTypeMatching &&
          isApprovalStatusMatching &&
          isKeywordMatching
        );
      });

    // WHY: ưu tiên pending lên đầu để giảm thời gian rà soát.
    filteredResearchWorkApprovalList.value = nextFilteredList.sort(
      (firstEntry, secondEntry) => {
        const firstPriority =
          firstEntry.approvalStatus === pendingStatusValue ? 0 : 1;
        const secondPriority =
          secondEntry.approvalStatus === pendingStatusValue ? 0 : 1;
        if (firstPriority !== secondPriority)
          return firstPriority - secondPriority;

        return (
          toBackendDateTimeTimestamp(secondEntry.submittedAtDateTimeString) -
          toBackendDateTimeTimestamp(firstEntry.submittedAtDateTimeString)
        );
      },
    );
  }

  function resetResearchWorkFilterConditions(): void {
    selectedAcademicYear.value = defaultAcademicYear.value;
    selectedResearchWorkType.value = "ALL_RESEARCH_WORK_TYPES";
    selectedApprovalStatus.value = "ALL_APPROVAL_STATUSES";
    selectedLecturerOrResearchWorkKeyword.value = "";

    if (parameters.isDepartmentFilterVisible) {
      selectedDepartmentIdentifier.value = "ALL_DEPARTMENTS";
    } else if (parameters.forcedDepartmentIdentifier) {
      selectedDepartmentIdentifier.value =
        parameters.forcedDepartmentIdentifier;
    }

    applyResearchWorkFilterConditions();
  }

  function setDefaultAcademicYear(value: string): void {
    defaultAcademicYear.value = value;
  }

  watch(
    [
      selectedAcademicYear,
      selectedDepartmentIdentifier,
      selectedResearchWorkType,
      selectedApprovalStatus,
      selectedLecturerOrResearchWorkKeyword,
    ],
    () => applyResearchWorkFilterConditions(),
  );

  return {
    academicYearOptions,
    departmentOptions,
    researchWorkTypeOptions,
    approvalStatusOptionList,

    selectedAcademicYear,
    selectedDepartmentIdentifier,
    selectedResearchWorkType,
    selectedApprovalStatus,
    selectedLecturerOrResearchWorkKeyword,

    filteredResearchWorkApprovalList,

    applyResearchWorkFilterConditions,
    resetResearchWorkFilterConditions,
    setDefaultAcademicYear,
  };
}
