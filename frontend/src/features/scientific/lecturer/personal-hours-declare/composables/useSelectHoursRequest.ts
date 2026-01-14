import { computed, ref } from "vue";
import type {
  ApprovedWorkRow,
  WorkDetail,
  WorksFilterState,
} from "../contracts/selectHoursRequest.contract";
import {
  approvedWorkRowFromDto,
  workDetailFromDto,
  isContentFullyApproved,
} from "../contracts/selectHoursRequest.contract";
import {
  loadApprovedWorksDTO,
  loadWorkDetailDTO,
  submitHoursApprovalRequestDTO,
} from "../services/selectHoursRequestService";

export function useSelectHoursRequest() {
  const works = ref<ApprovedWorkRow[]>([]);
  const selectedWorkIdSet = ref<Set<number>>(new Set());

  const filter = ref<WorksFilterState>({
    hoursMode: "all",
    keyword: "",
  });

  const currentPageNumber = ref(1);
  const pageSize = ref(12);
  const totalItemCount = ref(0);
  const totalApprovedCount = ref(0);

  const drawerOpen = ref(false);
  const selectedActivityId = ref<number | null>(null);
  const workDetail = ref<WorkDetail | null>(null);

  const loadingList = ref(false);
  const errorList = ref<string | null>(null);

  const loadingDetail = ref(false);
  const errorDetail = ref<string | null>(null);

  const submitting = ref(false);
  const submitError = ref<string | null>(null);
  const toastMessage = ref<string | null>(null);

  const contentApprovedWorks = computed(() => works.value);
  const filteredWorks = computed(() => works.value);

  const selectedIds = computed<number[]>(() => [...selectedWorkIdSet.value]);
  const selectedCount = computed(() => selectedIds.value.length);

  const selectedHoursTotal = computed(() => {
    const selected = selectedWorkIdSet.value;
    return works.value
      .filter((w) => selected.has(w.activityId))
      .reduce((sum, w) => sum + (w.hoursAssigned ?? 0), 0);
  });

  const selectableIds = computed<number[]>(() =>
    filteredWorks.value
      .filter(
        (w) =>
          w.hoursRequestState === "eligible" ||
          w.hoursRequestState === "rejected"
      )
      .map((w) => w.activityId)
  );

  async function loadApprovedWorks() {
    loadingList.value = true;
    errorList.value = null;

    try {
      const dtoList = await loadApprovedWorksDTO({
        status: filter.value.hoursMode,
        q: filter.value.keyword,
        page: currentPageNumber.value,
        per_page: pageSize.value,
      });

      works.value = dtoList.items.map(approvedWorkRowFromDto);
      totalItemCount.value = dtoList.pagination.total;
      totalApprovedCount.value = dtoList.summary.approved_count ?? 0;

      const selectable = new Set(
        works.value
          .filter(
            (w) =>
              isContentFullyApproved(w) &&
              (w.hoursRequestState === "eligible" ||
                w.hoursRequestState === "rejected")
          )
          .map((w) => w.activityId)
      );

      const next = new Set<number>();
      for (const id of selectedWorkIdSet.value) {
        if (selectable.has(id)) next.add(id);
      }
      selectedWorkIdSet.value = next;
    } catch (e) {
      errorList.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingList.value = false;
    }
  }

  function applyFilter(partial: Partial<WorksFilterState>) {
    filter.value = { ...filter.value, ...partial };
    currentPageNumber.value = 1;
    loadApprovedWorks();
  }

  function resetFilter() {
    filter.value = { hoursMode: "all", keyword: "" };
    currentPageNumber.value = 1;
    loadApprovedWorks();
  }

  function updateCurrentPageNumber(nextPage: number) {
    if (nextPage === currentPageNumber.value) return;
    currentPageNumber.value = nextPage;
    loadApprovedWorks();
  }

  function updatePageSize(nextPageSize: number) {
    if (nextPageSize === pageSize.value) return;
    pageSize.value = nextPageSize;
    currentPageNumber.value = 1;
    loadApprovedWorks();
  }

  function toggleWorkSelection(payload: {
    activityId: number;
    nextChecked: boolean;
  }) {
    const row = works.value.find((w) => w.activityId === payload.activityId);
    if (!row) return;

    if (!isContentFullyApproved(row)) return;
    if (
      row.hoursRequestState !== "eligible" &&
      row.hoursRequestState !== "rejected"
    )
      return;

    const next = new Set(selectedWorkIdSet.value);
    if (payload.nextChecked) next.add(payload.activityId);
    else next.delete(payload.activityId);
    selectedWorkIdSet.value = next;
  }

  function toggleSelectAll(payload: {
    selectableIds: number[];
    nextChecked: boolean;
  }) {
    const next = new Set(selectedWorkIdSet.value);
    for (const id of payload.selectableIds) {
      if (payload.nextChecked) next.add(id);
      else next.delete(id);
    }
    selectedWorkIdSet.value = next;
  }

  async function openWorkDetail(activityId: number) {
    drawerOpen.value = true;
    selectedActivityId.value = activityId;
    workDetail.value = null;
    errorDetail.value = null;
    loadingDetail.value = true;

    try {
      const dto = await loadWorkDetailDTO(activityId);
      workDetail.value = workDetailFromDto(dto);
    } catch (e) {
      errorDetail.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingDetail.value = false;
    }
  }

  function closeWorkDetail() {
    drawerOpen.value = false;
    selectedActivityId.value = null;
    workDetail.value = null;
    errorDetail.value = null;
  }

  async function submitRequest() {
    submitError.value = null;
    toastMessage.value = null;

    if (selectedWorkIdSet.value.size === 0) {
      submitError.value = "Bạn chưa chọn công trình nào.";
      return;
    }

    submitting.value = true;
    try {
      const activityIds = [...selectedWorkIdSet.value];
      await submitHoursApprovalRequestDTO({ activity_ids: activityIds });

      selectedWorkIdSet.value = new Set();
      await loadApprovedWorks();

      toastMessage.value = "Đã gửi yêu cầu xét duyệt giờ NCKH lên Khoa.";
      window.setTimeout(() => (toastMessage.value = null), 2500);
    } catch (e) {
      submitError.value = e instanceof Error ? e.message : String(e);
    } finally {
      submitting.value = false;
    }
  }

  return {
    works,
    contentApprovedWorks,
    filteredWorks,
    filter,

    selectedIds,
    selectedCount,
    selectedHoursTotal,
    selectableIds,

    drawerOpen,
    selectedActivityId,
    workDetail,

    loadingList,
    errorList,
    loadingDetail,
    errorDetail,

    submitting,
    submitError,
    toastMessage,

    totalApprovedCount,
    currentPageNumber,
    pageSize,
    totalItemCount,

    loadApprovedWorks,
    applyFilter,
    resetFilter,
    updateCurrentPageNumber,
    updatePageSize,
    toggleWorkSelection,
    toggleSelectAll,
    openWorkDetail,
    closeWorkDetail,
    submitRequest,
  };
}
