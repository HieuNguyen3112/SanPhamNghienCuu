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
      const dtoRows = await service.getRequests({ ...filter });
      rows.value = dtoRows.map(hourApprovalMappers.summaryFromDto);
    } catch (e) {
      errorList.value = e instanceof Error ? e.message : String(e);
      rows.value = [];
    } finally {
      loadingList.value = false;
    }
  }

  async function applyFilter(nextFilter: Partial<HourApprovalFilter>) {
    Object.assign(filter, nextFilter);
    await loadRequests();
  }

  async function resetFilter() {
    Object.assign(filter, createDefaultHourApprovalFilter());
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
      errorDetail.value = e instanceof Error ? e.message : String(e);
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
      errorApprove.value = e instanceof Error ? e.message : String(e);
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
      errorReject.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingReject.value = false;
    }
  }

  return {
    filter,
    rows,
    selectedRequestId,
    requestDetail,
    isDetailOpen,

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
  };
}
