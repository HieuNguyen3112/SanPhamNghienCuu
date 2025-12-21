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

  /** ✅ chỉ công trình hợp lệ nội dung (Khoa + Trường duyệt) */
  const contentApprovedWorks = computed(() =>
    works.value.filter((w) => isContentFullyApproved(w))
  );

  const totalApprovedCount = computed(() => contentApprovedWorks.value.length);

  /** ✅ lọc theo “duyệt giờ” + keyword */
  const filteredWorks = computed(() => {
    const keyword = filter.value.keyword.trim().toLowerCase();

    return contentApprovedWorks.value.filter((w) => {
      const matchHours =
        filter.value.hoursMode === "all"
          ? true
          : filter.value.hoursMode === "not_reviewed_hours"
          ? w.hoursRequestState === "eligible"
          : filter.value.hoursMode === "waiting_hours"
          ? w.hoursRequestState === "submitted"
          : w.hoursRequestState === "hours_approved";

      if (!matchHours) return false;

      if (!keyword) return true;
      const haystack = `${w.activityCode} ${w.title}`.toLowerCase();
      return haystack.includes(keyword);
    });
  });

  const selectedIds = computed<number[]>(() => [...selectedWorkIdSet.value]);
  const selectedCount = computed(() => selectedIds.value.length);

  const selectedHoursTotal = computed(() => {
    const selected = selectedWorkIdSet.value;
    return works.value
      .filter((w) => selected.has(w.activityId))
      .reduce((sum, w) => sum + (w.hoursAssigned ?? 0), 0);
  });

  /**
   * ✅ selectableIds nên bám theo FILTER (để select all đúng những gì đang “theo dõi”)
   * và chỉ chọn được khi: nội dung hợp lệ + hours_request_state=eligible
   */
  const selectableIds = computed<number[]>(() =>
    filteredWorks.value
      .filter((w) => w.hoursRequestState === "eligible")
      .map((w) => w.activityId)
  );

  async function loadApprovedWorks() {
    loadingList.value = true;
    errorList.value = null;

    try {
      const dtoList = await loadApprovedWorksDTO();
      works.value = dtoList.map(approvedWorkRowFromDto);

      // dọn selection: chỉ giữ những item vẫn còn eligible & nội dung hợp lệ
      const selectable = new Set(
        works.value
          .filter(
            (w) =>
              isContentFullyApproved(w) && w.hoursRequestState === "eligible"
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
  }

  function resetFilter() {
    filter.value = { hoursMode: "all", keyword: "" };
  }

  function toggleWorkSelection(payload: {
    activityId: number;
    nextChecked: boolean;
  }) {
    const row = works.value.find((w) => w.activityId === payload.activityId);
    if (!row) return;

    // ✅ P0: phải hợp lệ nội dung + chưa gửi duyệt giờ
    if (!isContentFullyApproved(row)) return;
    if (row.hoursRequestState !== "eligible") return;

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

      works.value = works.value.map((w) => {
        if (
          activityIds.includes(w.activityId) &&
          isContentFullyApproved(w) &&
          w.hoursRequestState === "eligible"
        ) {
          return { ...w, hoursRequestState: "submitted" };
        }
        return w;
      });

      selectedWorkIdSet.value = new Set();
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

    loadApprovedWorks,
    applyFilter,
    resetFilter,
    toggleWorkSelection,
    toggleSelectAll,
    openWorkDetail,
    closeWorkDetail,
    submitRequest,
  };
}
