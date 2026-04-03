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
  isHoursRequestApiError,
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

type SubmitIssueMap = Record<number, string[]>;

type DeleteEvidenceConfirmState = {
  evidenceId: number;
  message: string;
} | null;

const HOURS_MODE_OPTIONS: WorksFilterState["hoursMode"][] = [
  "all",
  "hours_not_submitted",
  "hours_pending_faculty",
  "hours_need_revision",
  "hours_approved",
  "hours_rejected",
];

const PAGE_SIZE_OPTIONS = [8, 12, 20, 50] as const;
const MAX_KEYWORD_LENGTH = 255;
const MAX_EVIDENCE_FILE_BYTES = 10 * 1024 * 1024;
const PDF_FILE_EXTENSION = ".pdf";
const PDF_MIME_TYPE = "application/pdf";

const BACKEND_ERROR_MESSAGE_MAP: Record<string, string> = {
  EVIDENCE_LOCKED_BY_APPROVED_HOURS:
    "Hồ sơ giờ đã được khoa duyệt nên bạn không thể cập nhật minh chứng nữa.",
  WORK_NOT_FOUND:
    "Không tìm thấy công trình hoặc bạn không còn quyền thao tác với công trình này.",
  EVIDENCE_REQUIRED:
    "Mỗi công trình phải có ít nhất một minh chứng PDF hợp lệ trước khi gửi duyệt giờ.",
  HOURS_ALREADY_PENDING:
    "Bạn đã gửi duyệt giờ trước đó và đang chờ khoa xử lý.",
  HOURS_FINAL_REJECTED:
    "Yêu cầu này đã bị từ chối hẳn và không thể gửi lại.",
  NO_SUBMITTABLE_WORKS:
    "Không có công trình đủ điều kiện để gửi duyệt trong yêu cầu này.",
};

function dedupeMessages(messages: string[]): string[] {
  return Array.from(
    new Set(messages.map((message) => message.trim()).filter(Boolean))
  );
}

function normalizeKeyword(value: string): string {
  return value
    .replace(/\s+/g, " ")
    .trim()
    .slice(0, MAX_KEYWORD_LENGTH);
}

function sanitizeHoursMode(
  value: WorksFilterState["hoursMode"] | string | null | undefined,
  fallback: WorksFilterState["hoursMode"]
): WorksFilterState["hoursMode"] {
  if (
    value &&
    HOURS_MODE_OPTIONS.includes(value as WorksFilterState["hoursMode"])
  ) {
    return value as WorksFilterState["hoursMode"];
  }

  return fallback;
}

function sanitizePageNumber(value: number): number {
  return Math.max(1, Math.trunc(Number.isFinite(value) ? value : 1));
}

function sanitizePageSize(value: number): number {
  const normalized = Math.trunc(
    Number.isFinite(value) ? value : PAGE_SIZE_OPTIONS[0]
  );
  if (
    PAGE_SIZE_OPTIONS.includes(
      normalized as (typeof PAGE_SIZE_OPTIONS)[number]
    )
  ) {
    return normalized;
  }

  return Math.min(100, Math.max(1, normalized || PAGE_SIZE_OPTIONS[0]));
}

function resolveFriendlyErrorMessage(error: unknown, fallback: string): string {
  const code =
    isHoursRequestApiError(error) && error.code ? error.code : null;
  if (code && BACKEND_ERROR_MESSAGE_MAP[code]) {
    return BACKEND_ERROR_MESSAGE_MAP[code];
  }

  return resolveApiErrorMessage(error, fallback);
}

function mergeIssueMaps(...maps: SubmitIssueMap[]): SubmitIssueMap {
  const merged = new Map<number, string[]>();

  for (const currentMap of maps) {
    Object.entries(currentMap).forEach(([activityId, messages]) => {
      const normalizedId = Number(activityId);
      const existing = merged.get(normalizedId) ?? [];
      merged.set(normalizedId, dedupeMessages([...existing, ...messages]));
    });
  }

  return Object.fromEntries(merged.entries());
}

export function useSelectHoursRequest() {
  const works = ref<ApprovedWorkRow[]>([]);
  const selectedWorkIdSet = ref<Set<number>>(new Set());

  const defaultHoursMode = ref<WorksFilterState["hoursMode"]>("all");
  const filter = ref<WorksFilterState>({
    academicYearId: null,
    hoursMode: "all",
    keyword: "",
  });

  const currentPageNumber = ref(1);
  const pageSize = ref<number>(PAGE_SIZE_OPTIONS[1]);
  const totalItemCount = ref(0);
  const totalApprovedCount = ref(0);
  const missingEvidenceOnly = ref(false);
  const missingEvidenceCount = ref(0);
  const missingEvidenceHoursTotal = ref(0);

  const drawerOpen = ref(false);
  const selectedActivityId = ref<number | null>(null);
  const workDetail = ref<WorkDetail | null>(null);

  const loadingList = ref(false);
  const errorList = ref<string | null>(null);

  const loadingDetail = ref(false);
  const errorDetail = ref<string | null>(null);

  const submitting = ref(false);
  const submitError = ref<string | null>(null);
  const submitServerIssuesByActivityId = ref<SubmitIssueMap>({});
  const { runWithFeedback } = useActionFeedback();
  const { runPageLoad } = usePageLoadFeedback();

  const evidenceFiles = ref<EvidenceFile[]>([]);
  const evidenceFileTypes = ref<EvidenceFileType[]>([]);
  const academicYearOptions = ref<AcademicYearOption[]>([]);
  const selectedEvidenceTypeId = ref<number | null>(null);
  const selectedEvidenceFile = ref<File | null>(null);
  const evidenceFileInputResetKey = ref(0);

  const loadingEvidence = ref(false);
  const loadingEvidenceTypes = ref(false);
  const evidenceError = ref<string | null>(null);
  const evidenceTypeError = ref<string | null>(null);
  const evidenceFileError = ref<string | null>(null);
  const uploadRequestError = ref<string | null>(null);
  const uploadingEvidence = ref(false);
  const deletingEvidenceId = ref<number | null>(null);
  const deleteEvidenceConfirmState = ref<DeleteEvidenceConfirmState>(null);
  const loadingAcademicYears = ref(false);

  const selectedIds = computed<number[]>(() => [...selectedWorkIdSet.value]);
  const selectedCount = computed(() => selectedIds.value.length);

  const selectedHoursTotal = computed(() => {
    const selected = selectedWorkIdSet.value;

    return works.value
      .filter((work) => selected.has(work.activityId))
      .reduce((sum, work) => sum + (work.effectiveHoursDisplay ?? 0), 0);
  });

  const contentApprovedWorks = computed(() => works.value);
  const filteredWorks = computed(() => works.value);
  const worksMissingEvidence = computed(() =>
    missingEvidenceOnly.value ? works.value : []
  );

  function buildSubmitIssuesForRow(row: ApprovedWorkRow): string[] {
    const issues: string[] = [];

    if (
      row.hoursRequestState !== "hours_not_submitted" &&
      row.hoursRequestState !== "hours_need_revision"
    ) {
      if (row.hoursRequestState === "hours_rejected") {
        issues.push(
          "Công trình này đã bị từ chối hẳn nên không thể chỉnh sửa và gửi lại."
        );
      } else {
        issues.push(
          "Chỉ công trình chưa gửi duyệt giờ hoặc được yêu cầu chỉnh sửa mới được gửi lại."
        );
      }
    }

    if (row.effectiveHoursDisplay === null) {
      issues.push(
        row.conversionRulePresent
          ? "Hệ thống chưa tính được giờ quy đổi tự động cho công trình này."
          : "Chưa có quy tắc quy đổi giờ cho công trình này."
      );
    }

    if (row.validEvidenceCount <= 0) {
      issues.push(
        "Cần tối thiểu một minh chứng PDF hợp lệ trước khi gửi duyệt giờ."
      );
    }

    return dedupeMessages(issues);
  }

  const workSubmitIssuesByActivityId = computed<SubmitIssueMap>(() => {
    return Object.fromEntries(
      works.value
        .map((row) => [row.activityId, buildSubmitIssuesForRow(row)] as const)
        .filter(([, issues]) => issues.length > 0)
    );
  });

  const submitItemErrorsByActivityId = computed<SubmitIssueMap>(() =>
    mergeIssueMaps(
      workSubmitIssuesByActivityId.value,
      submitServerIssuesByActivityId.value
    )
  );

  const selectableIds = computed<number[]>(() =>
    filteredWorks.value
      .filter((work) => isWorkEligibleForSubmit(work))
      .map((work) => work.activityId)
  );

  const isEvidenceEditingLocked = computed(
    () => workDetail.value?.hoursRequestState === "hours_approved"
  );

  const drawerActionsLocked = computed(
    () => uploadingEvidence.value || deletingEvidenceId.value !== null
  );

  const duplicateEvidenceWarning = computed<string | null>(() => {
    const file = selectedEvidenceFile.value;
    if (!file) {
      return null;
    }

    const duplicate = evidenceFiles.value.find(
      (existing) =>
        existing.originalName === file.name && existing.sizeBytes === file.size
    );

    if (!duplicate) {
      return null;
    }

    return `Tệp "${file.name}" có cùng tên và dung lượng với minh chứng đã có. Hệ thống vẫn sẽ kiểm tra trùng nội dung khi tải lên.`;
  });

  const canUploadEvidence = computed(() => {
    return (
      !!workDetail.value &&
      !loadingDetail.value &&
      !loadingEvidenceTypes.value &&
      evidenceFileTypes.value.length > 0 &&
      hasValidSelectedEvidenceType() &&
      hasValidSelectedEvidenceFile() &&
      !drawerActionsLocked.value &&
      !isEvidenceEditingLocked.value
    );
  });

  const deleteEvidenceConfirmOpen = computed(
    () => deleteEvidenceConfirmState.value !== null
  );
  const deleteEvidenceConfirmMessage = computed(
    () => deleteEvidenceConfirmState.value?.message ?? ""
  );

  function hasValidSelectedEvidenceType(): boolean {
    const selectedTypeId = selectedEvidenceTypeId.value;
    if (!Number.isInteger(selectedTypeId)) {
      return false;
    }

    return evidenceFileTypes.value.some((type) => type.id === selectedTypeId);
  }

  function hasValidSelectedEvidenceFile(): boolean {
    const file = selectedEvidenceFile.value;
    if (!file) {
      return false;
    }

    const extensionValid = file.name.toLowerCase().endsWith(PDF_FILE_EXTENSION);
    const mimeValid =
      !file.type || file.type.trim() === "" || file.type === PDF_MIME_TYPE;

    return (
      file.size > 0 &&
      file.size <= MAX_EVIDENCE_FILE_BYTES &&
      extensionValid &&
      mimeValid
    );
  }

  function clearSelectedEvidenceFile(resetInput = true): void {
    selectedEvidenceFile.value = null;
    if (resetInput) {
      evidenceFileInputResetKey.value += 1;
    }
  }

  function clearEvidenceFormErrors(): void {
    evidenceTypeError.value = null;
    evidenceFileError.value = null;
    uploadRequestError.value = null;
  }

  function clearSubmitServerIssues(): void {
    submitServerIssuesByActivityId.value = {};
  }

  function buildDefaultAcademicYearId(): number | null {
    const currentAcademicYear =
      academicYearOptions.value.find((item) => item.isActive) ??
      academicYearOptions.value.find((item) => item.isCurrent) ??
      academicYearOptions.value[0];

    return currentAcademicYear?.id ?? null;
  }

  function isAcademicYearOptionValid(id: number | null): boolean {
    if (id == null) {
      return true;
    }

    return academicYearOptions.value.some((option) => option.id === id);
  }

  function sanitizeAcademicYearId(value: number | null): number | null {
    if (value == null) {
      return null;
    }

    if (Number.isInteger(value) && isAcademicYearOptionValid(value)) {
      return value;
    }

    return buildDefaultAcademicYearId();
  }

  function sanitizeFilterState(
    nextFilter: Partial<WorksFilterState>
  ): WorksFilterState {
    const fallbackHoursMode = sanitizeHoursMode(
      filter.value.hoursMode,
      defaultHoursMode.value
    );

    const rawAcademicYearId =
      nextFilter.academicYearId !== undefined
        ? nextFilter.academicYearId
        : filter.value.academicYearId;

    const rawKeyword =
      nextFilter.keyword !== undefined ? nextFilter.keyword : filter.value.keyword;

    const rawHoursMode =
      nextFilter.hoursMode !== undefined
        ? nextFilter.hoursMode
        : filter.value.hoursMode;

    return {
      academicYearId: sanitizeAcademicYearId(rawAcademicYearId),
      hoursMode: sanitizeHoursMode(rawHoursMode, fallbackHoursMode),
      keyword: normalizeKeyword(rawKeyword),
    };
  }

  function buildListQueryParams() {
    const normalizedFilter = sanitizeFilterState(filter.value);
    filter.value = normalizedFilter;
    currentPageNumber.value = sanitizePageNumber(currentPageNumber.value);
    pageSize.value = sanitizePageSize(pageSize.value);

    return {
      academic_year_id: normalizedFilter.academicYearId ?? undefined,
      include_all_years: normalizedFilter.academicYearId == null,
      status:
        normalizedFilter.hoursMode === "all"
          ? undefined
          : normalizedFilter.hoursMode,
      q: normalizedFilter.keyword || undefined,
      page: currentPageNumber.value,
      per_page: pageSize.value,
      missing_evidence_only: missingEvidenceOnly.value,
    };
  }

  function normalizeEvidenceTypeSelection(
    value: number | null
  ): number | null {
    if (value == null) {
      return null;
    }

    if (!Number.isInteger(value)) {
      return null;
    }

    return evidenceFileTypes.value.some((type) => type.id === value)
      ? value
      : null;
  }

  function validateEvidenceTypeSelection(options?: {
    requireSelection?: boolean;
  }): string | null {
    const requireSelection = options?.requireSelection ?? false;

    if (loadingEvidenceTypes.value) {
      return "Hệ thống đang tải loại minh chứng. Vui lòng chờ hoàn tất.";
    }

    if (evidenceFileTypes.value.length === 0) {
      return "Hiện chưa có loại minh chứng khả dụng để tải lên.";
    }

    if (selectedEvidenceTypeId.value == null) {
      return requireSelection ? "Vui lòng chọn loại minh chứng." : null;
    }

    if (!hasValidSelectedEvidenceType()) {
      return "Loại minh chứng không hợp lệ hoặc không còn khả dụng.";
    }

    return null;
  }

  function validateEvidenceFileSelection(
    fileList: FileList | null,
    options?: { requireSelection?: boolean }
  ): { file: File | null; error: string | null } {
    const requireSelection = options?.requireSelection ?? false;

    if (!fileList || fileList.length === 0) {
      return {
        file: null,
        error: requireSelection ? "Vui lòng chọn tệp minh chứng PDF." : null,
      };
    }

    if (fileList.length !== 1) {
      return {
        file: null,
        error: "Bạn chỉ được chọn đúng một tệp minh chứng cho mỗi lần tải lên.",
      };
    }

    const file = fileList.item(0);
    if (!file) {
      return {
        file: null,
        error: "Không đọc được tệp minh chứng đã chọn.",
      };
    }

    if (file.size <= 0) {
      return {
        file: null,
        error: "Tệp minh chứng đang chọn không hợp lệ hoặc không có dữ liệu.",
      };
    }

    if (file.size > MAX_EVIDENCE_FILE_BYTES) {
      return {
        file: null,
        error: "Dung lượng tệp minh chứng không được vượt quá 10MB.",
      };
    }

    if (!file.name.toLowerCase().endsWith(PDF_FILE_EXTENSION)) {
      return {
        file: null,
        error: "Minh chứng phải là tệp PDF (.pdf).",
      };
    }

    if (file.type && file.type !== PDF_MIME_TYPE) {
      return {
        file: null,
        error: "Minh chứng phải có định dạng MIME application/pdf.",
      };
    }

    return { file, error: null };
  }

  function validateEvidenceUploadFields(): boolean {
    evidenceTypeError.value = validateEvidenceTypeSelection({
      requireSelection: true,
    });
    evidenceFileError.value = hasValidSelectedEvidenceFile()
      ? null
      : selectedEvidenceFile.value
        ? "Tệp minh chứng hiện tại không còn hợp lệ. Vui lòng chọn lại tệp PDF đúng chuẩn."
        : "Vui lòng chọn tệp minh chứng PDF.";

    if (!selectedEvidenceFile.value) {
      clearSelectedEvidenceFile(true);
    }

    if (isEvidenceEditingLocked.value) {
      uploadRequestError.value =
        "Hồ sơ giờ đã được duyệt nên bạn không thể chỉnh sửa minh chứng.";
      return false;
    }

    if (!evidenceTypeError.value && !evidenceFileError.value) {
      uploadRequestError.value = null;
      return true;
    }

    uploadRequestError.value = null;
    return false;
  }

  function setSubmitServerIssuesFromError(error: unknown): void {
    if (!isHoursRequestApiError(error)) {
      return;
    }

    const message =
      BACKEND_ERROR_MESSAGE_MAP[error.code ?? ""] ??
      resolveApiErrorMessage(
        error,
        "Có công trình không còn đủ điều kiện gửi duyệt giờ."
      );

    if (error.invalidActivityIds.length > 0) {
      submitServerIssuesByActivityId.value = Object.fromEntries(
        error.invalidActivityIds.map((activityId) => [activityId, [message]])
      );
      return;
    }

    if (error.code === "WORK_NOT_FOUND") {
      submitServerIssuesByActivityId.value = Object.fromEntries(
        selectedIds.value.map((activityId) => [activityId, [message]])
      );
      return;
    }

    if (error.code === "HOURS_VALUE_REQUIRED" && error.invalidItems.length > 0) {
      const mapped = error.invalidItems
        .map((item) => {
          if (!item || typeof item !== "object") {
            return null;
          }

          const activityId = Number(
            (item as { activity_id?: unknown }).activity_id ?? 0
          );
          if (!Number.isInteger(activityId) || activityId <= 0) {
            return null;
          }

          return [
            activityId,
            [
              "Công trình này chưa có dữ liệu giờ quy đổi hợp lệ để gửi duyệt.",
            ],
          ] as [number, string[]];
        })
        .filter((entry): entry is [number, string[]] => entry !== null);

      submitServerIssuesByActivityId.value = Object.fromEntries(mapped);
    }
  }

  async function loadApprovedWorksInternal() {
    loadingList.value = true;
    errorList.value = null;

    try {
      const dtoList = await loadApprovedWorksDTO(buildListQueryParams());

      works.value = dtoList.items.map(approvedWorkRowFromDto);
      totalItemCount.value = dtoList.pagination.total;
      totalApprovedCount.value = dtoList.summary.approved_count ?? 0;
      missingEvidenceCount.value = dtoList.summary.missing_evidence_count ?? 0;
      missingEvidenceHoursTotal.value =
        dtoList.summary.missing_evidence_hours_total ?? 0;

      const selectable = new Set(
        works.value
          .filter((work) => isWorkEligibleForSubmit(work))
          .map((work) => work.activityId)
      );

      const nextSelected = new Set<number>();
      for (const activityId of selectedWorkIdSet.value) {
        if (selectable.has(activityId)) {
          nextSelected.add(activityId);
        }
      }
      selectedWorkIdSet.value = nextSelected;
    } catch (error) {
      errorList.value = resolveFriendlyErrorMessage(
        error,
        "Không thể tải danh sách công trình."
      );
      missingEvidenceCount.value = 0;
      missingEvidenceHoursTotal.value = 0;
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
    filter.value = sanitizeFilterState({ ...filter.value, ...partial });
    currentPageNumber.value = 1;

    const partialKeys = Object.keys(partial).filter(
      (key) => partial[key as keyof WorksFilterState] !== undefined
    );
    const keywordOnly = partialKeys.length === 1 && partialKeys[0] === "keyword";
    void loadApprovedWorks({ withFeedback: !keywordOnly });
  }

  function resetFilter() {
    filter.value = sanitizeFilterState({
      academicYearId: buildDefaultAcademicYearId(),
      hoursMode: defaultHoursMode.value,
      keyword: "",
    });
    currentPageNumber.value = 1;
    void loadApprovedWorks();
  }

  async function loadAcademicYears() {
    loadingAcademicYears.value = true;

    try {
      const rows = await loadAcademicYearsDTO();
      academicYearOptions.value = rows.map(academicYearOptionFromDto);
      filter.value = sanitizeFilterState(filter.value);
    } finally {
      loadingAcademicYears.value = false;
    }
  }

  function updateCurrentPageNumber(nextPage: number) {
    const normalizedPage = sanitizePageNumber(nextPage);
    if (normalizedPage === currentPageNumber.value) {
      return;
    }

    currentPageNumber.value = normalizedPage;
    void loadApprovedWorks();
  }

  function updatePageSize(nextPageSize: number) {
    const normalizedPageSize = sanitizePageSize(nextPageSize);
    if (normalizedPageSize === pageSize.value) {
      return;
    }

    pageSize.value = normalizedPageSize;
    currentPageNumber.value = 1;
    void loadApprovedWorks();
  }

  function toggleWorkSelection(payload: {
    activityId: number;
    nextChecked: boolean;
  }) {
    const row = works.value.find((work) => work.activityId === payload.activityId);
    if (!row || !isWorkEligibleForSubmit(row)) {
      return;
    }

    clearSubmitServerIssues();
    submitError.value = null;

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
    clearSubmitServerIssues();
    submitError.value = null;

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
    if (evidenceFileTypes.value.length > 0) {
      return;
    }

    loadingEvidenceTypes.value = true;
    evidenceError.value = null;

    try {
      const rows = await loadEvidenceFileTypesDTO();
      evidenceFileTypes.value = rows.map((item) => ({
        id: item.id,
        code: item.code,
        name: item.name,
      }));

      const normalizedTypeId = normalizeEvidenceTypeSelection(
        selectedEvidenceTypeId.value
      );
      if (normalizedTypeId !== selectedEvidenceTypeId.value) {
        selectedEvidenceTypeId.value = normalizedTypeId;
      }
    } catch (error) {
      evidenceError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể tải loại minh chứng."
      );
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
      evidenceError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể tải danh sách minh chứng."
      );
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
    clearEvidenceFormErrors();
    deleteEvidenceConfirmState.value = null;
    selectedEvidenceTypeId.value = null;
    clearSelectedEvidenceFile(true);
    loadingDetail.value = true;

    try {
      await loadDetail(activityId);
      await Promise.all([loadEvidenceTypesIfNeeded(), loadEvidence(activityId)]);
    } catch (error) {
      errorDetail.value = resolveFriendlyErrorMessage(
        error,
        "Không thể tải chi tiết công trình."
      );
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
    clearEvidenceFormErrors();
    deleteEvidenceConfirmState.value = null;
    selectedEvidenceTypeId.value = null;
    clearSelectedEvidenceFile(true);
  }

  function validateSelectedWorksForSubmit(): number[] {
    return selectedIds.value.filter((activityId) => {
      const row = works.value.find((item) => item.activityId === activityId);
      return !row || buildSubmitIssuesForRow(row).length > 0;
    });
  }

  async function submitRequest() {
    if (submitting.value) {
      return;
    }

    submitError.value = null;
    clearSubmitServerIssues();

    if (selectedWorkIdSet.value.size === 0) {
      submitError.value = "Bạn chưa chọn công trình nào.";
      return;
    }

    const invalidSelectedIds = validateSelectedWorksForSubmit();
    if (invalidSelectedIds.length > 0) {
      submitError.value =
        "Một số công trình đã chọn chưa đủ điều kiện gửi duyệt giờ. Vui lòng kiểm tra lỗi hiển thị tại từng dòng.";
      submitServerIssuesByActivityId.value = Object.fromEntries(
        invalidSelectedIds.map((activityId) => {
          const row = works.value.find((item) => item.activityId === activityId);
          return [
            activityId,
            row
              ? buildSubmitIssuesForRow(row)
              : ["Không thể gửi duyệt công trình này."],
          ];
        })
      );
      return;
    }

    submitting.value = true;

    try {
      await runWithFeedback(
        async () => {
          const activityIds = [...selectedWorkIdSet.value];
          await submitHoursApprovalRequestDTO({ activity_ids: activityIds });
          selectedWorkIdSet.value = new Set();
          clearSubmitServerIssues();
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
                "Không thể gửi duyệt giờ lên khoa. Vui lòng thử lại."
              ),
          },
        }
      );
    } catch (error) {
      setSubmitServerIssuesFromError(error);
      submitError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể gửi duyệt giờ lên khoa. Vui lòng thử lại."
      );
    } finally {
      submitting.value = false;
    }
  }

  function setSelectedEvidenceTypeId(value: number | null) {
    selectedEvidenceTypeId.value = normalizeEvidenceTypeSelection(value);
    evidenceTypeError.value =
      value == null
        ? null
        : selectedEvidenceTypeId.value === null
          ? "Loại minh chứng không hợp lệ hoặc không còn khả dụng."
          : null;
    uploadRequestError.value = null;
  }

  function setSelectedEvidenceFile(files: FileList | null) {
    evidenceFileError.value = null;
    uploadRequestError.value = null;

    const validation = validateEvidenceFileSelection(files);
    if (validation.error) {
      clearSelectedEvidenceFile(true);
      evidenceFileError.value = validation.error;
      return;
    }

    selectedEvidenceFile.value = validation.file;
  }

  async function uploadEvidence() {
    if (uploadingEvidence.value || deletingEvidenceId.value !== null) {
      return;
    }

    if (!workDetail.value) {
      uploadRequestError.value =
        "Chưa tải xong chi tiết công trình để cập nhật minh chứng.";
      return;
    }

    if (!validateEvidenceUploadFields()) {
      return;
    }

    uploadingEvidence.value = true;
    uploadRequestError.value = null;

    try {
      await runWithFeedback(
        async () => {
          await uploadHoursEvidenceDTO({
            activityId: workDetail.value!.activityId,
            fileTypeId: selectedEvidenceTypeId.value!,
            file: selectedEvidenceFile.value!,
          });
          selectedEvidenceTypeId.value = null;
          clearSelectedEvidenceFile(true);
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
                "Không thể tải lên minh chứng. Vui lòng thử lại."
              ),
          },
        }
      );
    } catch (error) {
      if (isHoursRequestApiError(error) && error.errors.file_type_id?.length) {
        evidenceTypeError.value = error.errors.file_type_id[0] ?? null;
      }

      if (isHoursRequestApiError(error) && error.errors.file?.length) {
        evidenceFileError.value = error.errors.file[0] ?? null;
        clearSelectedEvidenceFile(true);
      }

      uploadRequestError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể tải lên minh chứng. Vui lòng thử lại."
      );
    } finally {
      uploadingEvidence.value = false;
    }
  }

  function requestDeleteEvidence(evidenceId: number) {
    if (
      !workDetail.value ||
      drawerActionsLocked.value ||
      isEvidenceEditingLocked.value
    ) {
      return;
    }

    const targetEvidence = evidenceFiles.value.find((file) => file.id === evidenceId);
    if (!targetEvidence) {
      uploadRequestError.value =
        "Không tìm thấy minh chứng cần xóa. Vui lòng tải lại danh sách.";
      return;
    }

    const remainingValidEvidenceCount = evidenceFiles.value.filter(
      (file) => file.id !== evidenceId
    ).length;
    const warning =
      remainingValidEvidenceCount === 0
        ? " Sau khi xóa, công trình này sẽ không còn minh chứng hợp lệ và sẽ mất điều kiện gửi duyệt giờ."
        : "";

    deleteEvidenceConfirmState.value = {
      evidenceId,
      message: `Bạn có chắc muốn xóa minh chứng "${targetEvidence.originalName}"?${warning}`,
    };
    uploadRequestError.value = null;
  }

  function cancelDeleteEvidence() {
    if (deletingEvidenceId.value !== null) {
      return;
    }

    deleteEvidenceConfirmState.value = null;
  }

  async function confirmDeleteEvidence() {
    if (!workDetail.value || !deleteEvidenceConfirmState.value) {
      return;
    }

    if (deletingEvidenceId.value !== null || uploadingEvidence.value) {
      return;
    }

    const evidenceId = deleteEvidenceConfirmState.value.evidenceId;
    deletingEvidenceId.value = evidenceId;
    deleteEvidenceConfirmState.value = null;
    uploadRequestError.value = null;

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
                "Không thể xóa minh chứng. Vui lòng thử lại."
              ),
          },
        }
      );
    } catch (error) {
      uploadRequestError.value = resolveFriendlyErrorMessage(
        error,
        "Không thể xóa minh chứng. Vui lòng thử lại."
      );
    } finally {
      deletingEvidenceId.value = null;
    }
  }

  async function initialize(options?: {
    defaultHoursMode?: WorksFilterState["hoursMode"];
    missingEvidenceOnly?: boolean;
  }) {
    defaultHoursMode.value = sanitizeHoursMode(
      options?.defaultHoursMode,
      "all"
    );
    filter.value.hoursMode = defaultHoursMode.value;
    missingEvidenceOnly.value = options?.missingEvidenceOnly ?? false;

    await runPageLoad(
      async () => {
        await loadAcademicYears();
        filter.value = sanitizeFilterState(filter.value);
        await loadApprovedWorksInternal();
      },
      {
        loading: {
          title: "Đang khởi tạo kê khai giờ NCKH",
          message:
            "Hệ thống đang chuẩn bị danh sách công trình và năm học...",
        },
      }
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
    submitItemErrorsByActivityId,

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
    missingEvidenceCount,
    missingEvidenceHoursTotal,
    currentPageNumber,
    pageSize,
    totalItemCount,

    evidenceFiles,
    evidenceFileTypes,
    selectedEvidenceTypeId,
    selectedEvidenceFile,
    evidenceFileInputResetKey,
    loadingEvidence,
    loadingEvidenceTypes,
    evidenceError,
    evidenceTypeError,
    evidenceFileError,
    uploadRequestError,
    duplicateEvidenceWarning,
    uploadingEvidence,
    deletingEvidenceId,
    drawerActionsLocked,
    canUploadEvidence,
    deleteEvidenceConfirmOpen,
    deleteEvidenceConfirmMessage,

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
    requestDeleteEvidence,
    cancelDeleteEvidence,
    confirmDeleteEvidence,
  };
}
