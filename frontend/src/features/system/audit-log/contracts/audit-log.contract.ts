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

/** ===== DTO (snake_case) =====
 * NOTE: Backend schema hiện chưa có audit_logs.
 * Đây là DTO đề xuất để BE trả về (hoặc dùng cho mock).
 */
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

  actor: AuditActorDTO;
  target: AuditTargetDTO;
  result: AuditResultDTO;

  faculty_id: number | null;
  faculty_name: string | null;

  ip: string | null;
  user_agent: string | null;
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

/** Query DTO (snake_case) – đề xuất cho API */
export interface AuditLogQueryDTO {
  scope: AuditLogScope;
  keyword?: string;
  actor_user_id?: number;
  action_group?: AuditActionGroup;
  faculty_id?: number;
  date_from?: string; // YYYY-MM-DD
  date_to?: string; // YYYY-MM-DD
  severity?: AuditSeverity;
  page?: number;
  per_page?: number;
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

/** Filters UI (camelCase) */
export interface AuditLogFilters {
  keyword: string;
  actorUserId: number | "ALL";
  actionGroup: AuditActionGroup | "ALL";
  facultyId: number | "ALL"; // chỉ dùng cho GLOBAL
  dateFrom: string | null; // YYYY-MM-DD
  dateTo: string | null; // YYYY-MM-DD
  severity: AuditSeverity | "ALL";
}

export const DEFAULT_AUDIT_LOG_FILTERS: AuditLogFilters = {
  keyword: "",
  actorUserId: "ALL",
  actionGroup: "ALL",
  facultyId: "ALL",
  dateFrom: null,
  dateTo: null,
  severity: "ALL",
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
  return ua.length > maxLen ? ua.slice(0, maxLen) + "…" : ua;
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

export function auditLogEntryFromDto(dto: AuditLogEntryDTO): AuditLogEntry {
  return {
    id: dto.id,
    occurredAt: dto.occurred_at,

    severity: dto.severity,
    actionGroup: dto.action_group,
    actionCode: dto.action_code,
    actionLabel: dto.action_label,

    actor: auditActorFromDto(dto.actor),
    target: auditTargetFromDto(dto.target),
    result: auditResultFromDto(dto.result),

    facultyId: dto.faculty_id,
    facultyName: dto.faculty_name,

    ip: dto.ip,
    userAgent: dto.user_agent,
    request: dto.request ? auditRequestFromDto(dto.request) : null,

    changes: dto.changes ? dto.changes.map(auditChangeFromDto) : null,
    note: dto.note,
  };
}

export function facultyOptionFromDto(dto: FacultyOptionDTO): FacultyOption {
  return { id: dto.id, name: dto.name };
}

export function actorOptionFromDto(dto: ActorOptionDTO): ActorOption {
  return { userId: dto.user_id, name: dto.name, email: dto.email };
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
  if (filters.severity !== "ALL") q.severity = filters.severity;
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
