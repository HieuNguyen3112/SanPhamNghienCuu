import { computed, ref } from "vue";
import type {
  HoursAlertActionSuggestion,
  HoursAlertItem,
  HoursAlertsFilter,
  HoursAlertsSummary,
} from "../contracts/hoursWarning.contract";
import {
  hoursAlertActionSuggestionFromDto,
  hoursAlertItemFromDto,
  hoursAlertsSummaryFromDto,
} from "../contracts/hoursWarning.contract";
import {
  deleteHoursWarning,
  fetchHoursWarnings,
  markHoursWarningSeen,
} from "../services/hoursWarningService";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

const defaultCounts = { all: 0, danger: 0, warning: 0, done: 0 };

export function useHoursWarning() {
  const { runPageLoad } = usePageLoadFeedback();

  const summaryStatus = ref<HoursAlertsSummary | null>(null);
  const alerts = ref<HoursAlertItem[]>([]);
  const actionSuggestions = ref<HoursAlertActionSuggestion[]>([]);
  const counts = ref({ ...defaultCounts });

  const filterStatus = ref<HoursAlertsFilter>("all");

  const loadingSummary = ref(false);
  const errorSummary = ref<string | null>(null);

  const loadingAlerts = ref(false);
  const errorAlerts = ref<string | null>(null);

  const loadingSuggestions = ref(false);
  const errorSuggestions = ref<string | null>(null);

  const pagination = ref({ page: 1, perPage: 12, total: 0, lastPage: 1 });

  const filteredAlerts = computed(() => alerts.value);

  async function loadAlertsInternal() {
    loadingSummary.value = true;
    loadingAlerts.value = true;
    loadingSuggestions.value = true;
    errorSummary.value = null;
    errorAlerts.value = null;
    errorSuggestions.value = null;

    try {
      const dto = await fetchHoursWarnings({
        tab: filterStatus.value,
        page: pagination.value.page,
        per_page: pagination.value.perPage,
      });

      summaryStatus.value = hoursAlertsSummaryFromDto(dto.summary);
      alerts.value = dto.items.map(hoursAlertItemFromDto);
      counts.value = { ...defaultCounts, ...dto.tab_counts };
      actionSuggestions.value = dto.suggestions.map(
        hoursAlertActionSuggestionFromDto,
      );
      pagination.value = {
        page: dto.pagination.page,
        perPage: dto.pagination.per_page,
        total: dto.pagination.total,
        lastPage: dto.pagination.last_page,
      };
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      errorSummary.value = message;
      errorAlerts.value = message;
      errorSuggestions.value = message;
    } finally {
      loadingSummary.value = false;
      loadingAlerts.value = false;
      loadingSuggestions.value = false;
    }
  }

  async function loadAlerts(options?: { withFeedback?: boolean }) {
    if (options?.withFeedback === false) {
      await loadAlertsInternal();
      return;
    }

    await runPageLoad(loadAlertsInternal, {
      loading: {
        title: "Đang tải cảnh báo giờ NCKH",
        message: "Hệ thống đang cập nhật các cảnh báo và gợi ý xử lý...",
      },
    });
  }

  async function bootstrap() {
    await runPageLoad(loadAlertsInternal, {
      loading: {
        title: "Đang khởi tạo cảnh báo giờ NCKH",
        message: "Hệ thống đang chuẩn bị dữ liệu cảnh báo cá nhân...",
      },
    });
  }

  function changeFilter(next: HoursAlertsFilter) {
    filterStatus.value = next;
    pagination.value.page = 1;
    void loadAlerts();
  }

  async function markAsSeen(alertId: string) {
    try {
      await runPageLoad(
        async () => {
          await markHoursWarningSeen(alertId);
          await loadAlertsInternal();
        },
        {
          loading: {
            title: "Đang cập nhật cảnh báo",
            message: "Hệ thống đang ghi nhận trạng thái đã xem...",
          },
          rethrow: true,
        },
      );
    } catch (error) {
      errorAlerts.value = error instanceof Error ? error.message : String(error);
    }
  }

  async function deleteAlert(alertId: string) {
    try {
      await runPageLoad(
        async () => {
          await deleteHoursWarning(alertId);
          await loadAlertsInternal();
        },
        {
          loading: {
            title: "Đang xóa cảnh báo",
            message: "Hệ thống đang cập nhật danh sách cảnh báo...",
          },
          rethrow: true,
        },
      );
    } catch (error) {
      errorAlerts.value = error instanceof Error ? error.message : String(error);
    }
  }

  return {
    summaryStatus,
    alerts,
    actionSuggestions,

    filterStatus,
    filteredAlerts,
    counts,

    loadingSummary,
    errorSummary,
    loadingAlerts,
    errorAlerts,
    loadingSuggestions,
    errorSuggestions,

    bootstrap,
    loadAlerts,
    changeFilter,
    markAsSeen,
    deleteAlert,
  };
}
