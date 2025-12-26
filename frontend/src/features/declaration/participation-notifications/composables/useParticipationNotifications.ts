import { computed, onMounted, ref, watch } from "vue";
import {
  mapParticipationNotificationDtoToModel,
  type ParticipationNotification,
  type NotificationStatus,
} from "../contracts/participationNotificationsContract";
import {
  accept_participation_notification,
  list_participation_notifications,
  reject_participation_notification,
} from "../services/participationNotifications.service";

export type StatusFilter = "ALL" | NotificationStatus;

export interface NotificationFilters {
  status: StatusFilter;
  q: string;
  from: string; // YYYY-MM-DD
  to: string; // YYYY-MM-DD
}

export function useParticipationNotifications(params: {
  currentUserId: number;
}) {
  const { currentUserId } = params;

  const loading = ref(false);
  const rows = ref<ParticipationNotification[]>([]);

  const filters = ref<NotificationFilters>({
    status: "ALL",
    q: "",
    from: "",
    to: "",
  });

  const currentPageNumber = ref(1);
  const pageSize = ref(8);

  const detailOpen = ref(false);
  const selectedId = ref<number | null>(null);

  const selected = computed<ParticipationNotification | null>(() => {
    if (!selectedId.value) return null;
    return rows.value.find((x) => x.id === selectedId.value) ?? null;
  });

  const filteredRows = computed(() => {
    const q = filters.value.q.trim().toLowerCase();

    const from = filters.value.from
      ? new Date(`${filters.value.from}T00:00:00`)
      : null;
    const to = filters.value.to
      ? new Date(`${filters.value.to}T23:59:59`)
      : null;

    return rows.value
      .filter((x) =>
        filters.value.status === "ALL"
          ? true
          : x.status === filters.value.status
      )
      .filter((x) => (q ? x.workTitle.toLowerCase().includes(q) : true))
      .filter((x) => {
        if (!from && !to) return true;
        const dt = new Date(x.requestedAt);
        if (from && dt < from) return false;
        if (to && dt > to) return false;
        return true;
      })
      .sort(
        (a, b) =>
          new Date(b.requestedAt).getTime() - new Date(a.requestedAt).getTime()
      );
  });

  const totalPages = computed(() => {
    const n = filteredRows.value.length;
    return Math.max(1, Math.ceil(n / pageSize.value));
  });

  const pagedRows = computed(() => {
    const start = (currentPageNumber.value - 1) * pageSize.value;
    return filteredRows.value.slice(start, start + pageSize.value);
  });

  function resetFilters() {
    filters.value = { status: "ALL", q: "", from: "", to: "" };
  }

  function openDetail(rowId: number) {
    selectedId.value = rowId;
    detailOpen.value = true;
  }

  function closeDetail() {
    detailOpen.value = false;
    selectedId.value = null;
  }

  async function load() {
    loading.value = true;
    try {
      const dtoList = await list_participation_notifications();
      rows.value = dtoList.map(mapParticipationNotificationDtoToModel);
    } finally {
      loading.value = false;
    }
  }

  async function acceptSelected() {
    if (!selected.value) return;
    if (selected.value.status !== "PENDING") return;

    loading.value = true;
    try {
      const updatedDto = await accept_participation_notification(
        selected.value.id,
        currentUserId
      );
      const updated = mapParticipationNotificationDtoToModel(updatedDto);
      rows.value = rows.value.map((x) => (x.id === updated.id ? updated : x));
    } finally {
      loading.value = false;
    }
  }

  async function rejectSelected(reason: string) {
    if (!selected.value) return;
    if (selected.value.status !== "PENDING") return;

    loading.value = true;
    try {
      const updatedDto = await reject_participation_notification(
        selected.value.id,
        currentUserId,
        reason
      );
      const updated = mapParticipationNotificationDtoToModel(updatedDto);
      rows.value = rows.value.map((x) => (x.id === updated.id ? updated : x));
    } finally {
      loading.value = false;
    }
  }

  function onUpdateCurrentPageNumber(n: number) {
    currentPageNumber.value = n;
  }

  function onUpdatePageSize(s: number) {
    pageSize.value = s;
    currentPageNumber.value = 1;
  }

  watch([filteredRows, pageSize], () => {
    if (currentPageNumber.value > totalPages.value) {
      currentPageNumber.value = totalPages.value;
    }
  });

  onMounted(load);

  return {
    loading,
    rows,

    filters,
    resetFilters,

    currentPageNumber,
    pageSize,
    totalPages,
    filteredRows,
    pagedRows,
    onUpdateCurrentPageNumber,
    onUpdatePageSize,

    detailOpen,
    selected,
    openDetail,
    closeDetail,

    acceptSelected,
    rejectSelected,
    currentUserId,
  };
}
