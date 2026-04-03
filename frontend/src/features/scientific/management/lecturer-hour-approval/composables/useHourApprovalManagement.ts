import { reactive, ref } from "vue";
import type {
  ApprovePayload,
  HourApprovalFilter,
  HourApprovalRequestDetail,
  HourApprovalRequestSummary,
  RejectPayload,
} from "../contracts/hourApproval.contract";
import { hourApprovalMappers } from "../contracts/hourApproval.contract";
import type { HourApprovalService } from "../services/hourApprovalService";
import {
  resolveApiErrorMessage,
  useActionFeedback,
} from "@/shared/composables/useActionFeedback";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

export function createDefaultHourApprovalFilter(): HourApprovalFilter {
  return {
    facultyId: null,
    academicYearId: null,
    status: "pending",
    submittedFrom: null,
    submittedTo: null,
    searchText: "",
  };
}

export function useHourApprovalManagement(
  service: HourApprovalService,
  initialFilter?: Partial<HourApprovalFilter>
) {
  const { runWithFeedback } = useActionFeedback();
  const { runPageLoad } = usePageLoadFeedback();

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

  async function loadRequestsInternal() {
    loadingList.value = true;
    errorList.value = null;
    try {
      const response = await service.getRequests(
        { ...filter },
        page.value,
        perPage.value
      );
      rows.value = response.data.items.map(hourApprovalMappers.summaryFromDto);
      total.value = response.data.pagination.total;
      lastPage.value = response.data.pagination.last_page;
      page.value = response.data.pagination.page;
      perPage.value = response.data.pagination.per_page;
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
      errorList.value = "Không tải được dữ liệu. Vui lòng thử lại.";
      rows.value = [];
      total.value = 0;
      lastPage.value = 1;
    } finally {
      loadingList.value = false;
    }
  }

  async function loadRequests(options?: { withFeedback?: boolean }) {
    if (options?.withFeedback === false) {
      await loadRequestsInternal();
      return;
    }

    await runPageLoad(loadRequestsInternal, {
      loading: {
        title: "Đang tải xét duyệt giờ NCKH",
        message: "Hệ thống đang cập nhật danh sách yêu cầu giờ nghiên cứu khoa học...",
      },
    });
  }

  async function applyFilter(nextFilter: Partial<HourApprovalFilter>) {
    const keywordOnly =
      Object.prototype.hasOwnProperty.call(nextFilter, "searchText") &&
      (nextFilter.searchText ?? "") !== filter.searchText &&
      (nextFilter.facultyId ?? filter.facultyId) === filter.facultyId &&
      (nextFilter.academicYearId ?? filter.academicYearId) ===
        filter.academicYearId &&
      (nextFilter.status ?? filter.status) === filter.status &&
      (nextFilter.submittedFrom ?? filter.submittedFrom) ===
        filter.submittedFrom &&
      (nextFilter.submittedTo ?? filter.submittedTo) === filter.submittedTo;

    Object.assign(filter, nextFilter);
    page.value = 1;
    await loadRequests({ withFeedback: !keywordOnly });
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
      const dto = await service.getRequestDetail(requestId, {
        academicYearId: filter.academicYearId,
      });
      requestDetail.value = hourApprovalMappers.detailFromDto(dto);
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
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

  async function approveRequest(requestId: number, payload?: ApprovePayload) {
    loadingApprove.value = true;
    errorApprove.value = null;

    try {
      await runWithFeedback(
        async () => {
          await service.approve(
            requestId,
            payload ? hourApprovalMappers.approvePayloadToDto(payload) : undefined
          );
          await loadRequests({ withFeedback: false });
          if (selectedRequestId.value === requestId) {
            await openRequestDetail(requestId);
          }
        },
        {
          loading: {
            title: "Đang duyệt yêu cầu",
            message: "Hệ thống đang cập nhật kết quả xét duyệt...",
          },
          success: {
            title: "Thành công",
            message: "Đã duyệt yêu cầu giờ NCKH.",
          },
          error: {
            title: "Duyệt yêu cầu thất bại",
            message: (error) =>
              resolveApiErrorMessage(
                error,
                "Không thể duyệt yêu cầu. Vui lòng thử lại."
              ),
          },
        }
      );
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
      errorApprove.value = resolveApiErrorMessage(
        error,
        "Không thể duyệt yêu cầu. Vui lòng thử lại."
      );
    } finally {
      loadingApprove.value = false;
    }
  }

  async function rejectRequest(requestId: number, payload: RejectPayload) {
    loadingReject.value = true;
    errorReject.value = null;
    const isRevisionMode = payload.decisionMode === "revision";

    try {
      await runWithFeedback(
        async () => {
          await service.reject(
            requestId,
            hourApprovalMappers.rejectPayloadToDto(payload)
          );
          await loadRequests({ withFeedback: false });
          if (selectedRequestId.value === requestId) {
            await openRequestDetail(requestId);
          }
        },
        {
          loading: {
            title: isRevisionMode
              ? "Đang gửi yêu cầu chỉnh sửa"
              : "Đang xử lý từ chối",
            message: "Hệ thống đang cập nhật kết quả xét duyệt...",
          },
          success: {
            title: "Thành công",
            message: isRevisionMode
              ? "Đã gửi yêu cầu chỉnh sửa cho giảng viên."
              : "Đã từ chối yêu cầu giờ NCKH.",
          },
          error: {
            title: isRevisionMode
              ? "Gửi yêu cầu chỉnh sửa thất bại"
              : "Từ chối yêu cầu thất bại",
            message: (error) =>
              resolveApiErrorMessage(
                error,
                isRevisionMode
                  ? "Không thể gửi yêu cầu chỉnh sửa. Vui lòng thử lại."
                  : "Không thể từ chối yêu cầu. Vui lòng thử lại."
              ),
          },
        }
      );
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error(error);
      errorReject.value = resolveApiErrorMessage(
        error,
        isRevisionMode
          ? "Không thể gửi yêu cầu chỉnh sửa. Vui lòng thử lại."
          : "Không thể từ chối yêu cầu. Vui lòng thử lại."
      );
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
