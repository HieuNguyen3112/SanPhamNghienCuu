import { computed, ref } from "vue";
import type {
  HoursApprovalBatchDetail,
  HoursApprovalBatchSummary,
  HoursDistributionItem,
  HoursOverview,
  HoursOverviewMode,
} from "../contracts/HoursOverviewContracts";
import {
  lecturerHoursOverviewService,
  type AcademicYearOption,
  type HoursOverviewFilter,
} from "../services/HoursOverviewService";

export function useLecturerHoursOverview() {
  const overview = ref<HoursOverview | null>(null);
  const distribution = ref<HoursDistributionItem[]>([]);
  const batches = ref<HoursApprovalBatchSummary[]>([]);

  const academicYears = ref<AcademicYearOption[]>([]);
  const selectedMode = ref<HoursOverviewMode>("year");
  const selectedAcademicYearId = ref<number | null>(null);

  const selectedBatchId = ref<number | null>(null);
  const selectedBatchDetail = ref<HoursApprovalBatchDetail | null>(null);

  const loadingOverview = ref(false);
  const loadingDistribution = ref(false);
  const loadingBatches = ref(false);
  const loadingBatchDetail = ref(false);
  const loadingAcademicYears = ref(false);

  const errorOverview = ref<string | null>(null);
  const errorDistribution = ref<string | null>(null);
  const errorBatches = ref<string | null>(null);
  const errorBatchDetail = ref<string | null>(null);
  const errorAcademicYears = ref<string | null>(null);

  const detailOpen = computed(() => selectedBatchId.value !== null);

  const scopeOptions = computed(() => {
    const yearOptions = academicYears.value.map((item) => ({
      value: String(item.id),
      label: item.code,
    }));

    return [{ value: "overall", label: "Tổng thể" }, ...yearOptions];
  });

  const selectedScopeValue = computed(() => {
    if (selectedMode.value === "overall") {
      return "overall";
    }
    return selectedAcademicYearId.value ? String(selectedAcademicYearId.value) : "overall";
  });

  function buildFilter(): HoursOverviewFilter {
    if (selectedMode.value === "overall") {
      return { mode: "overall" };
    }

    if (selectedAcademicYearId.value) {
      return { mode: "year", academic_year_id: selectedAcademicYearId.value };
    }

    return { mode: "year" };
  }

  async function loadAcademicYears(): Promise<void> {
    loadingAcademicYears.value = true;
    errorAcademicYears.value = null;

    try {
      academicYears.value = await lecturerHoursOverviewService.getAcademicYears();

      if (selectedMode.value === "overall") {
        return;
      }

      if (
        selectedAcademicYearId.value &&
        academicYears.value.some((item) => item.id === selectedAcademicYearId.value)
      ) {
        return;
      }

      const activeYear =
        academicYears.value.find((item) => item.isActive) ??
        academicYears.value.find((item) => item.isCurrent) ??
        academicYears.value[0] ??
        null;

      selectedAcademicYearId.value = activeYear ? activeYear.id : null;
    } catch (e) {
      errorAcademicYears.value =
        e instanceof Error ? e.message : "Không tải được danh sách năm học.";
      academicYears.value = [];
    } finally {
      loadingAcademicYears.value = false;
    }
  }

  async function loadOverview(): Promise<void> {
    loadingOverview.value = true;
    errorOverview.value = null;
    try {
      overview.value = await lecturerHoursOverviewService.getOverview(buildFilter());
    } catch (e) {
      errorOverview.value =
        e instanceof Error ? e.message : "Không tải được tổng quan.";
      overview.value = null;
    } finally {
      loadingOverview.value = false;
    }
  }

  async function loadDistribution(): Promise<void> {
    loadingDistribution.value = true;
    errorDistribution.value = null;
    try {
      distribution.value = await lecturerHoursOverviewService.getDistribution(
        buildFilter()
      );
    } catch (e) {
      errorDistribution.value =
        e instanceof Error ? e.message : "Không tải được phân bố.";
      distribution.value = [];
    } finally {
      loadingDistribution.value = false;
    }
  }

  async function loadBatches(): Promise<void> {
    loadingBatches.value = true;
    errorBatches.value = null;
    try {
      batches.value = await lecturerHoursOverviewService.getBatches(buildFilter());
    } catch (e) {
      errorBatches.value =
        e instanceof Error ? e.message : "Không tải được lịch sử.";
      batches.value = [];
    } finally {
      loadingBatches.value = false;
    }
  }

  async function reloadAll(): Promise<void> {
    await Promise.all([loadOverview(), loadDistribution(), loadBatches()]);
  }

  async function initialize(): Promise<void> {
    await loadAcademicYears();
    await reloadAll();
  }

  async function changeScope(value: string): Promise<void> {
    if (value === "overall") {
      selectedMode.value = "overall";
      selectedAcademicYearId.value = null;
      await reloadAll();
      return;
    }

    const nextYearId = Number(value);
    if (!Number.isFinite(nextYearId) || nextYearId <= 0) {
      return;
    }

    selectedMode.value = "year";
    selectedAcademicYearId.value = nextYearId;
    await reloadAll();
  }

  async function openBatchDetail(batchId: number): Promise<void> {
    selectedBatchId.value = batchId;
    selectedBatchDetail.value = null;

    loadingBatchDetail.value = true;
    errorBatchDetail.value = null;
    try {
      selectedBatchDetail.value =
        await lecturerHoursOverviewService.getBatchDetail(batchId);
    } catch (e) {
      errorBatchDetail.value =
        e instanceof Error ? e.message : "Không tải được chi tiết đợt.";
      selectedBatchDetail.value = null;
    } finally {
      loadingBatchDetail.value = false;
    }
  }

  function closeBatchDetail(): void {
    selectedBatchId.value = null;
    selectedBatchDetail.value = null;
    errorBatchDetail.value = null;
    loadingBatchDetail.value = false;
  }

  return {
    overview,
    distribution,
    batches,
    academicYears,
    scopeOptions,
    selectedScopeValue,

    detailOpen,
    selectedBatchDetail,

    loadingOverview,
    errorOverview,
    loadingDistribution,
    errorDistribution,
    loadingBatches,
    errorBatches,
    loadingBatchDetail,
    errorBatchDetail,
    loadingAcademicYears,
    errorAcademicYears,

    initialize,
    loadOverview,
    loadDistribution,
    loadBatches,
    reloadAll,
    changeScope,
    openBatchDetail,
    closeBatchDetail,
  };
}