export type HoursAlertLevelDTO = "danger" | "warning" | "info";

export type HoursAlertsFilterStatus = "all" | "danger" | "warning" | "done";

/**
 * DTO (snake_case) - backend computed (P0)
 * Sources:
 * - lecturer_yearly_hours.hours_total
 * - workload_quotas.required_hours
 * - academic_years.code
 * - academic_years.end_date (proxy deadline) OR academic_years.hours_declaration_deadline (future)
 */
export interface HoursAlertsSummaryDTO {
  academic_year_code: string;
  hours_total: number;
  required_hours: number;

  // P0: deadline field missing in schema -> derived/proxy
  deadline_date: string; // DATE ISO (YYYY-MM-DD)
  days_remaining: number; // derived (can be negative)
}

export interface HoursAlertItemDTO {
  id: number; // derived
  level: HoursAlertLevelDTO;

  title: string; // derived
  description: string; // derived

  // optional time hints
  updated_at: string | null; // DATETIME ISO (derived from e.g. lecturer_yearly_hours.updated_at)
  deadline_date: string | null; // DATE ISO

  // CTA
  cta_label: string | null; // derived
  cta_to: string | null; // route path (derived)

  // P0: needs persistence table -> mock only
  is_seen: boolean;
}

export interface HoursAlertActionSuggestionDTO {
  id: number; // derived
  title: string;
  description: string;
  cta_label: string | null;
  cta_to: string | null;
}

/* =========================
 * UI Models (camelCase)
 * ========================= */
export type HoursAlertLevel = "danger" | "warning" | "info";
export type HoursAlertsFilter = HoursAlertsFilterStatus;

export interface HoursAlertsSummary {
  academicYearCode: string;
  hoursTotal: number;
  requiredHours: number;
  missingHours: number;

  deadlineDate: string;
  daysRemaining: number;
}

export interface HoursAlertItem {
  id: number;
  level: HoursAlertLevel;
  title: string;
  description: string;

  updatedAt: string | null;
  deadlineDate: string | null;

  ctaLabel: string | null;
  ctaTo: string | null;

  isSeen: boolean;
}

export interface HoursAlertActionSuggestion {
  id: number;
  title: string;
  description: string;
  ctaLabel: string | null;
  ctaTo: string | null;
}

/* =========================
 * Mappers
 * ========================= */
export function hoursAlertsSummaryFromDto(
  dto: HoursAlertsSummaryDTO
): HoursAlertsSummary {
  const missing = Math.max(0, dto.required_hours - dto.hours_total);

  return {
    academicYearCode: dto.academic_year_code,
    hoursTotal: dto.hours_total,
    requiredHours: dto.required_hours,
    missingHours: missing,

    deadlineDate: dto.deadline_date,
    daysRemaining: dto.days_remaining,
  };
}

export function hoursAlertItemFromDto(dto: HoursAlertItemDTO): HoursAlertItem {
  return {
    id: dto.id,
    level: dto.level,
    title: dto.title,
    description: dto.description,
    updatedAt: dto.updated_at,
    deadlineDate: dto.deadline_date,
    ctaLabel: dto.cta_label,
    ctaTo: dto.cta_to,
    isSeen: dto.is_seen,
  };
}

export function hoursAlertActionSuggestionFromDto(
  dto: HoursAlertActionSuggestionDTO
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
  // Expecting YYYY-MM-DD or YYYY-MM-DDTHH:mm:ssZ
  const date = new Date(dateIso);
  if (Number.isNaN(date.getTime())) return dateIso;

  const dd = String(date.getDate()).padStart(2, "0");
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yyyy = String(date.getFullYear());
  return `${dd}/${mm}/${yyyy}`;
}

export function formatRelativeDaysFromNow(days: number): string {
  if (!Number.isFinite(days)) return "";
  if (days === 0) return "Hôm nay";
  if (days > 0) return `Còn ${days} ngày`;
  return `Quá hạn ${Math.abs(days)} ngày`;
}
