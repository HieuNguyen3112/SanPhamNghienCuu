import { reactive, ref } from "vue";
import type {
  HourApprovalFilter,
  HourApprovalRequestDetail,
  HourApprovalRequestSummary,
  RejectPayload,
} from "../contracts/hourApproval.contract";
import { hourApprovalMappers } from "../contracts/hourApproval.contract";
import type { HourApprovalService } from "../services/hourApprovalService";

export function createDefaultHourApprovalFilter(): HourApprovalFilter {
  return {
    facultyId: null,
    status: "all",
    submittedFrom: null,
    submittedTo: null,
    searchText: "",
  };
}

export function useHourApprovalManagement(
  service: HourApprovalService,
  initialFilter?: Partial<HourApprovalFilter>
) {
  const filter = reactive<HourApprovalFilter>({
    ...createDefaultHourApprovalFilter(),
    ...initialFilter,
  });

  const rows = ref<HourApprovalRequestSummary[]>([]);
  const selectedRequestId = ref<number | null>(null);
  const requestDetail = ref<HourApprovalRequestDetail | null>(null);
  const isDetailOpen = ref(false);

  const page = ref(1);
  const perPage = ref(12);
  const total = ref(0);
  const lastPage = ref(1);

  const loadingList = ref(false);
  const loadingDetail = ref(false);
  const loadingApprove = ref(false);
  const loadingReject = ref(false);

  const errorList = ref<string | null>(null);
  const errorDetail = ref<string | null>(null);
  const errorApprove = ref<string | null>(null);
  const errorReject = ref<string | null>(null);

  async function loadRequests() {
    loadingList.value = true;
    errorList.value = null;
    try {
      const response = await service.getRequests({ ...filter }, page.value, perPage.value);
      rows.value = response.data.items.map(hourApprovalMappers.summaryFromDto);
      total.value = response.data.pagination.total;
      lastPage.value = response.data.pagination.last_page;
      page.value = response.data.pagination.page;
      perPage.value = response.data.pagination.per_page;
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
      errorList.value = "Không tải được dữ liệu. Vui lòng thử lại.";
      rows.value = [];
      total.value = 0;
      lastPage.value = 1;
    } finally {
      loadingList.value = false;
    }
  }

  async function applyFilter(nextFilter: Partial<HourApprovalFilter>) {
    Object.assign(filter, nextFilter);
    page.value = 1;
    await loadRequests();
  }

  async function resetFilter() {
    Object.assign(filter, createDefaultHourApprovalFilter());
    page.value = 1;
    await loadRequests();
  }

  async function openRequestDetail(requestId: number) {
    selectedRequestId.value = requestId;
    isDetailOpen.value = true;

    loadingDetail.value = true;
    errorDetail.value = null;

    try {
      const dto = await service.getRequestDetail(requestId);
      requestDetail.value = hourApprovalMappers.detailFromDto(dto);
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
      errorDetail.value = "Không tải được chi tiết. Vui lòng thử lại.";
      requestDetail.value = null;
    } finally {
      loadingDetail.value = false;
    }
  }

  function closeRequestDetail() {
    isDetailOpen.value = false;
    selectedRequestId.value = null;
    requestDetail.value = null;
    errorDetail.value = null;
  }

  async function approveRequest(requestId: number) {
    loadingApprove.value = true;
    errorApprove.value = null;
    try {
      await service.approve(requestId);
      await loadRequests();
      if (selectedRequestId.value === requestId) {
        await openRequestDetail(requestId);
      }
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
      errorApprove.value = "Không thể duyệt yêu cầu. Vui lòng thử lại.";
    } finally {
      loadingApprove.value = false;
    }
  }

  async function rejectRequest(requestId: number, payload: RejectPayload) {
    loadingReject.value = true;
    errorReject.value = null;
    try {
      await service.reject(
        requestId,
        hourApprovalMappers.rejectPayloadToDto(payload)
      );
      await loadRequests();
      if (selectedRequestId.value === requestId) {
        await openRequestDetail(requestId);
      }
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
      errorReject.value = "Không thể từ chối yêu cầu. Vui lòng thử lại.";
    } finally {
      loadingReject.value = false;
    }
  }

  async function updatePage(nextPage: number) {
    page.value = Math.min(Math.max(1, nextPage), lastPage.value);
    await loadRequests();
  }

  async function updatePageSize(nextSize: number) {
    if (!Number.isFinite(nextSize) || nextSize <= 0) return;
    perPage.value = nextSize;
    page.value = 1;
    await loadRequests();
  }

  return {
    filter,
    rows,
    requestDetail,
    isDetailOpen,

    page,
    perPage,
    total,

    loadingList,
    loadingDetail,
    loadingApprove,
    loadingReject,

    errorList,
    errorDetail,
    errorApprove,
    errorReject,

    loadRequests,
    applyFilter,
    resetFilter,
    openRequestDetail,
    closeRequestDetail,
    approveRequest,
    rejectRequest,
    updatePage,
    updatePageSize,
  };
}
