import { computed, ref } from "vue";
import type {
  AcademicYearOption,
  ApprovedWorkRow,
  EvidenceFile,
  EvidenceFileType,
  WorkDetail,
  WorksFilterState,
} from "../contracts/selectHoursRequest.contract";
import {
  academicYearOptionFromDto,
  approvedWorkRowFromDto,
  evidenceFileFromDto,
  isWorkEligibleForSubmit,
  workDetailFromDto,
} from "../contracts/selectHoursRequest.contract";
import {
  deleteHoursEvidenceDTO,
  loadAcademicYearsDTO,
  loadApprovedWorksDTO,
  loadEvidenceFileTypesDTO,
  loadHoursEvidenceDTO,
  loadWorkDetailDTO,
  submitHoursApprovalRequestDTO,
  uploadHoursEvidenceDTO,
} from "../services/selectHoursRequestService";
import {
  resolveApiErrorMessage,
  useActionFeedback,
} from "@/shared/composables/useActionFeedback";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

export function useSelectHoursRequest() {
  const works = ref<ApprovedWorkRow[]>([]);
  const selectedWorkIdSet = ref<Set<number>>(new Set());

  const filter = ref<WorksFilterState>({
    academicYearId: null,
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
  const { runWithFeedback } = useActionFeedback();
  const { runPageLoad } = usePageLoadFeedback();

  const evidenceFiles = ref<EvidenceFile[]>([]);
  const evidenceFileTypes = ref<EvidenceFileType[]>([]);
  const academicYearOptions = ref<AcademicYearOption[]>([]);
  const selectedEvidenceTypeId = ref<number | null>(null);
  const selectedEvidenceFile = ref<File | null>(null);

  const loadingEvidence = ref(false);
  const loadingEvidenceTypes = ref(false);
  const evidenceError = ref<string | null>(null);
  const uploadEvidenceError = ref<string | null>(null);
  const uploadingEvidence = ref(false);
  const deletingEvidenceId = ref<number | null>(null);
  const loadingAcademicYears = ref(false);

  function resolveFriendlyErrorMessage(error: unknown, fallback: string) {
    return resolveApiErrorMessage(error, fallback);
  }

  const contentApprovedWorks = computed(() => works.value);
  const filteredWorks = computed(() => works.value);
  const worksMissingEvidence = computed(() =>
    works.value
      .filter(
        (work) =>
          work.hoursRequestState === "hours_not_submitted" &&
          work.evidenceCount === 0,
      )
      .sort(
        (a, b) => (b.effectiveHoursDisplay ?? 0) - (a.effectiveHoursDisplay ?? 0),
      ),
  );

  const selectedIds = computed<number[]>(() => [...selectedWorkIdSet.value]);
  const selectedCount = computed(() => selectedIds.value.length);

  const selectedHoursTotal = computed(() => {
    const selected = selectedWorkIdSet.value;
    return works.value
      .filter((work) => selected.has(work.activityId))
      .reduce((sum, work) => sum + (work.effectiveHoursDisplay ?? 0), 0);
  });

  const selectableIds = computed<number[]>(() =>
    filteredWorks.value
      .filter((work) => isWorkEligibleForSubmit(work))
      .map((work) => work.activityId),
  );

  const canUploadEvidence = computed(() => {
    return (
      !!workDetail.value &&
      selectedEvidenceTypeId.value !== null &&
      selectedEvidenceFile.value !== null &&
      !uploadingEvidence.value
    );
  });

  async function loadApprovedWorksInternal() {
    loadingList.value = true;
    errorList.value = null;

    try {
      const dtoList = await loadApprovedWorksDTO({
        academic_year_id: filter.value.academicYearId ?? undefined,
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
          .filter((work) => isWorkEligibleForSubmit(work))
          .map((work) => work.activityId),
      );

      const nextSelected = new Set<number>();
      for (const activityId of selectedWorkIdSet.value) {
        if (selectable.has(activityId)) {
          nextSelected.add(activityId);
        }
      }
      selectedWorkIdSet.value = nextSelected;
    } catch (error) {
      errorList.value = error instanceof Error ? error.message : String(error);
    } finally {
      loadingList.value = false;
    }
  }

  async function loadApprovedWorks(options?: { withFeedback?: boolean }) {
    if (options?.withFeedback === false) {
      await loadApprovedWorksInternal();
      return;
    }

    await runPageLoad(loadApprovedWorksInternal, {
      loading: {
        title: "Đang tải danh sách công trình",
        message: "Hệ thống đang cập nhật dữ liệu giờ NCKH cá nhân...",
      },
    });
  }

  async function loadDetail(activityId: number) {
    const dto = await loadWorkDetailDTO(activityId);
    const detail = workDetailFromDto(dto);
    workDetail.value = detail;
    evidenceFiles.value = [...detail.evidenceFiles];
  }

  function applyFilter(partial: Partial<WorksFilterState>) {
    filter.value = { ...filter.value, ...partial };
    currentPageNumber.value = 1;

    const partialKeys = Object.keys(partial).filter(
      (key) => partial[key as keyof WorksFilterState] !== undefined,
    );
    const keywordOnly = partialKeys.length === 1 && partialKeys[0] === "keyword";
    void loadApprovedWorks({ withFeedback: !keywordOnly });
  }

  function resetFilter() {
    filter.value = {
      academicYearId: null,
      hoursMode: "all",
      keyword: "",
    };
    currentPageNumber.value = 1;
    void loadApprovedWorks();
  }

  async function loadAcademicYears() {
    loadingAcademicYears.value = true;
    try {
      const rows = await loadAcademicYearsDTO();
      academicYearOptions.value = rows.map(academicYearOptionFromDto);

      if (!filter.value.academicYearId) {
        const currentAcademicYear =
          academicYearOptions.value.find((item) => item.isActive) ??
          academicYearOptions.value.find((item) => item.isCurrent) ??
          academicYearOptions.value[0];
        filter.value.academicYearId = currentAcademicYear?.id ?? null;
      }
    } finally {
      loadingAcademicYears.value = false;
    }
  }

  function updateCurrentPageNumber(nextPage: number) {
    if (nextPage === currentPageNumber.value) return;
    currentPageNumber.value = nextPage;
    void loadApprovedWorks();
  }

  function updatePageSize(nextPageSize: number) {
    if (nextPageSize === pageSize.value) return;
    pageSize.value = nextPageSize;
    currentPageNumber.value = 1;
    void loadApprovedWorks();
  }

  function toggleWorkSelection(payload: {
    activityId: number;
    nextChecked: boolean;
  }) {
    const row = works.value.find((work) => work.activityId === payload.activityId);
    if (!row || !isWorkEligibleForSubmit(row)) return;

    const nextSelected = new Set(selectedWorkIdSet.value);
    if (payload.nextChecked) {
      nextSelected.add(payload.activityId);
    } else {
      nextSelected.delete(payload.activityId);
    }
    selectedWorkIdSet.value = nextSelected;
  }

  function toggleSelectAll(payload: {
    selectableIds: number[];
    nextChecked: boolean;
  }) {
    const nextSelected = new Set(selectedWorkIdSet.value);
    for (const activityId of payload.selectableIds) {
      if (payload.nextChecked) {
        nextSelected.add(activityId);
      } else {
        nextSelected.delete(activityId);
      }
    }
    selectedWorkIdSet.value = nextSelected;
  }

  async function loadEvidenceTypesIfNeeded() {
    if (evidenceFileTypes.value.length > 0) return;

    loadingEvidenceTypes.value = true;
    evidenceError.value = null;
    try {
      const rows = await loadEvidenceFileTypesDTO();
      evidenceFileTypes.value = rows.map((item) => ({
        id: item.id,
        code: item.code,
        name: item.name,
      }));
    } catch (error) {
      evidenceError.value = error instanceof Error ? error.message : String(error);
    } finally {
      loadingEvidenceTypes.value = false;
    }
  }

  async function loadEvidence(activityId: number) {
    loadingEvidence.value = true;
    evidenceError.value = null;

    try {
      const rows = await loadHoursEvidenceDTO(activityId);
      evidenceFiles.value = rows.map(evidenceFileFromDto);
      if (workDetail.value && workDetail.value.activityId === activityId) {
        workDetail.value = {
          ...workDetail.value,
          evidenceFiles: [...evidenceFiles.value],
        };
      }
    } catch (error) {
      evidenceError.value = error instanceof Error ? error.message : String(error);
      evidenceFiles.value = [];
    } finally {
      loadingEvidence.value = false;
    }
  }

  async function openWorkDetail(activityId: number) {
    drawerOpen.value = true;
    selectedActivityId.value = activityId;
    workDetail.value = null;
    errorDetail.value = null;
    evidenceError.value = null;
    uploadEvidenceError.value = null;
    selectedEvidenceTypeId.value = null;
    selectedEvidenceFile.value = null;
    loadingDetail.value = true;

    try {
      await loadDetail(activityId);
      await Promise.all([loadEvidenceTypesIfNeeded(), loadEvidence(activityId)]);
    } catch (error) {
      errorDetail.value = error instanceof Error ? error.message : String(error);
    } finally {
      loadingDetail.value = false;
    }
  }

  function closeWorkDetail() {
    drawerOpen.value = false;
    selectedActivityId.value = null;
    workDetail.value = null;
    errorDetail.value = null;
    evidenceFiles.value = [];
    evidenceError.value = null;
    uploadEvidenceError.value = null;
    selectedEvidenceTypeId.value = null;
    selectedEvidenceFile.value = null;
  }

  async function submitRequest() {
    if (submitting.value) return;

    submitError.value = null;

    if (selectedWorkIdSet.value.size === 0) {
      submitError.value = "Bạn chưa chọn công trình nào.";
      return;
    }

    submitting.value = true;
    try {
      await runWithFeedback(
        async () => {
          const activityIds = [...selectedWorkIdSet.value];
          await submitHoursApprovalRequestDTO({ activity_ids: activityIds });
          selectedWorkIdSet.value = new Set();
          await loadApprovedWorks({ withFeedback: false });
        },
        {
          loading: {
            title: "Đang gửi duyệt",
            message: "Đang gửi yêu cầu duyệt giờ lên khoa...",
          },
          success: {
            title: "Thành công",
            message: "Đã gửi duyệt giờ lên khoa thành công.",
          },
          error: {
            title: "Gửi duyệt thất bại",
            message: (error) =>
              resolveFriendlyErrorMessage(
                error,
                "Không thể gửi duyệt giờ lên khoa. Vui lòng thử lại.",
              ),
          },
        },
      );
    } catch (error) {
      submitError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể gửi duyệt giờ lên khoa. Vui lòng thử lại.",
      );
    } finally {
      submitting.value = false;
    }
  }

  function setSelectedEvidenceTypeId(value: number | null) {
    selectedEvidenceTypeId.value = value;
    uploadEvidenceError.value = null;
  }

  function setSelectedEvidenceFile(file: File | null) {
    selectedEvidenceFile.value = file;
    uploadEvidenceError.value = null;
  }

  async function uploadEvidence() {
    if (uploadingEvidence.value) return;

    if (!workDetail.value || !selectedEvidenceFile.value || !selectedEvidenceTypeId.value) {
      uploadEvidenceError.value =
        "Bạn cần chọn loại minh chứng và tệp trước khi tải lên.";
      return;
    }

    uploadingEvidence.value = true;
    uploadEvidenceError.value = null;

    try {
      await runWithFeedback(
        async () => {
          await uploadHoursEvidenceDTO({
            activityId: workDetail.value!.activityId,
            fileTypeId: selectedEvidenceTypeId.value!,
            file: selectedEvidenceFile.value!,
          });
          selectedEvidenceFile.value = null;
          await Promise.all([
            loadEvidence(workDetail.value!.activityId),
            loadApprovedWorks({ withFeedback: false }),
          ]);
        },
        {
          loading: {
            title: "Đang tải minh chứng",
            message: "Vui lòng đợi hệ thống xử lý tệp đính kèm...",
          },
          success: {
            title: "Thành công",
            message: "Đã tải lên minh chứng thành công.",
          },
          error: {
            title: "Tải lên thất bại",
            message: (error) =>
              resolveFriendlyErrorMessage(
                error,
                "Không thể tải lên minh chứng. Vui lòng thử lại.",
              ),
          },
        },
      );
    } catch (error) {
      uploadEvidenceError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể tải lên minh chứng. Vui lòng thử lại.",
      );
    } finally {
      uploadingEvidence.value = false;
    }
  }

  async function deleteEvidence(evidenceId: number) {
    if (!workDetail.value) return;
    if (deletingEvidenceId.value !== null) return;

    deletingEvidenceId.value = evidenceId;
    uploadEvidenceError.value = null;

    try {
      await runWithFeedback(
        async () => {
          await deleteHoursEvidenceDTO(evidenceId);
          await Promise.all([
            loadEvidence(workDetail.value!.activityId),
            loadApprovedWorks({ withFeedback: false }),
          ]);
        },
        {
          loading: {
            title: "Đang xóa minh chứng",
            message: "Đang cập nhật dữ liệu...",
          },
          success: {
            title: "Thành công",
            message: "Đã xóa minh chứng thành công.",
          },
          error: {
            title: "Xóa minh chứng thất bại",
            message: (error) =>
              resolveFriendlyErrorMessage(
                error,
                "Không thể xóa minh chứng. Vui lòng thử lại.",
              ),
          },
        },
      );
    } catch (error) {
      uploadEvidenceError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể xóa minh chứng. Vui lòng thử lại.",
      );
    } finally {
      deletingEvidenceId.value = null;
    }
  }

  async function initialize(options?: {
    defaultHoursMode?: WorksFilterState["hoursMode"];
  }) {
    if (options?.defaultHoursMode) {
      filter.value.hoursMode = options.defaultHoursMode;
    }

    await runPageLoad(
      async () => {
        await loadAcademicYears();
        await loadApprovedWorksInternal();
      },
      {
        loading: {
          title: "Đang khởi tạo kê khai giờ NCKH",
          message: "Hệ thống đang chuẩn bị danh sách công trình và năm học...",
        },
      },
    );
  }

  return {
    works,
    contentApprovedWorks,
    filteredWorks,
    worksMissingEvidence,
    filter,
    academicYearOptions,
    loadingAcademicYears,

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

    totalApprovedCount,
    currentPageNumber,
    pageSize,
    totalItemCount,

    evidenceFiles,
    evidenceFileTypes,
    selectedEvidenceTypeId,
    selectedEvidenceFile,
    loadingEvidence,
    loadingEvidenceTypes,
    evidenceError,
    uploadEvidenceError,
    uploadingEvidence,
    deletingEvidenceId,
    canUploadEvidence,

    initialize,
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

    setSelectedEvidenceTypeId,
    setSelectedEvidenceFile,
    uploadEvidence,
    deleteEvidence,
  };
}
