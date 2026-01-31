import { computed, onMounted, ref, watch } from "vue";
import type {
  ActorOption,
  AuditActionCodeOption,
  AuditActionGroup,
  AuditLogEntry,
  AuditLogFilters,
  AuditLogScope,
  FacultyOption,
} from "../contracts/audit-log.contract";
import {
  DEFAULT_AUDIT_LOG_FILTERS,
  FACULTY_ALLOWED_GROUPS,
  GLOBAL_ALLOWED_GROUPS,
  actionCodeOptionFromDto,
  actorOptionFromDto,
  auditLogEntryFromDto,
  auditLogQueryDtoFromFilters,
  facultyOptionFromDto,
  isValidDateRange,
} from "../contracts/audit-log.contract";
import {
  fetchAuditLogDetail,
  fetchAuditLogEntries,
  fetchAuditLogMeta,
} from "../services/auditLogService";

export function useAuditLog(scope: AuditLogScope) {
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  const detailLoading = ref(false);
  const detailError = ref<string | null>(null);

  const facultyIdScoped = ref<number | null>(null);

  const actors = ref<ActorOption[]>([]);
  const faculties = ref<FacultyOption[]>([]);
  const actionCodes = ref<AuditActionCodeOption[]>([]);

  const entries = ref<AuditLogEntry[]>([]);
  const totalItems = ref(0);
  const totalPages = ref(1);

  const draftFilters = ref<AuditLogFilters>({ ...DEFAULT_AUDIT_LOG_FILTERS });
  const appliedFilters = ref<AuditLogFilters>({ ...DEFAULT_AUDIT_LOG_FILTERS });

  const page = ref(1);
  const pageSize = ref(12);

  const selectedEntry = ref<AuditLogEntry | null>(null);

  const allowedGroups = computed<readonly AuditActionGroup[]>(() =>
    scope === "FACULTY" ? FACULTY_ALLOWED_GROUPS : GLOBAL_ALLOWED_GROUPS
  );

  const dateRangeInvalid = computed(() =>
    !isValidDateRange(draftFilters.value.dateFrom, draftFilters.value.dateTo)
  );

  const actionCodeOptions = computed<AuditActionCodeOption[]>(() => {
    const group = draftFilters.value.actionGroup;
    if (group === "ALL") {
      return actionCodes.value.filter((c) => allowedGroups.value.includes(c.group));
    }
    return actionCodes.value.filter((c) => c.group === group);
  });

  const pagedEntries = computed(() => entries.value);

  async function loadEntries() {
    isLoading.value = true;
    error.value = null;

    try {
      const queryDto = auditLogQueryDtoFromFilters({
        scope,
        filters: appliedFilters.value,
        facultyIdScoped: facultyIdScoped.value,
        page: page.value,
        perPage: pageSize.value,
      });

      queryDto.sort = "occurred_at:desc";

      const res = await fetchAuditLogEntries(queryDto);
      entries.value = res.items.map(auditLogEntryFromDto);
      totalItems.value = res.pagination.total;
      totalPages.value = res.pagination.last_page;
    } catch (e) {
      console.error(e);
      error.value = "Không thể tải nhật ký hệ thống. Vui lòng thử lại.";
    } finally {
      isLoading.value = false;
    }
  }

  async function bootstrap() {
    isLoading.value = true;
    error.value = null;

    try {
      const meta = await fetchAuditLogMeta(scope);
      actors.value = meta.actors.map(actorOptionFromDto);
      faculties.value = meta.faculties.map(facultyOptionFromDto);
      actionCodes.value = meta.action_codes.map(actionCodeOptionFromDto);
      facultyIdScoped.value = meta.faculty_id_scoped;

      await loadEntries();
    } catch (e) {
      console.error(e);
      error.value =
        "Không thể tải dữ liệu nhật ký hệ thống. Vui lòng thử lại.";
    } finally {
      isLoading.value = false;
    }
  }

  function applyFilters() {
    if (!isValidDateRange(draftFilters.value.dateFrom, draftFilters.value.dateTo)) {
      error.value =
        "Khoảng ngày không hợp lệ: Từ ngày phải nhỏ hơn hoặc bằng Đến ngày.";
      return;
    }

    appliedFilters.value = { ...draftFilters.value };
    if (scope === "FACULTY") {
      appliedFilters.value.facultyId = "ALL";
    }

    page.value = 1;
    void loadEntries();
  }

  function resetFilters() {
    draftFilters.value = { ...DEFAULT_AUDIT_LOG_FILTERS };
    appliedFilters.value = { ...DEFAULT_AUDIT_LOG_FILTERS };

    if (scope === "FACULTY") {
      appliedFilters.value.facultyId = "ALL";
    }

    page.value = 1;
    void loadEntries();
  }

  async function openDetail(entry: AuditLogEntry) {
    selectedEntry.value = entry;
    detailLoading.value = true;
    detailError.value = null;

    try {
      const detailDto = await fetchAuditLogDetail(scope, entry.id);
      selectedEntry.value = auditLogEntryFromDto(detailDto);
    } catch (e) {
      console.error(e);
      detailError.value = "Không thể tải chi tiết nhật ký. Vui lòng thử lại.";
    } finally {
      detailLoading.value = false;
    }
  }

  function closeDetail() {
    selectedEntry.value = null;
    detailError.value = null;
  }

  function setPage(next: number) {
    page.value = Math.max(1, Math.min(totalPages.value, next));
    void loadEntries();
  }

  function setPageSize(next: number) {
    pageSize.value = next;
    page.value = 1;
    void loadEntries();
  }

  watch(
    () => draftFilters.value.actionGroup,
    () => {
      if (
        draftFilters.value.actionCode !== "ALL" &&
        !actionCodeOptions.value.some(
          (c) => c.code === draftFilters.value.actionCode
        )
      ) {
        draftFilters.value.actionCode = "ALL";
      }
    }
  );

  onMounted(() => {
    void bootstrap();
  });

  return {
    scope,

    isLoading,
    error,

    detailLoading,
    detailError,

    actors,
    faculties,
    actionCodeOptions,

    facultyIdScoped,

    draftFilters,
    appliedFilters,

    dateRangeInvalid,

    page,
    pageSize,
    totalItems,
    totalPages,
    pagedEntries,

    selectedEntry,

    applyFilters,
    resetFilters,
    openDetail,
    closeDetail,

    setPage,
    setPageSize,
  };
}
