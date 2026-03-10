import { computed, reactive, ref } from "vue";
import type {
  LecturerHoursOverview,
  LecturerHoursDetailRow,
  AcademicYearOption,
  FacultyOption,
  KpiStatusOption,
} from "../lecturerHours.contract";
import {
  lecturerHoursService,
  type LecturerHoursFilterModel,
  type KpiStatusFilter,
} from "../services/lecturerHoursService";

interface LecturerHoursServiceLike {
  loadOverview: (
    filter: LecturerHoursFilterModel,
    pagination: { page: number; perPage: number },
  ) => Promise<{
    overview: LecturerHoursOverview[];
    totals: {
      totalLecturers: number;
      hitCount: number;
      missCount: number;
      hitRate: number;
    };
    options: {
      academicYears: AcademicYearOption[];
      faculties: FacultyOption[];
      kpiStatuses: KpiStatusOption[];
    };
    meta: {
      pagination: {
        currentPage: number;
        perPage: number;
        total: number;
        lastPage: number;
      } | null;
      filters: {
        yearId: number | null;
        facultyId: number | null;
        kpiStatus: KpiStatusFilter;
        keyword: string;
      };
    };
  }>;
  loadDetail: (
    lecturerId: number,
    yearId: number,
  ) => Promise<LecturerHoursDetailRow[]>;
}

export interface UseLecturerHoursManagementOptions {
  initialYearId?: number | null;
  initialFacultyId: number | null; // faculty page: fixed id, university: null
  service?: LecturerHoursServiceLike;
}

function clampPercent(value: number) {
  if (Number.isNaN(value) || !Number.isFinite(value)) return 0;
  return Math.max(0, Math.min(100, value));
}

export function useLecturerHoursManagement(
  options: UseLecturerHoursManagementOptions,
) {
  const service = options.service ?? lecturerHoursService;
  const filter = reactive<LecturerHoursFilterModel>({
    yearId: options.initialYearId ?? null,
    facultyId: options.initialFacultyId,
    kpiStatus: "all",
    keyword: "",
  });

  const overview = ref<LecturerHoursOverview[]>([]);
  const selectedLecturerId = ref<number | null>(null);
  const drawerOpen = ref(false);
  const detailRows = ref<LecturerHoursDetailRow[]>([]);

  const yearOptions = ref<AcademicYearOption[]>([]);
  const facultyOptions = ref<FacultyOption[]>([]);
  const kpiStatusOptions = ref<KpiStatusOption[]>([]);

  const totalLecturers = ref(0);
  const hitCount = ref(0);
  const missCount = ref(0);
  const hitRate = ref(0);

  const currentPageNumber = ref(1);
  const pageSize = ref(12);
  const totalItemCount = ref(0);
  const lastPageNumber = ref(1);

  const loadingOverview = ref(false);
  const loadingDetail = ref(false);
  const errorOverview = ref<string | null>(null);
  const errorDetail = ref<string | null>(null);

  const defaultYearId = ref<number | null>(options.initialYearId ?? null);

  const selectedLecturerOverview = computed(() => {
    if (selectedLecturerId.value === null) return null;
    return (
      overview.value.find(
        (row) => row.lecturerId === selectedLecturerId.value,
      ) ?? null
    );
  });

  let keywordTimer: number | null = null;

  async function loadOverview() {
    loadingOverview.value = true;
    errorOverview.value = null;

    try {
      const payload = await service.loadOverview(filter, {
        page: currentPageNumber.value,
        perPage: pageSize.value,
      });

      overview.value = payload.overview;
      totalLecturers.value = payload.totals.totalLecturers;
      hitCount.value = payload.totals.hitCount;
      missCount.value = payload.totals.missCount;
      hitRate.value = clampPercent(payload.totals.hitRate);

      yearOptions.value = payload.options.academicYears;
      facultyOptions.value = payload.options.faculties;
      kpiStatusOptions.value = payload.options.kpiStatuses;

      const activeYearId =
        payload.options.academicYears.find((year) => year.isActive)?.id ?? null;
      const preferredYearId =
        activeYearId ?? payload.meta.filters.yearId ?? null;

      if (!defaultYearId.value && preferredYearId) {
        defaultYearId.value = preferredYearId;
      }

      if (!filter.yearId && preferredYearId) {
        filter.yearId = preferredYearId;
      }

      if (payload.meta.pagination) {
        currentPageNumber.value = payload.meta.pagination.currentPage;
        pageSize.value = payload.meta.pagination.perPage;
        totalItemCount.value = payload.meta.pagination.total;
        lastPageNumber.value = payload.meta.pagination.lastPage;
      } else {
        totalItemCount.value = payload.overview.length;
        lastPageNumber.value = 1;
      }
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
      errorOverview.value = "Không tải được dữ liệu. Vui lòng thử lại.";
      overview.value = [];
      totalLecturers.value = 0;
      hitCount.value = 0;
      missCount.value = 0;
      hitRate.value = 0;
      totalItemCount.value = 0;
      lastPageNumber.value = 1;
    } finally {
      loadingOverview.value = false;
    }
  }

  async function loadDetail(lecturerId: number) {
    loadingDetail.value = true;
    errorDetail.value = null;

    try {
      if (!filter.yearId) {
        detailRows.value = [];
        return;
      }
      detailRows.value = await service.loadDetail(lecturerId, filter.yearId);
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
      errorDetail.value = "Không tải được chi tiết. Vui lòng thử lại.";
      detailRows.value = [];
    } finally {
      loadingDetail.value = false;
    }
  }

  function scheduleLoad(delayMs: number) {
    if (keywordTimer) window.clearTimeout(keywordTimer);
    keywordTimer = window.setTimeout(() => {
      keywordTimer = null;
      loadOverview();
    }, delayMs);
  }

  function applyFilter(nextFilter: Partial<LecturerHoursFilterModel>) {
    Object.assign(filter, nextFilter);
    currentPageNumber.value = 1;

    if (Object.prototype.hasOwnProperty.call(nextFilter, "keyword")) {
      scheduleLoad(350);
      return;
    }

    loadOverview();
  }

  function resetFilter() {
    filter.yearId = defaultYearId.value ?? options.initialYearId ?? null;
    filter.facultyId = options.initialFacultyId;
    filter.kpiStatus = "all" as KpiStatusFilter;
    filter.keyword = "";
    currentPageNumber.value = 1;
    loadOverview();
  }

  function openDrawer(lecturerId: number) {
    selectedLecturerId.value = lecturerId;
    drawerOpen.value = true;
    loadDetail(lecturerId);
  }

  function closeDrawer() {
    drawerOpen.value = false;
    selectedLecturerId.value = null;
    detailRows.value = [];
    errorDetail.value = null;
  }

  function updatePage(nextPage: number) {
    const normalized = Math.min(Math.max(1, nextPage), lastPageNumber.value);
    currentPageNumber.value = normalized;
    loadOverview();
  }

  function updatePageSize(nextPageSize: number) {
    if (!Number.isFinite(nextPageSize) || nextPageSize <= 0) return;
    pageSize.value = nextPageSize;
    currentPageNumber.value = 1;
    loadOverview();
  }

  return {
    filter,
    overview,

    yearOptions,
    facultyOptions,
    kpiStatusOptions,

    selectedLecturerId,
    selectedLecturerOverview,

    drawerOpen,
    detailRows,

    totalLecturers,
    hitCount,
    missCount,
    hitRate,

    currentPageNumber,
    pageSize,
    totalItemCount,

    loadingOverview,
    loadingDetail,

    errorOverview,
    errorDetail,

    loadOverview,
    applyFilter,
    resetFilter,
    openDrawer,
    closeDrawer,
    loadDetail,
    updatePage,
    updatePageSize,
  };
}
