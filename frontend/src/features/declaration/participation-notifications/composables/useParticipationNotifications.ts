import { onMounted, ref, watch } from "vue";
import {
  mapParticipationNotificationDtoToModel,
  type ParticipationNotification,
  type NotificationStatus,
} from "../contracts/participationNotificationsContract";
import {
  accept_participation_notification,
  get_participation_notification_detail,
  list_participation_notifications,
  reject_participation_notification,
} from "../services/participationNotifications.service";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

export type StatusFilter = "ALL" | NotificationStatus;

export interface NotificationFilters {
  status: StatusFilter;
  q: string;
  from: string; // YYYY-MM-DD
  to: string; // YYYY-MM-DD
}

export function useParticipationNotifications() {
  const loading = ref(false);
  const processingDecision = ref(false);
  const rows = ref<ParticipationNotification[]>([]);

  const filters = ref<NotificationFilters>({
    status: "ALL",
    q: "",
    from: "",
    to: "",
  });

  const currentPageNumber = ref(1);
  const pageSize = ref(8);
  const totalItems = ref(0);
  const totalPages = ref(1);

  const detailOpen = ref(false);
  const selectedId = ref<number | null>(null);
  const selected = ref<ParticipationNotification | null>(null);

  const { showSuccessModal, showErrorModal } = useActionResultModal();

  const notificationMessage = ref<string | null>(null);
  let notificationTimer: number | null = null;

  function setNotification(message: string) {
    notificationMessage.value = message;
    if (notificationTimer != null) {
      window.clearTimeout(notificationTimer);
    }
    notificationTimer = window.setTimeout(() => {
      notificationMessage.value = null;
      notificationTimer = null;
    }, 2500);
  }

  function resolveFriendlyErrorMessage(error: unknown, fallback: string) {
    const raw =
      error instanceof Error
        ? error.message
        : typeof error === "string"
          ? error
          : "";
    const message = raw.split("\n")[0]?.trim() ?? "";

    if (!message) return fallback;
    if (/Network Error|timeout|ECONN/i.test(message)) {
      return "Không thể kết nối máy chủ. Vui lòng thử lại.";
    }
    return message;
  }

  function buildListParams() {
    return {
      status: filters.value.status,
      q: filters.value.q.trim() || undefined,
      from: filters.value.from || undefined,
      to: filters.value.to || undefined,
      page: currentPageNumber.value,
      per_page: pageSize.value,
    };
  }

  async function loadList() {
    loading.value = true;
    try {
      const response = await list_participation_notifications(buildListParams());
      rows.value = response.items.map(mapParticipationNotificationDtoToModel);
      totalItems.value = response.pagination.total;
      totalPages.value = response.pagination.last_page;
      currentPageNumber.value = response.pagination.page;
      pageSize.value = response.pagination.per_page;
    } catch (error) {
      console.error(error);
      rows.value = [];
      totalItems.value = 0;
      totalPages.value = 1;
      setNotification("Không thể tải danh sách yêu cầu xác nhận. Vui lòng thử lại.");
    } finally {
      loading.value = false;
    }
  }

  async function loadDetail(id: number) {
    loading.value = true;
    try {
      const dto = await get_participation_notification_detail(id);
      selected.value = mapParticipationNotificationDtoToModel(dto);
    } catch (error) {
      console.error(error);
      selected.value = null;
      setNotification("Không thể tải chi tiết yêu cầu. Vui lòng thử lại.");
    } finally {
      loading.value = false;
    }
  }

  function resetFilters() {
    filters.value = { status: "ALL", q: "", from: "", to: "" };
    currentPageNumber.value = 1;
    loadList();
  }

  function openDetail(rowId: number) {
    selectedId.value = rowId;
    detailOpen.value = true;
    selected.value = null;
    loadDetail(rowId);
  }

  function closeDetail() {
    detailOpen.value = false;
    selectedId.value = null;
    selected.value = null;
  }

  async function acceptSelected() {
    if (!selected.value) return;
    if (selected.value.status !== "PENDING") return;
    if (processingDecision.value) return;

    processingDecision.value = true;
    try {
      const updatedDto = await accept_participation_notification(selected.value.id);
      const updated = mapParticipationNotificationDtoToModel(updatedDto);
      selected.value = updated;
      rows.value = rows.value.map((x) => (x.id === updated.id ? updated : x));
      showSuccessModal("Đã xác nhận tham gia thành công.", "Thành công", undefined, {
        onClose: async () => {
          closeDetail();
          await loadList();
        },
      });
    } catch (error) {
      console.error(error);
      showErrorModal(
        resolveFriendlyErrorMessage(
          error,
          "Không thể xác nhận tham gia. Vui lòng thử lại."
        ),
        "Xác nhận thất bại",
        error
      );
    } finally {
      processingDecision.value = false;
    }
  }

  async function rejectSelected(reason: string) {
    if (!selected.value) return;
    if (selected.value.status !== "PENDING") return;
    if (processingDecision.value) return;

    processingDecision.value = true;
    try {
      const updatedDto = await reject_participation_notification(
        selected.value.id,
        reason
      );
      const updated = mapParticipationNotificationDtoToModel(updatedDto);
      selected.value = updated;
      rows.value = rows.value.map((x) => (x.id === updated.id ? updated : x));
      showSuccessModal("Đã từ chối tham gia thành công.", "Thành công", undefined, {
        onClose: async () => {
          closeDetail();
          await loadList();
        },
      });
    } catch (error) {
      console.error(error);
      showErrorModal(
        resolveFriendlyErrorMessage(
          error,
          "Không thể từ chối tham gia. Vui lòng thử lại."
        ),
        "Từ chối thất bại",
        error
      );
    } finally {
      processingDecision.value = false;
    }
  }

  function onUpdateCurrentPageNumber(n: number) {
    currentPageNumber.value = n;
    loadList();
  }

  function onUpdatePageSize(s: number) {
    pageSize.value = s;
    currentPageNumber.value = 1;
    loadList();
  }

  let filterTimer: number | null = null;
  watch(
    () => [
      filters.value.status,
      filters.value.q,
      filters.value.from,
      filters.value.to,
    ],
    () => {
      if (filterTimer != null) {
        window.clearTimeout(filterTimer);
      }
      filterTimer = window.setTimeout(() => {
        currentPageNumber.value = 1;
        loadList();
      }, 300);
    }
  );

  onMounted(loadList);

  return {
    loading,
    rows,

    filters,
    resetFilters,

    currentPageNumber,
    pageSize,
    totalPages,
    totalItems,
    onUpdateCurrentPageNumber,
    onUpdatePageSize,

    detailOpen,
    selected,
    openDetail,
    closeDetail,
    processingDecision,

    acceptSelected,
    rejectSelected,

    notificationMessage,
  };
}
