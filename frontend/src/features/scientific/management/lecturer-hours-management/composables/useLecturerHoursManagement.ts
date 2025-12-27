import { computed, reactive, ref } from "vue";
import type {
  LecturerHoursOverview,
  LecturerHoursDetailRow,
} from "../lecturerHours.contract";
import {
  lecturerHoursService,
  type LecturerHoursFilterModel,
  type KpiStatusFilter,
} from "../services/lecturerHoursService";

export interface UseLecturerHoursManagementOptions {
  initialYearId: number;
  initialFacultyId: number | null; // faculty page: fixed id, university: null
}

function clampPercent(value: number) {
  if (Number.isNaN(value) || !Number.isFinite(value)) return 0;
  return Math.max(0, Math.min(100, value));
}

export function useLecturerHoursManagement(
  options: UseLecturerHoursManagementOptions
) {
  const filter = reactive<LecturerHoursFilterModel>({
    yearId: options.initialYearId,
    facultyId: options.initialFacultyId,
    kpiStatus: "all",
    keyword: "",
  });

  const overview = ref<LecturerHoursOverview[]>([]);
  const selectedLecturerId = ref<number | null>(null);
  const drawerOpen = ref(false);
  const detailRows = ref<LecturerHoursDetailRow[]>([]);

  const loadingOverview = ref(false);
  const loadingDetail = ref(false);
  const errorOverview = ref<string | null>(null);
  const errorDetail = ref<string | null>(null);

  const totalLecturers = computed(() => overview.value.length);

  const hitCount = computed(() => {
    return overview.value.filter(
      (row) => row.hoursTotal - row.requiredHours >= 0
    ).length;
  });

  const missCount = computed(() => totalLecturers.value - hitCount.value);

  const hitRate = computed(() => {
    if (totalLecturers.value === 0) return 0;
    return clampPercent((hitCount.value / totalLecturers.value) * 100);
  });

  const selectedLecturerOverview = computed(() => {
    if (selectedLecturerId.value === null) return null;
    return (
      overview.value.find(
        (row) => row.lecturerId === selectedLecturerId.value
      ) ?? null
    );
  });

  async function loadOverview() {
    loadingOverview.value = true;
    errorOverview.value = null;

    try {
      overview.value = await lecturerHoursService.loadOverview(filter);
    } catch (e) {
      errorOverview.value = e instanceof Error ? e.message : String(e);
      overview.value = [];
    } finally {
      loadingOverview.value = false;
    }
  }

  async function loadDetail(lecturerId: number) {
    loadingDetail.value = true;
    errorDetail.value = null;

    try {
      detailRows.value = await lecturerHoursService.loadDetail(
        lecturerId,
        filter.yearId
      );
    } catch (e) {
      errorDetail.value = e instanceof Error ? e.message : String(e);
      detailRows.value = [];
    } finally {
      loadingDetail.value = false;
    }
  }

  function applyFilter(nextFilter: Partial<LecturerHoursFilterModel>) {
    Object.assign(filter, nextFilter);
    loadOverview();
  }

  function resetFilter() {
    filter.yearId = options.initialYearId;
    filter.facultyId = options.initialFacultyId;
    filter.kpiStatus = "all" as KpiStatusFilter;
    filter.keyword = "";
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

  return {
    filter,
    overview,

    selectedLecturerId,
    selectedLecturerOverview,

    drawerOpen,
    detailRows,

    totalLecturers,
    hitCount,
    missCount,
    hitRate,

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
  };
}
