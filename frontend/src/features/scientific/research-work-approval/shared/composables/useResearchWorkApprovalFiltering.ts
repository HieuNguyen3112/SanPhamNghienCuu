import { computed, ref, watch } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalScopeIdentifier,
  ResearchWorkApprovalStatus,
  ResearchWorkType,
} from "../models/researchWorkApprovalModels";
import { useResearchWorkApprovalDisplayMapping } from "./useResearchWorkApprovalDisplayMapping";

export function useResearchWorkApprovalFiltering(parameters: {
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;
  researchWorkApprovalListRef: { value: ResearchWorkApprovalEntry[] };

  isDepartmentFilterVisible: boolean;
  forcedDepartmentIdentifier: string | null; // faculty scope dùng để “khóa phạm vi”
}) {
  const academicYearOptions = ref<string[]>([
    "2022-2023",
    "2023-2024",
    "2024-2025",
  ]);

  const departmentOptions = ref<
    { departmentIdentifier: string; departmentDisplayName: string }[]
  >([
    {
      departmentIdentifier: "ALL_DEPARTMENTS",
      departmentDisplayName: "Tất cả khoa / đơn vị",
    },
    {
      departmentIdentifier: "FACULTY_INFORMATION_TECHNOLOGY",
      departmentDisplayName: "Khoa Công nghệ thông tin",
    },
    {
      departmentIdentifier: "FACULTY_ECONOMICS",
      departmentDisplayName: "Khoa Kinh tế",
    },
    {
      departmentIdentifier: "FACULTY_EDUCATION",
      departmentDisplayName: "Khoa Sư phạm",
    },
  ]);

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
    displayMapping.getApprovalStatusOptionList()
  );

  const selectedAcademicYear = ref<string>("ALL_ACADEMIC_YEARS");
  const selectedDepartmentIdentifier = ref<string>(
    parameters.forcedDepartmentIdentifier ?? "ALL_DEPARTMENTS"
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
              .includes(normalizedKeywordValue)
          );

        return (
          isDepartmentMatching &&
          isAcademicYearMatching &&
          isResearchWorkTypeMatching &&
          isApprovalStatusMatching &&
          isKeywordMatching
        );
      });

    // WHY: ưu tiên pending lên đầu để giảm thời gian rà soát
    filteredResearchWorkApprovalList.value = nextFilteredList.sort(
      (firstEntry, secondEntry) => {
        const firstPriority =
          firstEntry.approvalStatus === pendingStatusValue ? 0 : 1;
        const secondPriority =
          secondEntry.approvalStatus === pendingStatusValue ? 0 : 1;
        if (firstPriority !== secondPriority)
          return firstPriority - secondPriority;

        return (
          new Date(secondEntry.submittedAtDateTimeString).getTime() -
          new Date(firstEntry.submittedAtDateTimeString).getTime()
        );
      }
    );
  }

  function resetResearchWorkFilterConditions(): void {
    selectedAcademicYear.value = "ALL_ACADEMIC_YEARS";
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

  watch(
    [
      selectedAcademicYear,
      selectedDepartmentIdentifier,
      selectedResearchWorkType,
      selectedApprovalStatus,
      selectedLecturerOrResearchWorkKeyword,
    ],
    () => applyResearchWorkFilterConditions()
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
  };
}
