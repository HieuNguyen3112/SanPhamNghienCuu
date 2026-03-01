export type AuditLogScope = "FACULTY" | "GLOBAL";

/** ===== Enums (shared) ===== */
export type AuditActionGroup =
  | "auth"
  | "lecturer"
  | "research"
  | "approval"
  | "config"
  | "security";

export type AuditSeverity = "normal" | "important" | "dangerous";
export type AuditResultStatus = "success" | "failure";

/** ===== DTO (snake_case) ===== */
export interface AuditActorDTO {
  user_id: number | null;
  name: string | null;
  email: string | null;
  backend_roles_snapshot: string[] | null;
}

export interface AuditTargetDTO {
  type: string | null;
  id: string | number | null;
  display: string | null;

  route_name?: string | null;
  route_params?: Record<string, string | number> | null;
}

export interface AuditChangeDTO {
  field: string;
  before: string | null;
  after: string | null;
}

export interface AuditResultDTO {
  status: AuditResultStatus;
  error_message: string | null;
}

export interface AuditRequestMetaDTO {
  method: string | null;
  path: string | null;
  http_status: number | null;
}

export interface AuditLogEntryDTO {
  id: number;
  occurred_at: string; // ISO datetime

  severity: AuditSeverity;
  action_group: AuditActionGroup;
  action_code: string;
  action_label: string;
  action_label_display?: string | null;

  actor: AuditActorDTO;
  actor_display_name?: string | null;
  target: AuditTargetDTO;
  object_display?: string | null;
  object_type?: string | null;
  result: AuditResultDTO;
  result_label?: string | null;

  faculty_id: number | null;
  faculty_name: string | null;

  ip: string | null;
  user_agent: string | null;
  device_label?: string | null;
  request: AuditRequestMetaDTO | null;

  changes: AuditChangeDTO[] | null;
  note: string | null;
}

export interface FacultyOptionDTO {
  id: number;
  name: string;
}

export interface ActorOptionDTO {
  user_id: number;
  name: string;
  email: string;
}

export interface AuditActionCodeOptionDTO {
  code: string;
  label: string;
  group: AuditActionGroup;
}

export interface AuditLogListResponseDTO {
  items: AuditLogEntryDTO[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface AuditLogMetaDTO {
  actors: ActorOptionDTO[];
  faculties: FacultyOptionDTO[];
  action_codes: AuditActionCodeOptionDTO[];
  faculty_id_scoped: number | null;
}

/** Query DTO (snake_case) để xuất cho API */
export interface AuditLogQueryDTO {
  scope: AuditLogScope;
  keyword?: string;
  actor_user_id?: number;
  action_group?: AuditActionGroup;
  action_code?: string;
  faculty_id?: number;
  date_from?: string; // YYYY-MM-DD
  date_to?: string; // YYYY-MM-DD
  severity?: AuditSeverity;
  result?: AuditResultStatus;
  page?: number;
  per_page?: number;
  sort?: string;
}

/** ===== UI Models (camelCase) ===== */
export interface AuditActor {
  userId: number | null;
  name: string | null;
  email: string | null;
  backendRolesSnapshot: string[] | null;
}

export interface AuditTarget {
  type: string | null;
  id: string | number | null;
  display: string | null;

  routeName?: string | null;
  routeParams?: Record<string, string | number> | null;
}

export interface AuditChange {
  field: string;
  before: string | null;
  after: string | null;
}

export interface AuditResult {
  status: AuditResultStatus;
  errorMessage: string | null;
}

export interface AuditRequestMeta {
  method: string | null;
  path: string | null;
  httpStatus: number | null;
}

export interface AuditLogEntry {
  id: number;
  occurredAt: string;

  severity: AuditSeverity;
  actionGroup: AuditActionGroup;
  actionCode: string;
  actionLabel: string;
  objectDisplay: string;
  objectType: string | null;

  actor: AuditActor;
  target: AuditTarget;
  result: AuditResult;

  facultyId: number | null;
  facultyName: string | null;

  ip: string | null;
  userAgent: string | null;
  request: AuditRequestMeta | null;

  changes: AuditChange[] | null;
  note: string | null;
}

export interface FacultyOption {
  id: number;
  name: string;
}

export interface ActorOption {
  userId: number;
  name: string;
  email: string;
}

export interface AuditActionCodeOption {
  code: string;
  label: string;
  group: AuditActionGroup;
}

/** Filters UI (camelCase) */
export interface AuditLogFilters {
  keyword: string;
  actorUserId: number | "ALL";
  actionGroup: AuditActionGroup | "ALL";
  actionCode: string | "ALL";
  facultyId: number | "ALL"; // chỉ dùng cho GLOBAL
  dateFrom: string | null; // YYYY-MM-DD
  dateTo: string | null; // YYYY-MM-DD
  severity: AuditSeverity | "ALL";
  result: AuditResultStatus | "ALL";
}

export const DEFAULT_AUDIT_LOG_FILTERS: AuditLogFilters = {
  keyword: "",
  actorUserId: "ALL",
  actionGroup: "ALL",
  actionCode: "ALL",
  facultyId: "ALL",
  dateFrom: null,
  dateTo: null,
  severity: "ALL",
  result: "ALL",
};

export const AUDIT_GROUP_LABELS: Record<AuditActionGroup, string> = {
  auth: "Tài khoản",
  lecturer: "Giảng viên",
  research: "Công trình",
  approval: "Duyệt",
  config: "Cấu hình",
  security: "Bảo mật / nhạy cảm",
};

export const GLOBAL_ALLOWED_GROUPS: readonly AuditActionGroup[] = [
  "auth",
  "lecturer",
  "research",
  "approval",
  "config",
  "security",
];

export const FACULTY_ALLOWED_GROUPS: readonly AuditActionGroup[] = [
  "auth",
  "lecturer",
  "research",
  "approval",
  "security",
];

/** ===== Helpers ===== */
export function isValidDateRange(
  dateFrom: string | null,
  dateTo: string | null
) {
  if (!dateFrom || !dateTo) return true;
  return new Date(dateFrom).getTime() <= new Date(dateTo).getTime();
}

export function formatDateTimeVi(iso: string) {
  const d = new Date(iso);
  const fmt = new Intl.DateTimeFormat("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
  return fmt.format(d);
}

export function shortUserAgent(ua: string | null, maxLen = 90) {
  if (!ua) return "-";
  return ua.length > maxLen ? ua.slice(0, maxLen) + "..." : ua;
}

/** ===== Mappers (DTO -> UI) ===== */
export function auditActorFromDto(dto: AuditActorDTO): AuditActor {
  return {
    userId: dto.user_id,
    name: dto.name,
    email: dto.email,
    backendRolesSnapshot: dto.backend_roles_snapshot,
  };
}

export function auditTargetFromDto(dto: AuditTargetDTO): AuditTarget {
  return {
    type: dto.type,
    id: dto.id,
    display: dto.display,
    routeName: dto.route_name ?? null,
    routeParams: dto.route_params ?? null,
  };
}

export function auditChangeFromDto(dto: AuditChangeDTO): AuditChange {
  return { field: dto.field, before: dto.before, after: dto.after };
}

export function auditResultFromDto(dto: AuditResultDTO): AuditResult {
  return { status: dto.status, errorMessage: dto.error_message };
}

export function auditRequestFromDto(
  dto: AuditRequestMetaDTO
): AuditRequestMeta {
  return { method: dto.method, path: dto.path, httpStatus: dto.http_status };
}

function normalizeChangeValue(value: unknown): string | null {
  if (value === null || value === undefined) return null;
  if (typeof value === "string") return value;
  if (typeof value === "number" || typeof value === "boolean") return String(value);
  try {
    return JSON.stringify(value);
  } catch {
    return String(value);
  }
}

function coerceAuditChange(item: unknown): AuditChange | null {
  if (!item || typeof item !== "object") return null;
  const obj = item as Record<string, unknown>;
  const field = obj.field;
  if (typeof field !== "string" || !field.trim()) return null;
  return {
    field,
    before: normalizeChangeValue(obj.before ?? obj.old ?? obj.from ?? null),
    after: normalizeChangeValue(obj.after ?? obj.new ?? obj.to ?? null),
  };
}

export function normalizeAuditChanges(raw: unknown): AuditChange[] {
  if (!raw) return [];

  if (Array.isArray(raw)) {
    return raw.map(coerceAuditChange).filter(Boolean) as AuditChange[];
  }

  if (typeof raw === "string") {
    try {
      return normalizeAuditChanges(JSON.parse(raw));
    } catch {
      return [];
    }
  }

  if (typeof raw === "object") {
    const obj = raw as Record<string, unknown>;
    if ("field" in obj) {
      const single = coerceAuditChange(obj);
      return single ? [single] : [];
    }

    return Object.entries(obj).map(([field, value]) => {
      if (value && typeof value === "object" && !Array.isArray(value)) {
        const inner = value as Record<string, unknown>;
        return {
          field,
          before: normalizeChangeValue(inner.before ?? inner.old ?? inner.from ?? null),
          after: normalizeChangeValue(inner.after ?? inner.new ?? inner.to ?? null),
        };
      }

      return {
        field,
        before: null,
        after: normalizeChangeValue(value),
      };
    });
  }

  return [];
}

export function auditLogEntryFromDto(dto: AuditLogEntryDTO): AuditLogEntry {
  const changes = normalizeAuditChanges(dto.changes as unknown);

  return {
    id: dto.id,
    occurredAt: dto.occurred_at,

    severity: dto.severity,
    actionGroup: dto.action_group,
    actionCode: dto.action_code,
    actionLabel: dto.action_label,
    objectDisplay: dto.object_display ?? dto.target.display ?? "-",
    objectType: dto.object_type ?? null,

    actor: auditActorFromDto(dto.actor),
    target: auditTargetFromDto(dto.target),
    result: auditResultFromDto(dto.result),

    facultyId: dto.faculty_id,
    facultyName: dto.faculty_name,

    ip: dto.ip,
    userAgent: dto.user_agent,
    request: dto.request ? auditRequestFromDto(dto.request) : null,

    changes: changes.length ? changes : null,
    note: dto.note,
  };
}

export function facultyOptionFromDto(dto: FacultyOptionDTO): FacultyOption {
  return { id: dto.id, name: dto.name };
}

export function actorOptionFromDto(dto: ActorOptionDTO): ActorOption {
  return { userId: dto.user_id, name: dto.name, email: dto.email };
}

export function actionCodeOptionFromDto(
  dto: AuditActionCodeOptionDTO
): AuditActionCodeOption {
  return { code: dto.code, label: dto.label, group: dto.group };
}

/** Mapper filters UI -> query DTO (snake_case) */
export function auditLogQueryDtoFromFilters(args: {
  scope: AuditLogScope;
  filters: AuditLogFilters;
  facultyIdScoped: number | null;
  page: number;
  perPage: number;
}): AuditLogQueryDTO {
  const { scope, filters, facultyIdScoped, page, perPage } = args;

  const q: AuditLogQueryDTO = {
    scope,
    page,
    per_page: perPage,
  };

  if (filters.keyword.trim()) q.keyword = filters.keyword.trim();
  if (filters.actorUserId !== "ALL") q.actor_user_id = filters.actorUserId;
  if (filters.actionGroup !== "ALL") q.action_group = filters.actionGroup;
  if (filters.actionCode !== "ALL") q.action_code = filters.actionCode;
  if (filters.severity !== "ALL") q.severity = filters.severity;
  if (filters.result !== "ALL") q.result = filters.result;
  if (filters.dateFrom) q.date_from = filters.dateFrom;
  if (filters.dateTo) q.date_to = filters.dateTo;

  if (scope === "FACULTY") {
    // BCN không được chọn khoa khác; inject bằng scope
    if (facultyIdScoped != null) q.faculty_id = facultyIdScoped;
  } else {
    // GLOBAL chọn khoa nếu có
    if (filters.facultyId !== "ALL") q.faculty_id = filters.facultyId;
  }

  return q;
}
