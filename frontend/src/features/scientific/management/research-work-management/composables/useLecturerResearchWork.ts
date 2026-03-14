import { computed, onMounted, reactive, ref } from "vue";
import type {
  AcademicYearOption,
  ApprovedDetail,
  ApprovedSummary,
  FacultyOption,
  FilterState,
  OverviewItem,
  Pagination,
  PaginatedResult,
} from "../lecturerResearchWork.contracts";
import type { LecturerResearchWorkClient, PaginationRequest } from "../api/lecturerResearchWork.client";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

export interface UseOptions {
  client: LecturerResearchWorkClient;
}

const defaultPagination: Pagination = {
  page: 1,
  perPage: 8,
  total: 0,
  lastPage: 1,
};

export function useLecturerResearchWorkManagement(options: UseOptions) {
  const { runPageLoad } = usePageLoadFeedback();
  const filter = reactive<FilterState>({
    facultyId: null,
    academicYearId: null,
    lecturerName: "",
    statusMode: "all",
  });

  const facultyOptions = ref<FacultyOption[]>([]);
  const academicYearOptions = ref<AcademicYearOption[]>([]);

  const overviewItems = ref<OverviewItem[]>([]);
  const overviewPagination = reactive<Pagination>({ ...defaultPagination });
  const isOverviewLoading = ref(false);
  const overviewError = ref<string | null>(null);

  const selectedLecturerId = ref<number | null>(null);
  const selectedWorkId = ref<number | null>(null);

  const isLecturerDrawerOpen = ref(false);
  const isDetailDrawerOpen = ref(false);

  const approvedItems = ref<ApprovedSummary[]>([]);
  const approvedPagination = reactive<Pagination>({ ...defaultPagination });
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

  function updatePagination(target: Pagination, next: Pagination) {
    target.page = next.page;
    target.perPage = next.perPage;
    target.total = next.total;
    target.lastPage = next.lastPage;
  }

  function buildPaginationRequest(source: Pagination): PaginationRequest {
    return { page: source.page, perPage: source.perPage };
  }

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

  async function loadOverviewInternal() {
    isOverviewLoading.value = true;
    overviewError.value = null;

    try {
      const result: PaginatedResult<OverviewItem> =
        await options.client.loadOverview(
          filter,
          buildPaginationRequest(overviewPagination)
        );
      overviewItems.value = result.items;
      updatePagination(overviewPagination, result.pagination);
    } catch (error: any) {
      overviewError.value =
        error?.message || "Không tải được tổng quan.";
      overviewItems.value = [];
      updatePagination(overviewPagination, { ...defaultPagination });
    } finally {
      isOverviewLoading.value = false;
    }
  }

  async function loadOverview(options?: { withFeedback?: boolean }) {
    if (options?.withFeedback === false) {
      await loadOverviewInternal();
      return;
    }

    await runPageLoad(loadOverviewInternal, {
      loading: {
        title: "Đang tải quản lý công trình",
        message: "Hệ thống đang cập nhật danh sách công trình nghiên cứu khoa học...",
      },
    });
  }

  function updateFilter(partial: Partial<FilterState>) {
    Object.assign(filter, partial);
    overviewPagination.page = 1;
    const nextKeys = Object.keys(partial);
    const lecturerKeywordOnly =
      nextKeys.length === 1 && nextKeys[0] === "lecturerName";
    void loadOverview({ withFeedback: !lecturerKeywordOnly });
  }

  function resetFilter() {
    filter.facultyId = null;
    filter.academicYearId =
      academicYearOptions.value.find((y) => y.isActive)?.id ?? null;
    filter.lecturerName = "";
    filter.statusMode = "all";
    overviewPagination.page = 1;
    void loadOverview();
  }

  async function loadApprovedWorks() {
    if (!selectedLecturerId.value) return;

    approvedError.value = null;
    isApprovedLoading.value = true;

    try {
      const result: PaginatedResult<ApprovedSummary> =
        await options.client.loadApprovedWorks(
          selectedLecturerId.value,
          filter,
          buildPaginationRequest(approvedPagination)
        );
      approvedItems.value = result.items;
      updatePagination(approvedPagination, result.pagination);
    } catch (error: any) {
      approvedError.value =
        error?.message ||
        "Không tải được danh sách đã duyệt.";
      approvedItems.value = [];
      updatePagination(approvedPagination, { ...defaultPagination });
    } finally {
      isApprovedLoading.value = false;
    }
  }

  async function openLecturerDrawer(lecturerId: number) {
    selectedLecturerId.value = lecturerId;
    selectedWorkId.value = null;

    isLecturerDrawerOpen.value = true;
    isDetailDrawerOpen.value = false;

    approvedItems.value = [];
    approvedPagination.page = 1;

    await loadApprovedWorks();
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
      detailError.value =
        error?.message ||
        "Không tải được chi tiết đã duyệt.";
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

  function updateOverviewPage(page: number) {
    overviewPagination.page = page;
    void loadOverview();
  }

  function updateOverviewPerPage(perPage: number) {
    overviewPagination.perPage = perPage;
    overviewPagination.page = 1;
    void loadOverview();
  }

  function updateApprovedPage(page: number) {
    approvedPagination.page = page;
    loadApprovedWorks();
  }

  function updateApprovedPerPage(perPage: number) {
    approvedPagination.perPage = perPage;
    approvedPagination.page = 1;
    loadApprovedWorks();
  }

  onMounted(async () => {
    await runPageLoad(
      async () => {
        await loadOptions();
        await loadOverviewInternal();
      },
      {
        loading: {
          title: "Đang khởi tạo quản lý công trình",
          message: "Hệ thống đang chuẩn bị dữ liệu công trình nghiên cứu khoa học...",
        },
      },
    );
  });

  return {
    filter,
    facultyOptions,
    academicYearOptions,

    overviewItems,
    overviewPagination,
    isOverviewLoading,
    overviewError,

    selectedLecturerId,
    selectedWorkId,
    selectedLecturerOverview,
    isLecturerDrawerOpen,
    isDetailDrawerOpen,

    approvedItems,
    approvedPagination,
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
    updateOverviewPage,
    updateOverviewPerPage,
    updateApprovedPage,
    updateApprovedPerPage,
  };
}
