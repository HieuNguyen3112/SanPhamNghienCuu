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
  loadHoursActionSuggestionsDTO,
  loadHoursAlertListDTO,
  loadHoursAlertsSummaryDTO,
} from "../services/hoursWarningService";

export function useHoursWarning() {
  const summaryStatus = ref<HoursAlertsSummary | null>(null);
  const alerts = ref<HoursAlertItem[]>([]);
  const actionSuggestions = ref<HoursAlertActionSuggestion[]>([]);

  const filterStatus = ref<HoursAlertsFilter>("all");

  const loadingSummary = ref(false);
  const errorSummary = ref<string | null>(null);

  const loadingAlerts = ref(false);
  const errorAlerts = ref<string | null>(null);

  const loadingSuggestions = ref(false);
  const errorSuggestions = ref<string | null>(null);

  const filteredAlerts = computed(() => {
    const status = filterStatus.value;
    const list = alerts.value;

    if (status === "all") return list;
    if (status === "danger") return list.filter((a) => a.level === "danger");
    if (status === "warning") return list.filter((a) => a.level === "warning");
    // done
    return list.filter((a) => a.isSeen);
  });

  const counts = computed(() => {
    const list = alerts.value;
    return {
      all: list.length,
      danger: list.filter((a) => a.level === "danger").length,
      warning: list.filter((a) => a.level === "warning").length,
      done: list.filter((a) => a.isSeen).length,
    };
  });

  async function loadAlerts() {
    await Promise.all([loadSummary(), loadList(), loadSuggestions()]);
  }

  async function loadSummary() {
    loadingSummary.value = true;
    errorSummary.value = null;
    try {
      const dto = await loadHoursAlertsSummaryDTO();
      summaryStatus.value = hoursAlertsSummaryFromDto(dto);
    } catch (e) {
      errorSummary.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingSummary.value = false;
    }
  }

  async function loadList() {
    loadingAlerts.value = true;
    errorAlerts.value = null;
    try {
      const dtoList = await loadHoursAlertListDTO();
      alerts.value = dtoList.map(hoursAlertItemFromDto);
    } catch (e) {
      errorAlerts.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingAlerts.value = false;
    }
  }

  async function loadSuggestions() {
    loadingSuggestions.value = true;
    errorSuggestions.value = null;
    try {
      const dtoList = await loadHoursActionSuggestionsDTO();
      actionSuggestions.value = dtoList.map(hoursAlertActionSuggestionFromDto);
    } catch (e) {
      errorSuggestions.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingSuggestions.value = false;
    }
  }

  function changeFilter(next: HoursAlertsFilter) {
    filterStatus.value = next;
  }

  function markAsSeen(alertId: number) {
    alerts.value = alerts.value.map((a) =>
      a.id === alertId ? { ...a, isSeen: true } : a
    );
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
  };
}
