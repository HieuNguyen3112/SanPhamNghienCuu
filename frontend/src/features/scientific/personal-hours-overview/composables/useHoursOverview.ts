// File: src/features/lecturer-hours-overview/composables/useLecturerHoursOverview.ts
import { computed, ref } from "vue";
import type {
  HoursApprovalBatchDetail,
  HoursApprovalBatchSummary,
  HoursDistributionItem,
  HoursOverview,
} from "../contracts/HoursOverviewContracts";
import { lecturerHoursOverviewService } from "../services/HoursOverviewService";

export function useLecturerHoursOverview() {
  const overview = ref<HoursOverview | null>(null);
  const distribution = ref<HoursDistributionItem[]>([]);
  const batches = ref<HoursApprovalBatchSummary[]>([]);

  const selectedBatchId = ref<number | null>(null);
  const selectedBatchDetail = ref<HoursApprovalBatchDetail | null>(null);

  const loadingOverview = ref(false);
  const loadingDistribution = ref(false);
  const loadingBatches = ref(false);
  const loadingBatchDetail = ref(false);

  const errorOverview = ref<string | null>(null);
  const errorDistribution = ref<string | null>(null);
  const errorBatches = ref<string | null>(null);
  const errorBatchDetail = ref<string | null>(null);

  const detailOpen = computed(() => selectedBatchId.value !== null);

  async function loadOverview(): Promise<void> {
    loadingOverview.value = true;
    errorOverview.value = null;
    try {
      overview.value = await lecturerHoursOverviewService.getOverview();
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
      distribution.value = await lecturerHoursOverviewService.getDistribution();
    } catch (e) {
      errorDistribution.value =
        e instanceof Error ? e.message : "Không tải được phân bổ.";
      distribution.value = [];
    } finally {
      loadingDistribution.value = false;
    }
  }

  async function loadBatches(): Promise<void> {
    loadingBatches.value = true;
    errorBatches.value = null;
    try {
      batches.value = await lecturerHoursOverviewService.getBatches();
    } catch (e) {
      errorBatches.value =
        e instanceof Error ? e.message : "Không tải được lịch sử.";
      batches.value = [];
    } finally {
      loadingBatches.value = false;
    }
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

    loadOverview,
    loadDistribution,
    loadBatches,
    openBatchDetail,
    closeBatchDetail,
  };
}
