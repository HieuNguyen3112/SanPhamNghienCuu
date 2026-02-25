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
  const toastMessage = ref<string | null>(null);

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

  const contentApprovedWorks = computed(() => works.value);
  const filteredWorks = computed(() => works.value);
  const worksMissingEvidence = computed(() =>
    works.value
      .filter(
        (work) =>
          work.hoursRequestState === "hours_not_submitted" &&
          work.evidenceCount === 0
      )
      .sort(
        (a, b) => (b.effectiveHoursDisplay ?? 0) - (a.effectiveHoursDisplay ?? 0)
      )
  );

  const selectedIds = computed<number[]>(() => [...selectedWorkIdSet.value]);
  const selectedCount = computed(() => selectedIds.value.length);

  const selectedHoursTotal = computed(() => {
    const selected = selectedWorkIdSet.value;
    return works.value
      .filter((w) => selected.has(w.activityId))
      .reduce((sum, w) => sum + (w.effectiveHoursDisplay ?? 0), 0);
  });

  const selectableIds = computed<number[]>(() =>
    filteredWorks.value
      .filter((w) => isWorkEligibleForSubmit(w))
      .map((w) => w.activityId)
  );

  const canUploadEvidence = computed(() => {
    return (
      !!workDetail.value &&
      selectedEvidenceTypeId.value !== null &&
      selectedEvidenceFile.value !== null &&
      !uploadingEvidence.value
    );
  });

  async function loadApprovedWorks() {
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
        works.value.filter((w) => isWorkEligibleForSubmit(w)).map((w) => w.activityId)
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

  async function loadDetail(activityId: number) {
    const dto = await loadWorkDetailDTO(activityId);
    const detail = workDetailFromDto(dto);
    workDetail.value = detail;
    evidenceFiles.value = [...detail.evidenceFiles];
  }

  function applyFilter(partial: Partial<WorksFilterState>) {
    filter.value = { ...filter.value, ...partial };
    currentPageNumber.value = 1;
    loadApprovedWorks();
  }

  function resetFilter() {
    filter.value = {
      academicYearId: null,
      hoursMode: "all",
      keyword: "",
    };
    currentPageNumber.value = 1;
    loadApprovedWorks();
  }

  async function loadAcademicYears() {
    loadingAcademicYears.value = true;
    try {
      const rows = await loadAcademicYearsDTO();
      academicYearOptions.value = rows.map(academicYearOptionFromDto);

      if (!filter.value.academicYearId) {
        const current =
          academicYearOptions.value.find((item) => item.isActive) ??
          academicYearOptions.value.find((item) => item.isCurrent) ??
          academicYearOptions.value[0];
        filter.value.academicYearId = current?.id ?? null;
      }
    } finally {
      loadingAcademicYears.value = false;
    }
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
    if (!row || !isWorkEligibleForSubmit(row)) return;

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
    } catch (e) {
      evidenceError.value = e instanceof Error ? e.message : String(e);
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
    } catch (e) {
      evidenceError.value = e instanceof Error ? e.message : String(e);
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
    evidenceFiles.value = [];
    evidenceError.value = null;
    uploadEvidenceError.value = null;
    selectedEvidenceTypeId.value = null;
    selectedEvidenceFile.value = null;
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

      toastMessage.value = "Đã gửi duyệt giờ lên khoa.";
      window.setTimeout(() => (toastMessage.value = null), 2500);
    } catch (e) {
      submitError.value = e instanceof Error ? e.message : String(e);
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
    if (!workDetail.value || !selectedEvidenceFile.value || !selectedEvidenceTypeId.value) {
      uploadEvidenceError.value = "Bạn cần chọn loại minh chứng và tệp trước khi tải lên.";
      return;
    }

    uploadingEvidence.value = true;
    uploadEvidenceError.value = null;

    try {
      await uploadHoursEvidenceDTO({
        activityId: workDetail.value.activityId,
        fileTypeId: selectedEvidenceTypeId.value,
        file: selectedEvidenceFile.value,
      });

      selectedEvidenceFile.value = null;
      await Promise.all([
        loadEvidence(workDetail.value.activityId),
        loadApprovedWorks(),
      ]);
      toastMessage.value = "Đã tải lên minh chứng.";
      window.setTimeout(() => (toastMessage.value = null), 2500);
    } catch (e) {
      uploadEvidenceError.value = e instanceof Error ? e.message : String(e);
    } finally {
      uploadingEvidence.value = false;
    }
  }

  async function deleteEvidence(evidenceId: number) {
    if (!workDetail.value) return;

    deletingEvidenceId.value = evidenceId;
    uploadEvidenceError.value = null;

    try {
      await deleteHoursEvidenceDTO(evidenceId);
      await Promise.all([
        loadEvidence(workDetail.value.activityId),
        loadApprovedWorks(),
      ]);
      toastMessage.value = "Đã xóa minh chứng.";
      window.setTimeout(() => (toastMessage.value = null), 2500);
    } catch (e) {
      uploadEvidenceError.value = e instanceof Error ? e.message : String(e);
    } finally {
      deletingEvidenceId.value = null;
    }
  }

  async function initialize() {
    await loadAcademicYears();
    await loadApprovedWorks();
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
    toastMessage,

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
