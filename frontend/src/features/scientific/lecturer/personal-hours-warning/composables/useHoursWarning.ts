import { computed, ref } from "vue";
import type {
  HoursAlertItem,
  HoursAlertsFilter,
  HoursAlertsSummary,
  HoursAlertActionSuggestion,
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

const defaultCounts = { all: 0, danger: 0, warning: 0, done: 0 };

export function useHoursWarning() {
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

  async function loadAlerts() {
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
        hoursAlertActionSuggestionFromDto
      );
      pagination.value = {
        page: dto.pagination.page,
        perPage: dto.pagination.per_page,
        total: dto.pagination.total,
        lastPage: dto.pagination.last_page,
      };
    } catch (e) {
      const message = e instanceof Error ? e.message : String(e);
      errorSummary.value = message;
      errorAlerts.value = message;
      errorSuggestions.value = message;
    } finally {
      loadingSummary.value = false;
      loadingAlerts.value = false;
      loadingSuggestions.value = false;
    }
  }

  function changeFilter(next: HoursAlertsFilter) {
    filterStatus.value = next;
    pagination.value.page = 1;
    loadAlerts();
  }

  async function markAsSeen(alertId: string) {
    try {
      await markHoursWarningSeen(alertId);
      await loadAlerts();
    } catch (e) {
      errorAlerts.value = e instanceof Error ? e.message : String(e);
    }
  }

  async function deleteAlert(alertId: string) {
    try {
      await deleteHoursWarning(alertId);
      await loadAlerts();
    } catch (e) {
      errorAlerts.value = e instanceof Error ? e.message : String(e);
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

    loadAlerts,
    changeFilter,
    markAsSeen,
    deleteAlert,
  };
}
