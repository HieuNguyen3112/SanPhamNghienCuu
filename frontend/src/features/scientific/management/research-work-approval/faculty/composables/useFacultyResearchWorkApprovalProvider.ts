import { computed, onMounted, ref } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalUiConfiguration,
  ResearchWorkRejectionReasonType,
} from "../../shared/models/researchWorkApprovalModels";
import { useResearchWorkApprovalMockApi } from "../../shared/composables/useResearchWorkApprovalMockApi";
import { useResearchWorkApprovalFiltering } from "../../shared/composables/useResearchWorkApprovalFiltering";
import { useResearchWorkApprovalDisplayMapping } from "../../shared/composables/useResearchWorkApprovalDisplayMapping";

export function useFacultyResearchWorkApprovalProvider() {
  const currentUserFacultyIdentifier = "FACULTY_INFORMATION_TECHNOLOGY";
  const currentUserFacultyDisplayName = "Khoa Công nghệ thông tin";

  const uiConfiguration = ref<ResearchWorkApprovalUiConfiguration>({
    approvalScopeIdentifier: "FACULTY_SCOPE",
    pageTitle: `Duyệt công trình nghiên cứu – ${currentUserFacultyDisplayName}`,
    pageSubtitle:
      "Xét duyệt công trình do giảng viên trong khoa kê khai trước khi chuyển lên cấp trường",
    summaryStripText:
      "Các công trình dưới đây cần được khoa xác nhận trước khi gửi lên cấp trường",
    isDepartmentFilterVisible: false,
    isOfficialResearchHoursEditable: false,
    tableActionButtonLabel: "Xem & duyệt",
    drawerTitle: "Hồ sơ công trình – Cấp khoa",
    drawerSubtitle: "Khoa → Trường",
    primaryActionButtonLabel: "Duyệt & chuyển lên cấp trường",
    dangerActionButtonLabel: "Từ chối",
  });

  const api = useResearchWorkApprovalMockApi();
  const displayMapping = useResearchWorkApprovalDisplayMapping({
    approvalScopeIdentifier: "FACULTY_SCOPE",
  });

  const filtering = useResearchWorkApprovalFiltering({
    approvalScopeIdentifier: "FACULTY_SCOPE",
    researchWorkApprovalListRef: api.researchWorkApprovalList,
    isDepartmentFilterVisible: false,
    forcedDepartmentIdentifier: currentUserFacultyIdentifier,
  });

  const isDetailDrawerOpen = ref<boolean>(false);
  const selectedResearchWorkApprovalEntry =
    ref<ResearchWorkApprovalEntry | null>(null);

  const totalPendingResearchWorkCount = computed<number>(() => {
    const pendingValue = displayMapping.getPendingApprovalStatusValue();
    return api.researchWorkApprovalList.value.filter(
      (entry) => entry.approvalStatus === pendingValue
    ).length;
  });

  const pendingBadgeText = computed<string>(
    () => `Chờ duyệt: ${totalPendingResearchWorkCount.value} công trình`
  );

  function openResearchWorkDetailDrawer(
    entry: ResearchWorkApprovalEntry
  ): void {
    selectedResearchWorkApprovalEntry.value = entry;
    isDetailDrawerOpen.value = true;
  }

  function closeResearchWorkDetailDrawer(): void {
    isDetailDrawerOpen.value = false;
    selectedResearchWorkApprovalEntry.value = null;
  }

  async function load(): Promise<void> {
    await api.loadResearchWorkApprovalList({
      approvalScopeIdentifier: "FACULTY_SCOPE",
      facultyIdentifier: currentUserFacultyIdentifier,
      totalResearchWorkCount: 60,
      seedValue: 20251218,
    });

    filtering.applyResearchWorkFilterConditions();
  }

  function approve(payload: {
    researchWorkIdentifier: number;
    officialResearchHours?: number | null;
    memberHours?: { authorIdentifier: number; officialHours: number }[];
  }): void {
    api.approveResearchWorkAtFacultyLevel(payload);
    filtering.applyResearchWorkFilterConditions();
  }

  function reject(payload: {
    researchWorkIdentifier: number;
    rejectionReasonType: ResearchWorkRejectionReasonType;
    rejectionReasonDetail: string | null;
  }): void {
    api.rejectResearchWorkAtFacultyLevel({
      researchWorkIdentifier: payload.researchWorkIdentifier,
      rejectionReasonType: payload.rejectionReasonType,
      rejectionReasonDetail: payload.rejectionReasonDetail,
    });
    filtering.applyResearchWorkFilterConditions();
  }

  onMounted(() => {
    load();
  });

  return {
    uiConfiguration,

    pageTitle: computed(() => uiConfiguration.value.pageTitle),
    pageSubtitle: computed(() => uiConfiguration.value.pageSubtitle),
    pendingBadgeText,

    filterPanelHelperText: computed(
      () =>
        "Không có bộ lọc khoa vì phạm vi đã cố định theo khoa đăng nhập, giúp tránh duyệt nhầm ngoài phạm vi quản lý."
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
        "Màn hình cấp khoa xác nhận hồ sơ và minh chứng trước khi chuyển lên cấp trường."
    ),
    drawerFooterHelperText: computed(
      () =>
        "Khoa chỉ xác nhận hồ sơ và minh chứng; không chỉnh sửa giờ NCKH tại màn hình này."
    ),

    rejectionReasonOptionList: computed(() =>
      displayMapping.getRejectionReasonOptionList()
    ),

    approve,
    reject,
  };
}
