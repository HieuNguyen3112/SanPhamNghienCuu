import { computed, onMounted, ref, watch } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalUiConfiguration,
  ResearchWorkRejectionReasonType,
  ResearchWorkType,
} from "../../shared/models/researchWorkApprovalModels";
import { useResearchWorkApprovalFiltering } from "../../shared/composables/useResearchWorkApprovalFiltering";
import { useResearchWorkApprovalDisplayMapping } from "../../shared/composables/useResearchWorkApprovalDisplayMapping";
import {
  approveFacultyApproval,
  fetchFacultyApprovalDetail,
  fetchFacultyApprovals,
  fetchFacultyApprovalLookups,
  rejectFacultyApproval,
  type FacultyApprovalDetailResponse,
  type FacultyApprovalListItem,
} from "../../shared/services/facultyApproval.service";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

export function useFacultyResearchWorkApprovalProvider() {
  const uiConfiguration = ref<ResearchWorkApprovalUiConfiguration>({
    approvalScopeIdentifier: "FACULTY_SCOPE",
    pageTitle: "Duyệt công trình nghiên cứu ở cấp khoa",
    pageSubtitle:
      "Xác nhận công trình do giảng viên trong khoa kê khai (cấp khoa là bước duyệt cuối)",
    summaryStripText:
      "Các công trình dưới đây cần được khoa xác nhận trước khi kết thúc quy trình",
    isDepartmentFilterVisible: false,
    isOfficialResearchHoursEditable: false,
    tableActionButtonLabel: "Xem & duyệt",
    drawerTitle: "Hồ sơ công trình ở cấp khoa",
    drawerSubtitle: "Khoa ở Trường",
    primaryActionButtonLabel: "Duyệt công trình",
    dangerActionButtonLabel: "Từ chối",
  });

  const displayMapping = useResearchWorkApprovalDisplayMapping({
    approvalScopeIdentifier: "FACULTY_SCOPE",
  });

  const researchWorkApprovalList = ref<ResearchWorkApprovalEntry[]>([]);
  const filtering = useResearchWorkApprovalFiltering({
    approvalScopeIdentifier: "FACULTY_SCOPE",
    researchWorkApprovalListRef: researchWorkApprovalList,
    isDepartmentFilterVisible: false,
    forcedDepartmentIdentifier: null,
  });

  const isDetailDrawerOpen = ref<boolean>(false);
  const selectedResearchWorkApprovalEntry =
    ref<ResearchWorkApprovalEntry | null>(null);

  const pendingCount = ref<number>(0);
  const lookupReady = ref(false);
  const academicYearIdByCode = ref<Record<string, number>>({});
  const facultyIdentifierById = ref<Record<number, string>>({});

  const {
    showSuccessModal,
    showErrorModal,
  } = useActionResultModal();

  const totalPendingResearchWorkCount = computed<number>(() => {
    const pendingValue = displayMapping.getPendingApprovalStatusValue();
    if (pendingCount.value > 0 || researchWorkApprovalList.value.length === 0) {
      return pendingCount.value;
    }
    return researchWorkApprovalList.value.filter(
      (entry) => entry.approvalStatus === pendingValue
    ).length;
  });

  const pendingBadgeText = computed<string>(
    () => `Chờ duyệt: ${totalPendingResearchWorkCount.value} công trình`
  );

  function showActionSuccess(message: string): void {
    showSuccessModal(message);
  }

  function showActionError(message: string, details?: unknown): void {
    showErrorModal(message, "Có lỗi xảy ra", details);
  }

  function openResearchWorkDetailDrawer(
    entry: ResearchWorkApprovalEntry
  ): void {
    selectedResearchWorkApprovalEntry.value = entry;
    isDetailDrawerOpen.value = true;
    loadDetail(entry.researchWorkIdentifier);
  }

  function closeResearchWorkDetailDrawer(): void {
    isDetailDrawerOpen.value = false;
    selectedResearchWorkApprovalEntry.value = null;
  }

  async function loadLookups(): Promise<void> {
    const response = await fetchFacultyApprovalLookups();
    const lookups = response.data;

    academicYearIdByCode.value = {};
    filtering.academicYearOptions.value = lookups.academic_years.map((year) => {
      academicYearIdByCode.value[year.code] = year.id;
      return year.code;
    });

    // Mặc định để ALL để tránh bỏ sót hồ sơ chờ duyệt khác năm học active.
    filtering.selectedAcademicYear.value = "ALL_ACADEMIC_YEARS";

    facultyIdentifierById.value = {
      [lookups.faculty.id]: `FACULTY_${lookups.faculty.id}`,
    };

    filtering.departmentOptions.value = [
      {
        departmentIdentifier: `FACULTY_${lookups.faculty.id}`,
        departmentDisplayName: lookups.faculty.name,
      },
    ];

    uiConfiguration.value = {
      ...uiConfiguration.value,
      pageTitle: `Duyệt công trình nghiên cứu ở cấp khoa ${lookups.faculty.name}`,
    };

    lookupReady.value = true;
  }

  function mapKindCodeToResearchWorkType(kindCode: string): ResearchWorkType {
    const mapping: Record<string, ResearchWorkType> = {
      paper: "JOURNAL_ARTICLE",
      project: "RESEARCH_PROJECT",
      conference: "CONFERENCE_PROCEEDING",
      book: "BOOK_CHAPTER",
    };
    return mapping[kindCode] ?? "JOURNAL_ARTICLE";
  }

  function mapAuthors(item: FacultyApprovalListItem, submitterId: number) {
    return item.authors.map((author) => {
      const isPrimary =
        author.member_role_code === "principal" ||
        author.member_role_code === "corresponding_author" ||
        author.member_role_code === "chief_editor";
      return {
        authorIdentifier: author.lecturer_id,
        authorDisplayName: `${author.lecturer_full_name} (${author.lecturer_code})`,
        authorFacultyIdentifier:
          facultyIdentifierById.value[item.lecturer.faculty_id ?? 0] ??
          "ALL_DEPARTMENTS",
        authorFacultyDisplayName:
          author.faculty_name ?? author.department_name ?? "",
        isPrimaryAuthor: isPrimary,
        isSubmittingLecturer: author.lecturer_id === submitterId,
      };
    });
  }

  function mapListEntry(item: FacultyApprovalListItem): ResearchWorkApprovalEntry {
    const facultyIdentifier =
      facultyIdentifierById.value[item.lecturer.faculty_id ?? 0] ??
      "ALL_DEPARTMENTS";

    return {
      researchWorkIdentifier: item.activity_id,
      researchWorkTitle: item.title,
      submittingLecturerDisplayName: `${item.lecturer.full_name} (${item.lecturer.code})`,
      facultyIdentifier,
      facultyDisplayName:
        item.lecturer.faculty_name ?? item.lecturer.department_name ?? "",
      academicYear: item.academic_year_code ?? "",
      researchWorkType: mapKindCodeToResearchWorkType(item.kind_code),
      submittedAtDateTimeString: item.submitted_at ?? "",

      facultyReviewedAtDateTimeString: null,
      facultyApprovedAtDateTimeString: null,
      facultyApprovalNote: null,
      facultyRejectionReasonType: null,
      facultyRejectionReasonDetail: null,

      universityReviewedAtDateTimeString: null,
      universityRejectionReasonType: null,
      universityRejectionReasonDetail: null,

      lecturerDeclaredResearchHours: item.declared_hours ?? 0,
      recommendedResearchHoursByPolicy: 0,
      officialResearchHours: item.official_hours ?? 0,

      approvalStatus: item.approval_status as any,

      evidenceAttachmentList: Array.from(
        { length: item.evidence_count ?? 0 },
        (_, index) => ({
          evidenceAttachmentIdentifier: index + 1,
          evidenceAttachmentDisplayName: "",
          evidenceAttachmentFileType: "",
          evidenceAttachmentPreviewUrl: "",
        })
      ),
      researchWorkAuthorList: mapAuthors(item, item.lecturer.id),
      coAuthorList: [],
      approvalHistoryList: [],
    };
  }

  function mapDetailEntry(
    detail: FacultyApprovalDetailResponse
  ): ResearchWorkApprovalEntry {
    const item = detail.activity;
    const facultyIdentifier =
      facultyIdentifierById.value[item.lecturer.faculty_id ?? 0] ??
      "ALL_DEPARTMENTS";

    const authorList = detail.members.map((member) => {
      const isPrimary =
        member.member_role_code === "principal" ||
        member.member_role_code === "corresponding_author" ||
        member.member_role_code === "chief_editor";
      const computedMemberHours =
        member.computed_member_hours ?? member.declared_hours ?? member.hours_assigned ?? null;
      return {
        authorIdentifier: member.lecturer_id,
        authorDisplayName: `${member.lecturer_full_name} (${member.lecturer_code})`,
        authorFacultyIdentifier:
          facultyIdentifierById.value[item.lecturer.faculty_id ?? 0] ??
          "ALL_DEPARTMENTS",
        authorFacultyDisplayName: member.faculty_name ?? "",
        isPrimaryAuthor: isPrimary,
        isSubmittingLecturer: member.lecturer_id === item.lecturer.id,
        authorRoleDisplayName:
          member.member_role_name ??
          (isPrimary ? "Tác giả chính" : "Đồng tác giả"),
        declaredHours: computedMemberHours,
        computedMemberHours,
        recommendedHoursByPolicy:
          member.recommended_hours ?? computedMemberHours,
        officialHours: member.official_hours,
      };
    });

    const approvalHistoryList = detail.approvals
      .filter((approval) => approval.decided_at)
      .map((approval) => {
        const reviewAction =
          approval.status === "approved"
            ? "Duyệt"
            : approval.status === "rejected"
            ? "Từ chối"
            : "Chờ duyệt";
        const reviewLevel = approval.stage_code === "assistant" ? "Cấp khoa" : "Lịch sử cũ";
        return {
          historyIdentifier: approval.id,
          reviewLevelDisplayName: reviewLevel,
          reviewActionDisplayName: reviewAction,
          reviewedAtDateTimeString: approval.decided_at ?? "",
          reviewNote:
            approval.note ??
            (reviewAction === "Duyệt"
              ? `${reviewLevel} đã duyệt.`
              : `${reviewLevel} cập nhật trạng thái.`),
        };
      });

    return {
      researchWorkIdentifier: item.activity_id,
      researchWorkTitle: item.title,
      submittingLecturerDisplayName: `${item.lecturer.full_name} (${item.lecturer.code})`,
      facultyIdentifier,
      facultyDisplayName:
        item.lecturer.faculty_name ?? item.lecturer.department_name ?? "",
      academicYear: item.academic_year_code ?? "",
      researchWorkType: mapKindCodeToResearchWorkType(item.kind_code),
      submittedAtDateTimeString: item.submitted_at ?? "",

      facultyReviewedAtDateTimeString: null,
      facultyApprovedAtDateTimeString: null,
      facultyApprovalNote: null,
      facultyRejectionReasonType: null,
      facultyRejectionReasonDetail: null,

      universityReviewedAtDateTimeString: null,
      universityRejectionReasonType: null,
      universityRejectionReasonDetail: null,

      lecturerDeclaredResearchHours: item.declared_hours ?? 0,
      recommendedResearchHoursByPolicy: item.computed_total_hours ?? 0,
      officialResearchHours: item.official_hours ?? 0,

      approvalStatus: item.approval_status as any,

      evidenceAttachmentList: detail.evidence_files.map((file) => ({
        evidenceAttachmentIdentifier: file.id,
        evidenceAttachmentDisplayName: file.original_name ?? "",
        evidenceAttachmentFileType: file.file_type_name ?? "",
        evidenceAttachmentPreviewUrl: file.url ?? "#",
      })),
      researchWorkAuthorList: authorList,
      coAuthorList: [],
      approvalHistoryList,
    };
  }

  function resolveKindCodeFilter(
    selected: ResearchWorkType | "ALL_RESEARCH_WORK_TYPES"
  ) {
    if (selected === "ALL_RESEARCH_WORK_TYPES") return null;
    if (selected === "JOURNAL_ARTICLE") return "paper";
    if (selected === "RESEARCH_PROJECT") return "project";
    if (selected === "CONFERENCE_PROCEEDING") return "conference";
    if (selected === "BOOK_CHAPTER") return "book";
    return null;
  }

  async function loadList(): Promise<void> {
    if (!lookupReady.value) return;

    const selectedYear = filtering.selectedAcademicYear.value;
    const selectedType = filtering.selectedResearchWorkType.value;
    const selectedStatus = filtering.selectedApprovalStatus.value;
    const keyword = filtering.selectedLecturerOrResearchWorkKeyword.value;

    if (selectedType === "STUDENT_SUPERVISION") {
      researchWorkApprovalList.value = [];
      pendingCount.value = 0;
      filtering.applyResearchWorkFilterConditions();
      return;
    }

    const academicYearId =
      selectedYear === "ALL_ACADEMIC_YEARS"
        ? null
        : academicYearIdByCode.value[selectedYear] ?? null;

    const kindCode = resolveKindCodeFilter(selectedType);

    const response = await fetchFacultyApprovals({
      academic_year_id: academicYearId,
      kind_code: kindCode,
      status:
        selectedStatus === "ALL_APPROVAL_STATUSES" ? null : selectedStatus,
      q: keyword,
    });

    researchWorkApprovalList.value = response.data.map(mapListEntry);
    pendingCount.value = response.meta?.counters?.pending ?? 0;
    filtering.applyResearchWorkFilterConditions();
  }

  async function loadDetail(activityId: number): Promise<void> {
    try {
      const detail = await fetchFacultyApprovalDetail(activityId);
      selectedResearchWorkApprovalEntry.value = mapDetailEntry(detail);
    } catch (error) {
      console.error(error);
      showActionError("Không tải được chi tiết. Vui lòng thử lại.");
    }
  }

  async function approve(payload: {
    researchWorkIdentifier: number;
    officialResearchHours: number | null;
    memberHours?: { authorIdentifier: number; officialHours: number }[];
  }): Promise<void> {
    try {
      await approveFacultyApproval(payload.researchWorkIdentifier);
      showActionSuccess("Đã duyệt công trình.");
      await loadList();
      closeResearchWorkDetailDrawer();
    } catch (error) {
      console.error(error);
      showActionError("Không thể duyệt công trình. Vui lòng thử lại.");
    }
  }

  async function reject(payload: {
    researchWorkIdentifier: number;
    rejectionReasonType: ResearchWorkRejectionReasonType;
    rejectionReasonDetail: string | null;
  }): Promise<void> {
    try {
      await rejectFacultyApproval(payload.researchWorkIdentifier, {
        reason_type: payload.rejectionReasonType,
        reason_detail: payload.rejectionReasonDetail,
      });
      showActionSuccess("Đã từ chối công trình.");
      await loadList();
      closeResearchWorkDetailDrawer();
    } catch (error) {
      console.error(error);
      showActionError("Không thể từ chối công trình. Vui lòng thử lại.");
    }
  }

  onMounted(() => {
    loadLookups().then(loadList).catch((error) => {
      console.error(error);
      showActionError("Không tải được dữ liệu. Vui lòng thử lại.");
    });
  });

  watch(
    [
      filtering.selectedAcademicYear,
      filtering.selectedDepartmentIdentifier,
      filtering.selectedResearchWorkType,
      filtering.selectedApprovalStatus,
      filtering.selectedLecturerOrResearchWorkKeyword,
    ],
    () => {
      if (!lookupReady.value) return;
      loadList().catch((error) => {
        console.error(error);
        showActionError("Không tải được dữ liệu. Vui lòng thử lại.");
      });
    }
  );

  return {
    uiConfiguration,

    pageTitle: computed(() => uiConfiguration.value.pageTitle),
    pageSubtitle: computed(() => uiConfiguration.value.pageSubtitle),
    pendingBadgeText,
    filterPanelHelperText: computed(
      () =>
        "Không có bộ lọc khoa vì phạm vi đã cố định theo khoa đăng nhập. Mặc định hiển thị tất cả năm học."
    ),

    academicYearOptions: filtering.academicYearOptions,
    departmentOptions: filtering.departmentOptions,
    researchWorkTypeOptions: filtering.researchWorkTypeOptions,
    approvalStatusOptionList: filtering.approvalStatusOptionList,

    selectedAcademicYear: filtering.selectedAcademicYear,
    selectedDepartmentIdentifier: filtering.selectedDepartmentIdentifier,
    selectedResearchWorkType: filtering.selectedResearchWorkType,
    selectedApprovalStatus: filtering.selectedApprovalStatus,
    selectedLecturerOrResearchWorkKeyword:
      filtering.selectedLecturerOrResearchWorkKeyword,

    filteredResearchWorkApprovalList:
      filtering.filteredResearchWorkApprovalList,
    totalPendingResearchWorkCount,

    resetFilterConditions: filtering.resetResearchWorkFilterConditions,

    isDetailDrawerOpen,
    selectedResearchWorkApprovalEntry,
    openResearchWorkDetailDrawer,
    closeResearchWorkDetailDrawer,

    drawerHelperText: computed(
      () =>
        "Khoa xác nhận hồ sơ và minh chứng, sau đó quy trình công trình kết thúc."
    ),
    drawerFooterHelperText: computed(
      () =>
        "Khoa chỉ xác nhận hồ sơ; không chỉnh sửa giờ NCKH ở màn hình này."
    ),

    rejectionReasonOptionList: computed(() =>
      displayMapping.getRejectionReasonOptionList()
    ),

    approve,
    reject,
  };
}


