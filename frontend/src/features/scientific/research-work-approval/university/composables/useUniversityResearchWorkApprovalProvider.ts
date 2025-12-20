import { computed, onMounted, ref } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalUiConfiguration,
  ResearchWorkRejectionReasonType,
} from "../../shared/models/researchWorkApprovalModels";
import { useResearchWorkApprovalMockApi } from "../../shared/composables/useResearchWorkApprovalMockApi";
import { useResearchWorkApprovalFiltering } from "../../shared/composables/useResearchWorkApprovalFiltering";
import { useResearchWorkApprovalDisplayMapping } from "../../shared/composables/useResearchWorkApprovalDisplayMapping";

export function useUniversityResearchWorkApprovalProvider() {
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

  const api = useResearchWorkApprovalMockApi();
  const displayMapping = useResearchWorkApprovalDisplayMapping({
    approvalScopeIdentifier: "UNIVERSITY_SCOPE",
  });

  const filtering = useResearchWorkApprovalFiltering({
    approvalScopeIdentifier: "UNIVERSITY_SCOPE",
    researchWorkApprovalListRef: api.researchWorkApprovalList,
    isDepartmentFilterVisible: true,
    forcedDepartmentIdentifier: null,
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
    () => `Chờ duyệt cấp trường: ${totalPendingResearchWorkCount.value}`
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
      approvalScopeIdentifier: "UNIVERSITY_SCOPE",
      totalResearchWorkCount: 80,
      seedValue: 20251219,
    });

    filtering.applyResearchWorkFilterConditions();
  }

  function approve(payload: {
    researchWorkIdentifier: number;
    officialResearchHours: number | null;
  }): void {
    // University approve requires official hours
    api.approveResearchWorkAtUniversityLevel({
      researchWorkIdentifier: payload.researchWorkIdentifier,
      officialResearchHours: payload.officialResearchHours ?? 0,
    });
    filtering.applyResearchWorkFilterConditions();
  }

  function reject(payload: {
    researchWorkIdentifier: number;
    rejectionReasonType: ResearchWorkRejectionReasonType;
    rejectionReasonDetail: string | null;
  }): void {
    api.rejectResearchWorkAtUniversityLevel({
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
        "Cấp trường cần bộ lọc khoa/đơn vị để đối soát thống nhất toàn trường."
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
      () => "Giờ chính thức là dữ liệu dùng để tính giờ NCKH toàn trường."
    ),

    rejectionReasonOptionList: computed(() =>
      displayMapping.getRejectionReasonOptionList()
    ),

    approve,
    reject,
  };
}
