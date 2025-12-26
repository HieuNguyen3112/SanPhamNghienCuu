import { computed, onMounted, ref } from "vue";
import type {
  ActorOption,
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
  auditLogEntryFromDto,
  actorOptionFromDto,
  facultyOptionFromDto,
  auditLogQueryDtoFromFilters,
  isValidDateRange,
} from "../contracts/audit-log.contract";
import {
  fetchAuditActors,
  fetchAuditLogEntries,
  fetchFaculties,
  resolveMyFacultyId,
} from "../services/auditLogService";

function normalizeText(v: string) {
  return v.trim().toLowerCase();
}

function includesKeyword(haystack: string, needle: string) {
  if (!needle) return true;
  return normalizeText(haystack).includes(needle);
}

function dateOnly(iso: string) {
  const d = new Date(iso);
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
}

export function useAuditLog(scope: AuditLogScope) {
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  const facultyIdScoped = ref<number | null>(null);

  const actors = ref<ActorOption[]>([]);
  const faculties = ref<FacultyOption[]>([]);

  const allEntries = ref<AuditLogEntry[]>([]);

  // draft vs applied filters
  const draftFilters = ref<AuditLogFilters>({ ...DEFAULT_AUDIT_LOG_FILTERS });
  const appliedFilters = ref<AuditLogFilters>({ ...DEFAULT_AUDIT_LOG_FILTERS });

  const page = ref(1);
  const pageSize = ref(12);

  const selectedEntry = ref<AuditLogEntry | null>(null);

  const allowedGroups = computed<readonly AuditActionGroup[]>(() =>
    scope === "FACULTY" ? FACULTY_ALLOWED_GROUPS : GLOBAL_ALLOWED_GROUPS
  );

  const dateRangeInvalid = computed(
    () =>
      !isValidDateRange(
        appliedFilters.value.dateFrom,
        appliedFilters.value.dateTo
      )
  );

  const visibleEntries = computed(() => {
    const f = appliedFilters.value;

    // invalid range => show empty (UI will show empty)
    if (!isValidDateRange(f.dateFrom, f.dateTo)) return [];

    const keyword = normalizeText(f.keyword);

    return allEntries.value.filter((e) => {
      // group allow list
      if (!allowedGroups.value.includes(e.actionGroup)) return false;

      // FACULTY enforcement
      if (scope === "FACULTY") {
        if (facultyIdScoped.value == null) return false;
        if (e.facultyId !== facultyIdScoped.value) return false;
      }

      // GLOBAL faculty filter
      if (scope === "GLOBAL" && f.facultyId !== "ALL") {
        if (e.facultyId !== f.facultyId) return false;
      }

      // actor
      if (f.actorUserId !== "ALL") {
        if (e.actor.userId !== f.actorUserId) return false;
      }

      // group filter
      if (f.actionGroup !== "ALL") {
        if (e.actionGroup !== f.actionGroup) return false;
      }

      // severity
      if (f.severity !== "ALL") {
        if (e.severity !== f.severity) return false;
      }

      // date range
      const d = dateOnly(e.occurredAt);
      if (f.dateFrom && d < f.dateFrom) return false;
      if (f.dateTo && d > f.dateTo) return false;

      // keyword across actor/action/target
      if (!keyword) return true;
      const actorText = `${e.actor.name ?? ""} ${e.actor.email ?? ""}`;
      const actionText = `${e.actionLabel} ${e.actionCode}`;
      const targetText = `${e.target.display ?? ""} ${
        e.target.type ?? ""
      } ${String(e.target.id ?? "")}`;

      return (
        includesKeyword(actorText, keyword) ||
        includesKeyword(actionText, keyword) ||
        includesKeyword(targetText, keyword)
      );
    });
  });

  const totalItems = computed(() => visibleEntries.value.length);
  const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalItems.value / pageSize.value))
  );

  const pagedEntries = computed(() => {
    const p = Math.max(1, Math.min(page.value, totalPages.value));
    const start = (p - 1) * pageSize.value;
    return visibleEntries.value.slice(start, start + pageSize.value);
  });

  function applyFilters() {
    appliedFilters.value = { ...draftFilters.value };

    // faculty scope doesn't use faculty filter
    if (scope === "FACULTY") {
      appliedFilters.value.facultyId = "ALL";
    }

    page.value = 1;
  }

  function resetFilters() {
    draftFilters.value = { ...DEFAULT_AUDIT_LOG_FILTERS };
    appliedFilters.value = { ...DEFAULT_AUDIT_LOG_FILTERS };

    if (scope === "FACULTY") {
      appliedFilters.value.facultyId = "ALL";
    }

    page.value = 1;
  }

  function openDetail(entry: AuditLogEntry) {
    selectedEntry.value = entry;
  }

  function closeDetail() {
    selectedEntry.value = null;
  }

  function setPage(next: number) {
    page.value = Math.max(1, Math.min(totalPages.value, next));
  }

  function setPageSize(next: number) {
    pageSize.value = next;
    page.value = 1;
  }

  async function bootstrap() {
    isLoading.value = true;
    error.value = null;

    try {
      if (scope === "FACULTY") {
        facultyIdScoped.value = await resolveMyFacultyId();
      }

      const [actorsDto, facultiesDto] = await Promise.all([
        fetchAuditActors(),
        fetchFaculties(),
      ]);

      actors.value = actorsDto.map(actorOptionFromDto);
      faculties.value = facultiesDto.map(facultyOptionFromDto);

      const queryDto = auditLogQueryDtoFromFilters({
        scope,
        filters: appliedFilters.value,
        facultyIdScoped: facultyIdScoped.value,
        page: page.value,
        perPage: pageSize.value,
      });

      const entriesDto = await fetchAuditLogEntries(queryDto);
      allEntries.value = entriesDto.map(auditLogEntryFromDto);

      // default show latest
      applyFilters();
    } catch (e) {
      error.value =
        e instanceof Error ? e.message : "Không thể tải nhật ký hệ thống.";
    } finally {
      isLoading.value = false;
    }
  }

  onMounted(() => {
    void bootstrap();
  });

  return {
    scope,

    isLoading,
    error,

    actors,
    faculties,

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
