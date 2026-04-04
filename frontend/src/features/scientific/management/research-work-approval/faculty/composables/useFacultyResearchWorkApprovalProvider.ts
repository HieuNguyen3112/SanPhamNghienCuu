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
  type WorkDetailDto,
} from "../../shared/services/facultyApproval.service";
import {
  resolveApiErrorMessage,
  useActionFeedback,
} from "@/shared/composables/useActionFeedback";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

export function useFacultyResearchWorkApprovalProvider() {
  const { runWithFeedback } = useActionFeedback();
  const { runPageLoad } = usePageLoadFeedback();
  const uiConfiguration = ref<ResearchWorkApprovalUiConfiguration>({
    approvalScopeIdentifier: "FACULTY_SCOPE",
    pageTitle: "Duyệt công trình nghiên cứu ở cấp khoa",
    pageSubtitle:
      "Xác nhận công trình do giảng viên trong khoa kê khai trước khi chuyển lên cấp trường.",
    summaryStripText:
      "Các công trình dưới đây cần được khoa xác nhận trước khi kết thúc quy trình.",
    isDepartmentFilterVisible: false,
    isOfficialResearchHoursEditable: false,
    tableActionButtonLabel: "Xem & duyệt",
    drawerTitle: "Hồ sơ công trình ở cấp khoa",
    drawerSubtitle: "Khoa ở trường",
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
  const defaultAcademicYearCode = ref<string>("ALL_ACADEMIC_YEARS");

  const { showErrorModal } = useActionResultModal();

  const totalPendingResearchWorkCount = computed<number>(() => {
    const pendingValue = displayMapping.getPendingApprovalStatusValue();
    if (pendingCount.value > 0 || researchWorkApprovalList.value.length === 0) {
      return pendingCount.value;
    }
    return researchWorkApprovalList.value.filter(
      (entry) => entry.approvalStatus === pendingValue,
    ).length;
  });

  const pendingBadgeText = computed<string>(
    () => `Chờ duyệt: ${totalPendingResearchWorkCount.value} công trình`,
  );

  function showActionError(message: string, details?: unknown): void {
    showErrorModal(message, "Có lỗi xảy ra", details);
  }

  function resolveDecisionErrorMessage(
    error: unknown,
    fallbackMessage: string,
  ): string {
    const errorCode =
      typeof error === "object" &&
      error !== null &&
      "response" in error &&
      typeof error.response === "object" &&
      error.response !== null &&
      "data" in error.response &&
      typeof error.response.data === "object" &&
      error.response.data !== null &&
      "code" in error.response.data
        ? String(error.response.data.code ?? "")
        : "";

    if (errorCode === "APPROVER_IS_ACTIVITY_PARTICIPANT") {
      return "Bạn không thể duyệt hoặc từ chối công trình mà mình tham gia. Vui lòng chuyển hồ sơ cho thành viên hội đồng khoa khác.";
    }

    return resolveApiErrorMessage(error, fallbackMessage);
  }

  function openResearchWorkDetailDrawer(
    entry: ResearchWorkApprovalEntry,
  ): void {
    selectedResearchWorkApprovalEntry.value = entry;
    isDetailDrawerOpen.value = true;
    void loadDetail(entry.researchWorkIdentifier);
  }

  function closeResearchWorkDetailDrawer(): void {
    isDetailDrawerOpen.value = false;
    selectedResearchWorkApprovalEntry.value = null;
  }

  async function loadLookups(): Promise<void> {
    const response = await fetchFacultyApprovalLookups();
    const lookups = response.data;

    academicYearIdByCode.value = {};
    let activeAcademicYearCode: string | null = null;
    filtering.academicYearOptions.value = lookups.academic_years.map((year) => {
      academicYearIdByCode.value[year.code] = year.id;
      if (year.is_active && !activeAcademicYearCode) {
        activeAcademicYearCode = year.code;
      }
      return year.code;
    });

    defaultAcademicYearCode.value =
      activeAcademicYearCode ??
      filtering.academicYearOptions.value[0] ??
      "ALL_ACADEMIC_YEARS";
    filtering.setDefaultAcademicYear(defaultAcademicYearCode.value);
    filtering.selectedAcademicYear.value = defaultAcademicYearCode.value;

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

  function mapWorkDetail(
    workDetail: WorkDetailDto | null | undefined,
  ): ResearchWorkApprovalEntry["researchWorkDetail"] {
    if (!workDetail) return null;

    const sections = (workDetail.sections ?? [])
      .map((section) => ({
        code: section.code,
        title: section.title,
        fields: (section.fields ?? [])
          .filter((field) => field && field.label)
          .map((field) => ({
            key: field.key,
            label: field.label,
            value: field.value ?? null,
          })),
      }))
      .filter((section) => section.fields.length > 0);

    if (sections.length === 0) return null;

    return {
      kindCode: workDetail.kind_code ?? null,
      sections,
    };
  }

  function buildFinalApprovalHistory(
    approvals: FacultyApprovalDetailResponse["approvals"],
  ): ResearchWorkApprovalEntry["approvalHistoryList"] {
    const processedApprovals = approvals.filter(
      (approval) =>
        approval.status !== "pending" &&
        Boolean(approval.decided_at || approval.decided_by_user_name),
    );

    if (processedApprovals.length === 0) {
      return [];
    }

    const latest = processedApprovals.reduce((selected, current) => {
      const selectedDate = selected.decided_at ?? "";
      const currentDate = current.decided_at ?? "";

      if (currentDate > selectedDate) return current;
      if (currentDate < selectedDate) return selected;
      return current.id > selected.id ? current : selected;
    });

    const reviewActionDisplayName =
      latest.status === "approved"
        ? "Duyệt"
        : latest.status === "rejected"
          ? "Từ chối"
          : "Đã xử lý";

    const reviewerName = latest.decided_by_user_name?.trim() || "Không rõ";
    const stageName = latest.stage_name?.trim() || "Hội đồng";

    return [
      {
        historyIdentifier: latest.id,
        reviewLevelDisplayName: "Người duyệt cuối cùng",
        reviewActionDisplayName,
        reviewedAtDateTimeString: latest.decided_at ?? "",
        reviewNote: `${reviewerName} (${stageName})`,
      },
    ];
  }

  function mapAuthors(item: FacultyApprovalListItem, submitterId: number) {
    const ownerFacultyId = item.lecturer.faculty_id ?? null;
    return item.authors.map((author) => {
      const isPrimary =
        author.member_role_code === "principal" ||
        author.member_role_code === "corresponding_author" ||
        author.member_role_code === "chief_editor";
      const isExternal = Boolean(
        author.is_external || author.lecturer_id == null,
      );
      const memberFacultyId = author.member_faculty_id ?? null;
      const isOutsideFaculty =
        (author.is_outside_faculty ?? isExternal) ||
        (ownerFacultyId !== null &&
          memberFacultyId !== null &&
          ownerFacultyId !== memberFacultyId);
      const authorCode = author.lecturer_code?.trim() ?? "";
      const displayName = authorCode
        ? `${author.lecturer_full_name} (${authorCode})`
        : author.lecturer_full_name;
      return {
        authorIdentifier: author.member_id,
        lecturerId: author.lecturer_id ?? null,
        authorDisplayName: displayName,
        authorFacultyIdentifier:
          facultyIdentifierById.value[memberFacultyId ?? 0] ??
          facultyIdentifierById.value[ownerFacultyId ?? 0] ??
          "ALL_DEPARTMENTS",
        authorFacultyDisplayName:
          author.faculty_name ?? author.department_name ?? "",
        authorFacultyId: memberFacultyId,
        ownerFacultyId,
        isOutsideFaculty,
        isExternal,
        isPrimaryAuthor: isPrimary,
        isSubmittingLecturer: !isExternal && author.lecturer_id === submitterId,
      };
    });
  }

  function mapListEntry(
    item: FacultyApprovalListItem,
  ): ResearchWorkApprovalEntry {
    const facultyIdentifier =
      facultyIdentifierById.value[item.lecturer.faculty_id ?? 0] ??
      "ALL_DEPARTMENTS";

    return {
      researchWorkIdentifier: item.activity_id,
      researchWorkTitle: item.title,
      researchWorkKindDisplayName: item.kind_name ?? null,
      researchWorkCategoryDisplayName: item.type_name ?? null,
      journalInfo: null,
      submittingLecturerDisplayName: `${item.lecturer.full_name} (${item.lecturer.code})`,
      facultyIdentifier,
      facultyDisplayName:
        item.lecturer.faculty_name ?? item.lecturer.department_name ?? "",
      academicYear: item.academic_year_code ?? "",
      researchWorkType: mapKindCodeToResearchWorkType(item.kind_code),
      submittedAtDateTimeString: item.submitted_at ?? "",

      facultyReviewedAtDateTimeString: item.approved_at,
      facultyApprovedAtDateTimeString: item.approved_at,
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
      hasApproverConflict: item.has_approver_conflict ?? false,
      approverConflictCode: item.approver_conflict_code ?? null,
      approverConflictMessage: item.approver_conflict_message ?? null,

      evidenceAttachmentList: Array.from(
        { length: item.evidence_count ?? 0 },
        (_, index) => ({
          evidenceAttachmentIdentifier: index + 1,
          evidenceAttachmentDisplayName: "",
          evidenceAttachmentFileType: "",
          evidenceAttachmentPreviewUrl: "",
        }),
      ),
      researchWorkAuthorList: mapAuthors(item, item.lecturer.id),
      coAuthorList: [],
      approvalHistoryList: [],
      researchWorkDetail: null,
    };
  }

  function mapDetailEntry(
    detail: FacultyApprovalDetailResponse,
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
      const isExternal = Boolean(
        member.is_external || member.lecturer_id == null,
      );
      const computedMemberHours =
        member.computed_member_hours ??
        member.declared_hours ??
        member.hours_assigned ??
        null;
      const ownerFacultyId =
        member.owner_faculty_id ?? item.lecturer.faculty_id ?? null;
      const memberFacultyId = member.member_faculty_id ?? null;
      const isOutsideFaculty =
        (member.is_outside_faculty ?? isExternal) ||
        (ownerFacultyId !== null &&
          memberFacultyId !== null &&
          ownerFacultyId !== memberFacultyId);
      const memberCode = member.lecturer_code?.trim() ?? "";
      const displayName = memberCode
        ? `${member.lecturer_full_name} (${memberCode})`
        : member.lecturer_full_name;
      return {
        authorIdentifier: member.member_id,
        lecturerId: member.lecturer_id ?? null,
        authorDisplayName: displayName,
        authorFacultyIdentifier:
          facultyIdentifierById.value[memberFacultyId ?? 0] ??
          facultyIdentifierById.value[ownerFacultyId ?? 0] ??
          "ALL_DEPARTMENTS",
        authorFacultyDisplayName:
          member.faculty_name ?? member.department_name ?? "",
        authorFacultyId: memberFacultyId,
        ownerFacultyId,
        isOutsideFaculty,
        isExternal,
        isPrimaryAuthor: isPrimary,
        isSubmittingLecturer:
          !isExternal && member.lecturer_id === item.lecturer.id,
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

    const approvalHistoryList = buildFinalApprovalHistory(detail.approvals);

    const assistantReviewedAt =
      detail.approvals.find(
        (approval) =>
          approval.stage_code === "assistant" && !!approval.decided_at,
      )?.decided_at ?? null;
    const managerReviewedAt =
      detail.approvals.find(
        (approval) =>
          approval.stage_code === "manager" && !!approval.decided_at,
      )?.decided_at ?? null;

    return {
      researchWorkIdentifier: item.activity_id,
      researchWorkTitle: item.title,
      researchWorkKindDisplayName: item.kind_name ?? null,
      researchWorkCategoryDisplayName: item.type_name ?? null,
      journalInfo: detail.activity.journal
        ? {
            journalName: detail.activity.journal.journal_name ?? null,
            issn: detail.activity.journal.issn ?? null,
            journalScope: detail.activity.journal.journal_scope ?? null,
            journalSourceName:
              detail.activity.journal.journal_source_name ?? null,
            journalPublisher: detail.activity.journal.journal_publisher ?? null,
            journalWebsite: detail.activity.journal.journal_website ?? null,
            workScore: detail.activity.journal.work_score ?? null,
          }
        : null,
      submittingLecturerDisplayName: `${item.lecturer.full_name} (${item.lecturer.code})`,
      facultyIdentifier,
      facultyDisplayName:
        item.lecturer.faculty_name ?? item.lecturer.department_name ?? "",
      academicYear: item.academic_year_code ?? "",
      researchWorkType: mapKindCodeToResearchWorkType(item.kind_code),
      submittedAtDateTimeString: item.submitted_at ?? "",

      facultyReviewedAtDateTimeString:
        assistantReviewedAt ?? item.approved_at ?? null,
      facultyApprovedAtDateTimeString: item.approved_at ?? assistantReviewedAt,
      facultyApprovalNote: null,
      facultyRejectionReasonType: null,
      facultyRejectionReasonDetail: null,

      universityReviewedAtDateTimeString: managerReviewedAt,
      universityRejectionReasonType: null,
      universityRejectionReasonDetail: null,

      lecturerDeclaredResearchHours: item.declared_hours ?? 0,
      recommendedResearchHoursByPolicy: item.computed_total_hours ?? 0,
      officialResearchHours: item.official_hours ?? 0,
      ruleResolved: item.rule_resolved ?? false,
      ruleSummary: item.rule_summary ?? null,
      hoursResolutionNote: item.hours_resolution_note ?? null,

      approvalStatus: item.approval_status as any,
      hasApproverConflict: item.has_approver_conflict ?? false,
      approverConflictCode: item.approver_conflict_code ?? null,
      approverConflictMessage: item.approver_conflict_message ?? null,

      evidenceAttachmentList: detail.evidence_files.map((file) => ({
        evidenceAttachmentIdentifier: file.id,
        evidenceAttachmentDisplayName: file.original_name ?? "",
        evidenceAttachmentFileType: file.file_type_name ?? "",
        evidenceAttachmentPreviewUrl: file.preview_url ?? file.url ?? "#",
        evidenceAttachmentDownloadUrl: file.download_url ?? null,
      })),
      researchWorkAuthorList: authorList,
      coAuthorList: [],
      approvalHistoryList,
      researchWorkDetail: mapWorkDetail(detail.activity.work_detail),
    };
  }

  function resolveKindCodeFilter(
    selected: ResearchWorkType | "ALL_RESEARCH_WORK_TYPES",
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
        : (academicYearIdByCode.value[selectedYear] ?? null);

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

  async function loadListWithFeedback(options?: { withFeedback?: boolean }) {
    if (options?.withFeedback === false) {
      await loadList();
      return;
    }

    await runPageLoad(loadList, {
      loading: {
        title: "Đang tải danh sách xét duyệt",
        message:
          "Hệ thống đang cập nhật hồ sơ công trình nghiên cứu khoa học...",
      },
      onError: (error) => {
        console.error(error);
        showActionError("Không tải được dữ liệu. Vui lòng thử lại.");
      },
    });
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
    await runWithFeedback(
      async () => {
        await approveFacultyApproval(payload.researchWorkIdentifier);
        await loadList();
        closeResearchWorkDetailDrawer();
      },
      {
        loading: {
          title: "Đang duyệt công trình",
          message: "Hệ thống đang cập nhật kết quả xét duyệt...",
        },
        success: {
          title: "Thành công",
          message: "Đã duyệt công trình.",
        },
        error: {
          title: "Duyệt công trình thất bại",
          message: (error) =>
            resolveDecisionErrorMessage(
              error,
              "Không thể duyệt công trình. Vui lòng thử lại.",
            ),
        },
        rethrow: false,
      },
    );
  }

  async function reject(payload: {
    researchWorkIdentifier: number;
    decision?: "reject" | "return_for_revision";
    rejectionReasonType: ResearchWorkRejectionReasonType;
    rejectionReasonDetail: string | null;
  }): Promise<void> {
    const decision = payload.decision ?? "reject";

    await runWithFeedback(
      async () => {
        await rejectFacultyApproval(payload.researchWorkIdentifier, {
          decision,
          reason_type: payload.rejectionReasonType,
          reason_detail: payload.rejectionReasonDetail,
        });
        await loadList();
        closeResearchWorkDetailDrawer();
      },
      {
        loading: {
          title:
            decision === "return_for_revision"
              ? "Đang gửi yêu cầu chỉnh sửa"
              : "Đang xử lý từ chối",
          message: "Hệ thống đang cập nhật kết quả xét duyệt...",
        },
        success: {
          title: "Thành công",
          message:
            decision === "return_for_revision"
              ? "Đã gửi yêu cầu chỉnh sửa cho giảng viên."
              : "Đã từ chối công trình.",
        },
        error: {
          title: "Từ chối công trình thất bại",
          message: (error) =>
            resolveDecisionErrorMessage(
              error,
              "Không thể từ chối công trình. Vui lòng thử lại.",
            ),
        },
        rethrow: false,
      },
    );
  }

  onMounted(() => {
    void runPageLoad(
      async () => {
        await loadLookups();
        await loadList();
      },
      {
        loading: {
          title: "Đang khởi tạo xét duyệt công trình",
          message: "Hệ thống đang chuẩn bị dữ liệu xét duyệt công trình...",
        },
        onError: (error) => {
          console.error(error);
          showActionError("Không tải được dữ liệu. Vui lòng thử lại.");
        },
      },
    );
  });

  function resetFilterConditions(): void {
    filtering.resetResearchWorkFilterConditions();
    filtering.selectedAcademicYear.value = defaultAcademicYearCode.value;
  }

  watch(
    [
      filtering.selectedAcademicYear,
      filtering.selectedDepartmentIdentifier,
      filtering.selectedResearchWorkType,
      filtering.selectedApprovalStatus,
      filtering.selectedLecturerOrResearchWorkKeyword,
    ],
    (newValues, oldValues) => {
      if (!lookupReady.value) return;
      const keywordOnly =
        !!oldValues &&
        newValues[0] === oldValues[0] &&
        newValues[1] === oldValues[1] &&
        newValues[2] === oldValues[2] &&
        newValues[3] === oldValues[3] &&
        newValues[4] !== oldValues[4];
      void loadListWithFeedback({ withFeedback: !keywordOnly });
    },
  );

  return {
    uiConfiguration,

    pageTitle: computed(() => uiConfiguration.value.pageTitle),
    pageSubtitle: computed(() => uiConfiguration.value.pageSubtitle),
    pendingBadgeText,
    filterPanelHelperText: computed(
      () =>
        "Không có bộ lọc khoa vì phạm vi đã cố định theo khoa đăng nhập. Mặc định lọc theo năm học đang hoạt động.",
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

    resetFilterConditions,

    isDetailDrawerOpen,
    selectedResearchWorkApprovalEntry,
    openResearchWorkDetailDrawer,
    closeResearchWorkDetailDrawer,

    drawerHelperText: computed(
      () =>
        "Khoa xác nhận hồ sơ và minh chứng trước khi công trình chuyển lên cấp trường.",
    ),
    drawerFooterHelperText: computed(
      () => "Khoa chỉ xác nhận hồ sơ; không chỉnh sửa giờ NCKH ở màn hình này.",
    ),

    rejectionReasonOptionList: computed(() =>
      displayMapping.getRejectionReasonOptionList(),
    ),

    approve,
    reject,
  };
}
