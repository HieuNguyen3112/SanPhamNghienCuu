export type HoursWarningSeverityDTO = "danger" | "warning" | "info";
export type HoursWarningsTabDTO = "all" | "danger" | "warning" | "done";
export type HoursWarningStatusDTO = "unseen" | "seen" | "resolved";

export interface HoursWarningsSummaryDTO {
  academic_year_id: number;
  academic_year_code: string;
  required_hours: number;
  approved_hours: number;
  pending_hours: number;
  rejected_hours: number;
  total_hours_current: number;
  shortage_hours: number;
  deadline_date: string | null;
  days_remaining: number | null;
}

export interface HoursWarningItemDTO {
  id: string;
  type_key: string;
  severity_key: HoursWarningSeverityDTO;
  title: string;
  message: string;
  status_key: HoursWarningStatusDTO;
  updated_at: string | null;
  deadline_at: string | null;
  action?: {
    label?: string | null;
    route_path?: string | null;
    external_url?: string | null;
  };
}

export interface HoursWarningSuggestionDTO {
  id: string;
  title: string;
  description: string;
  cta_label: string | null;
  cta_to: string | null;
}

export interface HoursWarningsResponseDTO {
  summary: HoursWarningsSummaryDTO;
  tab_counts: { all: number; danger: number; warning: number; done: number };
  items: HoursWarningItemDTO[];
  pagination: { page: number; per_page: number; total: number; last_page: number };
  suggestions: HoursWarningSuggestionDTO[];
}

export interface HoursWarningSeenResponseDTO {
  id: string;
  status_key: HoursWarningStatusDTO;
  seen_at: string | null;
}

/* =========================
 * UI Models (camelCase)
 * ========================= */
export type HoursAlertLevel = HoursWarningSeverityDTO;
export type HoursAlertsFilter = HoursWarningsTabDTO;

export interface HoursAlertsSummary {
  academicYearCode: string;
  requiredHours: number;
  approvedHours: number;
  pendingHours: number;
  rejectedHours: number;
  totalHoursCurrent: number;
  shortageHours: number;
  deadlineDate: string | null;
  daysRemaining: number | null;
}

export interface HoursAlertItem {
  id: string;
  level: HoursAlertLevel;
  title: string;
  description: string;
  statusKey: HoursWarningStatusDTO;
  statusLabel: string;
  updatedAt: string | null;
  deadlineDate: string | null;
  ctaLabel: string | null;
  ctaTo: string | null;
  ctaExternal: boolean;
  isSeen: boolean;
}

export interface HoursAlertActionSuggestion {
  id: string;
  title: string;
  description: string;
  ctaLabel: string | null;
  ctaTo: string | null;
}

/* =========================
 * Mappers
 * ========================= */
export function hoursAlertsSummaryFromDto(
  dto: HoursWarningsSummaryDTO
): HoursAlertsSummary {
  return {
    academicYearCode: dto.academic_year_code,
    requiredHours: dto.required_hours,
    approvedHours: dto.approved_hours,
    pendingHours: dto.pending_hours,
    rejectedHours: dto.rejected_hours,
    totalHoursCurrent: dto.total_hours_current,
    shortageHours: dto.shortage_hours,
    deadlineDate: dto.deadline_date,
    daysRemaining: dto.days_remaining,
  };
}

function statusLabel(status: HoursWarningStatusDTO): string {
  if (status === "resolved") return "Đã xử lý";
  if (status === "seen") return "Đã xem";
  return "Chưa xử lý";
}

export function hoursAlertItemFromDto(dto: HoursWarningItemDTO): HoursAlertItem {
  const action = dto.action ?? {};
  const ctaTo = action.route_path ?? action.external_url ?? null;
  const isExternal = Boolean(action.external_url);
  return {
    id: dto.id,
    level: dto.severity_key,
    title: dto.title,
    description: dto.message,
    statusKey: dto.status_key,
    statusLabel: statusLabel(dto.status_key),
    updatedAt: dto.updated_at,
    deadlineDate: dto.deadline_at,
    ctaLabel: action.label ?? null,
    ctaTo,
    ctaExternal: isExternal,
    isSeen: dto.status_key !== "unseen",
  };
}

export function hoursAlertActionSuggestionFromDto(
  dto: HoursWarningSuggestionDTO
): HoursAlertActionSuggestion {
  return {
    id: dto.id,
    title: dto.title,
    description: dto.description,
    ctaLabel: dto.cta_label,
    ctaTo: dto.cta_to,
  };
}

/* =========================
 * Format helpers
 * ========================= */
export function formatHours(value: number): string {
  if (!Number.isFinite(value)) return "0";
  return value.toFixed(0);
}

export function formatDateVietnamese(dateIso: string): string {
  const date = new Date(dateIso);
  if (Number.isNaN(date.getTime())) return dateIso;

  const dd = String(date.getDate()).padStart(2, "0");
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yyyy = String(date.getFullYear());
  return `${dd}/${mm}/${yyyy}`;
}

export function formatRelativeDaysFromNow(days: number | null): string {
  if (days === null || !Number.isFinite(days)) return "";
  if (days === 0) return "Hôm nay";
  if (days > 0) return `Còn ${days} ngày`;
  return `Quá hạn ${Math.abs(days)} ngày`;
}
