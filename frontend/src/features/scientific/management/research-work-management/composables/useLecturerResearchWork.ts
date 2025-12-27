import { computed, onMounted, reactive, ref } from "vue";
import type {
  AcademicYearOption,
  ApprovedDetail,
  ApprovedSummary,
  FacultyOption,
  FilterState,
  OverviewItem,
} from "../lecturerResearchWork.contracts";
import type { LecturerResearchWorkClient } from "../api/lecturerResearchWork.client";

export interface UseOptions {
  client: LecturerResearchWorkClient;
}

export function useLecturerResearchWorkManagement(options: UseOptions) {
  const filter = reactive<FilterState>({
    facultyId: null,
    academicYearId: null,
    lecturerName: "",
    statusMode: "all",
  });

  const facultyOptions = ref<FacultyOption[]>([]);
  const academicYearOptions = ref<AcademicYearOption[]>([]);

  const overviewItems = ref<OverviewItem[]>([]);
  const isOverviewLoading = ref(false);
  const overviewError = ref<string | null>(null);

  const selectedLecturerId = ref<number | null>(null);
  const selectedWorkId = ref<number | null>(null);

  const isLecturerDrawerOpen = ref(false);
  const isDetailDrawerOpen = ref(false);

  const approvedItems = ref<ApprovedSummary[]>([]);
  const isApprovedLoading = ref(false);
  const approvedError = ref<string | null>(null);

  const detail = ref<ApprovedDetail | null>(null);
  const isDetailLoading = ref(false);
  const detailError = ref<string | null>(null);

  const selectedLecturerOverview = computed(() => {
    if (!selectedLecturerId.value) return null;
    return (
      overviewItems.value.find(
        (x) => x.lecturerId === selectedLecturerId.value
      ) ?? null
    );
  });

  async function loadOptions() {
    const [faculties, years] = await Promise.all([
      options.client.loadFacultyOptions(),
      options.client.loadAcademicYearOptions(),
    ]);

    facultyOptions.value = faculties;
    academicYearOptions.value = years;

    const activeYear = years.find((y) => y.isActive);
    if (activeYear && !filter.academicYearId) {
      filter.academicYearId = activeYear.id;
    }
  }

  async function loadOverview() {
    isOverviewLoading.value = true;
    overviewError.value = null;
    try {
      overviewItems.value = await options.client.loadOverview(filter);
    } catch (error: any) {
      overviewError.value = error?.message ?? "Không tải được tổng quan.";
      overviewItems.value = [];
    } finally {
      isOverviewLoading.value = false;
    }
  }

  function updateFilter(partial: Partial<FilterState>) {
    Object.assign(filter, partial);
    loadOverview();
  }

  function resetFilter() {
    filter.facultyId = null;
    filter.academicYearId =
      academicYearOptions.value.find((y) => y.isActive)?.id ?? null;
    filter.lecturerName = "";
    filter.statusMode = "all";
    loadOverview();
  }

  async function openLecturerDrawer(lecturerId: number) {
    selectedLecturerId.value = lecturerId;
    selectedWorkId.value = null;

    isLecturerDrawerOpen.value = true;
    isDetailDrawerOpen.value = false;

    approvedItems.value = [];
    approvedError.value = null;
    isApprovedLoading.value = true;

    try {
      approvedItems.value = await options.client.loadApprovedWorks(
        lecturerId,
        filter
      );
    } catch (error: any) {
      approvedError.value =
        error?.message ?? "Không tải được danh sách approved.";
      approvedItems.value = [];
    } finally {
      isApprovedLoading.value = false;
    }
  }

  function closeLecturerDrawer() {
    isLecturerDrawerOpen.value = false;
    selectedLecturerId.value = null;
    approvedItems.value = [];
    approvedError.value = null;
  }

  async function openDetail(workId: number) {
    selectedWorkId.value = workId;

    isDetailDrawerOpen.value = true;
    isLecturerDrawerOpen.value = false;

    detail.value = null;
    detailError.value = null;
    isDetailLoading.value = true;

    try {
      detail.value = await options.client.loadApprovedDetail(workId);
    } catch (error: any) {
      detailError.value = error?.message ?? "Không tải được chi tiết approved.";
      detail.value = null;
    } finally {
      isDetailLoading.value = false;
    }
  }

  function backToList() {
    isDetailDrawerOpen.value = false;
    isLecturerDrawerOpen.value = true;
    selectedWorkId.value = null;
    detail.value = null;
    detailError.value = null;
  }

  onMounted(async () => {
    await loadOptions();
    await loadOverview();
  });

  return {
    filter,
    facultyOptions,
    academicYearOptions,

    overviewItems,
    isOverviewLoading,
    overviewError,

    selectedLecturerId,
    selectedWorkId,
    selectedLecturerOverview,
    isLecturerDrawerOpen,
    isDetailDrawerOpen,

    approvedItems,
    isApprovedLoading,
    approvedError,

    detail,
    isDetailLoading,
    detailError,

    loadOverview,
    updateFilter,
    resetFilter,
    openLecturerDrawer,
    closeLecturerDrawer,
    openDetail,
    backToList,
  };
}
