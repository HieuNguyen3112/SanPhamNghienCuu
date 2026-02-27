import { computed, onMounted, ref, watch } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalUiConfiguration,
  ResearchWorkRejectionReasonType,
  ResearchWorkType,
} from "../../shared/models/researchWorkApprovalModels";
import { useResearchWorkApprovalFiltering } from "../../shared/composables/useResearchWorkApprovalFiltering";
import { useResearchWorkApprovalDisplayMapping } from "../../shared/composables/useResearchWorkApprovalDisplayMapping";
import http from "@/lib/http";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import {
  fetchUniversityApprovalDetail,
  fetchUniversityApprovals,
  finalizeUniversityApproval,
  rejectUniversityApproval,
  type UniversityApprovalDetailResponse,
  type UniversityApprovalListItem,
} from "../../shared/services/universityApproval.service";

export function useUniversityResearchWorkApprovalProvider() {
  const { runWithFeedback } = useActionFeedback();
  const uiConfiguration = ref<ResearchWorkApprovalUiConfiguration>({
    approvalScopeIdentifier: "UNIVERSITY_SCOPE",
    pageTitle: "Duyệt công trình nghiên cứu – Cấp trường",
    pageSubtitle:
      "Xét duyệt cuối cùng và chốt giờ nghiên cứu khoa học cho giảng viên",
    summaryStripText:
      "Các công trình đã được khoa duyệt, chờ xác nhận chính thức",
    isDepartmentFilterVisible: true,
    isOfficialResearchHoursEditable: true,
    tableActionButtonLabel: "Xem & chốt giờ",
    drawerTitle: "Hồ sơ công trình – Cấp trường",
    drawerSubtitle: "Final",
    primaryActionButtonLabel: "Duyệt & chốt giờ NCKH",
    dangerActionButtonLabel: "Từ chối & trả về khoa",
  });

  const displayMapping = useResearchWorkApprovalDisplayMapping({
    approvalScopeIdentifier: "UNIVERSITY_SCOPE",
  });

  const researchWorkApprovalList = ref<ResearchWorkApprovalEntry[]>([]);
  const filtering = useResearchWorkApprovalFiltering({
    approvalScopeIdentifier: "UNIVERSITY_SCOPE",
    researchWorkApprovalListRef: researchWorkApprovalList,
    isDepartmentFilterVisible: true,
    forcedDepartmentIdentifier: null,
  });

  const isDetailDrawerOpen = ref<boolean>(false);
  const selectedResearchWorkApprovalEntry =
    ref<ResearchWorkApprovalEntry | null>(null);
  const pendingCount = ref<number>(0);
  const lookupReady = ref(false);
  const academicYearIdByCode = ref<Record<string, number>>({});
  const facultyIdentifierById = ref<Record<number, string>>({});

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
    () => `Chờ duyệt cấp trường: ${totalPendingResearchWorkCount.value}`
  );

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
    const [yearsResponse, facultyResponse] = await Promise.all([
      http.get("/api/lookups/academic-years"),
      http.get("/api/lookups/faculties"),
    ]);

    const years = (yearsResponse.data?.data ?? []) as {
      id: number;
      code: string;
    }[];
    const faculties = (facultyResponse.data?.data ?? []) as {
      id: number;
      name: string;
    }[];

    academicYearIdByCode.value = {};
    filtering.academicYearOptions.value = years.map((year) => {
      academicYearIdByCode.value[year.code] = year.id;
      return year.code;
    });

    facultyIdentifierById.value = {};
    filtering.departmentOptions.value = [
      {
        departmentIdentifier: "ALL_DEPARTMENTS",
        departmentDisplayName: "Tất cả khoa / đơn vị",
      },
      ...faculties.map((faculty) => {
        const identifier = `FACULTY_${faculty.id}`;
        facultyIdentifierById.value[faculty.id] = identifier;
        return {
          departmentIdentifier: identifier,
          departmentDisplayName: faculty.name,
        };
      }),
    ];

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

  function mapAuthors(
    item: UniversityApprovalListItem,
    submitterId: number
  ) {
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

  function mapListEntry(
    item: UniversityApprovalListItem
  ): ResearchWorkApprovalEntry {
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
    detail: UniversityApprovalDetailResponse
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
      const entry: any = {
        authorIdentifier: member.lecturer_id,
        authorDisplayName: `${member.lecturer_full_name} (${member.lecturer_code})`,
        authorFacultyIdentifier:
          facultyIdentifierById.value[item.lecturer.faculty_id ?? 0] ??
          "ALL_DEPARTMENTS",
        authorFacultyDisplayName: member.faculty_name ?? "",
        isPrimaryAuthor: isPrimary,
        isSubmittingLecturer: member.lecturer_id === item.lecturer.id,
        declaredHours: member.declared_hours,
        recommendedHoursByPolicy: member.recommended_hours,
        officialHours: member.official_hours,
      };
      return entry;
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
        const reviewLevel =
          approval.stage_code === "assistant" ? "Cấp khoa" : "Cấp trường";
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
      recommendedResearchHoursByPolicy: 0,
      officialResearchHours: item.official_hours ?? 0,

      approvalStatus: item.approval_status as any,

      evidenceAttachmentList: detail.evidence_files.map((file) => ({
        evidenceAttachmentIdentifier: file.id,
        evidenceAttachmentDisplayName: file.original_name ?? "",
        evidenceAttachmentFileType: file.file_type_name ?? "",
        evidenceAttachmentPreviewUrl: file.url ?? "#",
      })),
      researchWorkAuthorList: authorList as any,
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
    const selectedDepartment = filtering.selectedDepartmentIdentifier.value;
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

    const facultyId =
      selectedDepartment === "ALL_DEPARTMENTS"
        ? null
        : Number(selectedDepartment.replace("FACULTY_", ""));

    const kindCode = resolveKindCodeFilter(selectedType);

    const response = await fetchUniversityApprovals({
      academic_year_id: academicYearId,
      faculty_id: Number.isFinite(facultyId) ? facultyId : null,
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
      const detail = await fetchUniversityApprovalDetail(activityId);
      selectedResearchWorkApprovalEntry.value = mapDetailEntry(detail);
    } catch (error) {
      console.error(error);
    }
  }

  async function approve(payload: {
    researchWorkIdentifier: number;
    officialResearchHours: number | null;
    memberHours?: { authorIdentifier: number; officialHours: number }[];
  }): Promise<void> {
    const memberHours =
      payload.memberHours?.map((member) => ({
        lecturer_id: member.authorIdentifier,
        official_hours: member.officialHours,
      })) ?? [];

    await runWithFeedback(
      async () => {
        await finalizeUniversityApproval(payload.researchWorkIdentifier, {
          members: memberHours,
        });
        await loadList();
        closeResearchWorkDetailDrawer();
      },
      {
        loading: {
          title: "Đang duyệt & chốt giờ",
          message: "Hệ thống đang cập nhật kết quả duyệt...",
        },
        success: {
          title: "Thành công",
          message: "Đã duyệt công trình và chốt giờ NCKH.",
        },
        error: {
          title: "Có lỗi xảy ra",
          message: "Không thể duyệt công trình. Vui lòng thử lại.",
        },
        rethrow: false,
      }
    );
  }

  async function reject(payload: {
    researchWorkIdentifier: number;
    rejectionReasonType: ResearchWorkRejectionReasonType;
    rejectionReasonDetail: string | null;
  }): Promise<void> {
    await runWithFeedback(
      async () => {
        await rejectUniversityApproval(payload.researchWorkIdentifier, {
          reason_type: payload.rejectionReasonType,
          reason_detail: payload.rejectionReasonDetail,
        });
        await loadList();
        closeResearchWorkDetailDrawer();
      },
      {
        loading: {
          title: "Đang xử lý từ chối",
          message: "Hệ thống đang cập nhật kết quả duyệt...",
        },
        success: {
          title: "Thành công",
          message: "Đã từ chối công trình.",
        },
        error: {
          title: "Có lỗi xảy ra",
          message: "Không thể từ chối công trình. Vui lòng thử lại.",
        },
        rethrow: false,
      }
    );
  }

  onMounted(() => {
    loadLookups().then(loadList).catch(console.error);
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
      loadList().catch(console.error);
    }
  );

  return {
    uiConfiguration,

    pageTitle: computed(() => uiConfiguration.value.pageTitle),
    pageSubtitle: computed(() => uiConfiguration.value.pageSubtitle),
    pendingBadgeText,

    filterPanelHelperText: computed(
      () => "Cấp trường cần bộ lọc khoa/đơn vị để đối soát thống nhất toàn trường."
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
      () => "Cấp trường duyệt cuối cùng và chốt giờ NCKH chính thức."
    ),
    drawerFooterHelperText: computed(
      () =>
        "Giờ chính thức là dữ liệu dùng để tính giờ NCKH toàn trường."
    ),

    rejectionReasonOptionList: computed(() =>
      displayMapping.getRejectionReasonOptionList()
    ),

    approve,
    reject,
  };
}
