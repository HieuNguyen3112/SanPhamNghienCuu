export type CatalogStatus = "active" | "inactive" | "locked";

/** ========= Schema DTOs (snake_case) ========= */
export interface AcademicYearDTO {
  id: number;
  code: string; // VARCHAR9
  start_date: string; // YYYY-MM-DD
  end_date: string; // YYYY-MM-DD
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface ActivityKindDTO {
  id: number;
  code: string; // paper|book|project|conference
  name: string;
  created_at: string;
  updated_at: string;
}

export interface ActivityTypeDTO {
  id: number;
  kind_id: number;
  code: string;
  name: string;
  created_at: string;
  updated_at: string;
}

export type DistributionStrategyDTO =
  | "equal_all_members"
  | "principal_fraction_others_equal"
  | "per_lecturer_fixed";

/** hour_rules table */
export interface HourRuleDTO {
  id: number;
  kind_id: number;
  type_id: number | null;
  distribution_strategy: DistributionStrategyDTO;
  hours_total_per_activity: string | null; // DECIMAL -> string from API
  hours_per_occurrence: string | null;
  principal_fraction: string | null;
  others_fraction_total: string | null;
  max_occurrences_per_year: number | null;
  effective_from: string; // YYYY-MM-DD
  effective_to: string | null; // YYYY-MM-DD
  is_active: boolean;
  version: number;
  created_at: string;
  updated_at: string;
}

/** workload_quotas table (schema thiếu dimension đối tượng) */
export interface WorkloadQuotaDTO {
  id: number;
  academic_year_id: number;
  required_hours: string; // DECIMAL -> string
  notes: string | null;
  created_at: string;
  updated_at: string;
}

/** ========= Derived DTOs (API computed) ========= */
export interface HourRuleDerivedDTO extends HourRuleDTO {
  // derived from calculation_logs count (not in schema)
  is_locked: boolean;
}

/** Tab 2 derived (schema missing) */
export interface WorkloadQuotaRuleDerivedDTO {
  id: number;
  academic_year_id: number;
  // proposed: academic_rank_id -> academic_ranks.id
  academic_rank_id: number;
  required_hours: string;
  notes: string | null;
  is_active: boolean;
  is_locked: boolean;
  created_at: string;
  updated_at: string;
}

/** Tab 3 derived “period” (schema missing) */
export interface AcademicYearPeriodDerivedDTO {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
  status: CatalogStatus; // active|inactive|locked
  is_locked: boolean;
}

/** ========= UI Models (camelCase) ========= */
export interface SelectOption<T extends string | number> {
  value: T;
  label: string;
  hint?: string;
}

export interface WorkConversionRow {
  id: number;

  academicYearId: number | null;
  academicYearCode: string; // derived from effective_from mapping to academic_years
  kindId: number;
  kindName: string;
  typeId: number | null;
  typeName: string;

  hours: number | null; // from hours_total_per_activity
  isActive: boolean;
  isLocked: boolean;

  effectiveFrom: string;
  effectiveTo: string | null;

  // UI-only (schema missing)
  notes: string;
}

export interface WorkConversionDraft {
  academicYearId: number | null;
  kindId: number;
  typeId: number | null;
  hours: number | null;
  isActive: boolean;
  notes: string;
}

export type WorkConversionErrors = Partial<
  Record<
    "academicYearId" | "kindId" | "typeId" | "hours" | "notes" | "isActive",
    string
  >
> &
  Record<string, string | undefined>;

export interface HoursQuotaRow {
  id: number;
  academicYearId: number;
  academicYearCode: string;
  academicRankId: number;
  academicRankName: string;
  requiredHours: number;
  notes: string;
  isActive: boolean;
  isLocked: boolean;
}

export interface HoursQuotaDraft {
  academicYearId: number | null;
  academicRankId: number | null;
  requiredHours: number | null;
  notes: string;
  isActive: boolean;
}

export type HoursQuotaErrors = Partial<
  Record<
    "academicYearId" | "academicRankId" | "requiredHours" | "notes",
    string
  >
> &
  Record<string, string | undefined>;

export interface AcademicYearPeriodRow {
  id: number;
  kind: "academic_year" | "period";
  name: string;
  startDate: string;
  endDate: string;
  status: CatalogStatus;
  isLocked: boolean;
}

/** ========= Helpers / Mapper ========= */
export function parseDecimalToNumber(v: string | null): number | null {
  if (v === null) return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

export function numberToDecimalString(v: number | null): string | null {
  if (v === null) return null;
  // keep 2 decimals
  return (Math.round(v * 100) / 100).toFixed(2);
}

export function formatDateRange(start: string, end: string | null): string {
  return end ? `${start} → ${end}` : start;
}

export function statusLabel(status: CatalogStatus): string {
  if (status === "active") return "Đang áp dụng";
  if (status === "locked") return "Đã khóa";
  return "Chưa áp dụng";
}

export function getErrorMessage(e: unknown, fallback: string): string {
  if (typeof e === "object" && e !== null) {
    const maybe = e as {
      response?: { data?: { message?: unknown } };
      message?: unknown;
    };
    const serverMsg = maybe.response?.data?.message;
    if (typeof serverMsg === "string" && serverMsg.trim()) return serverMsg;
    if (typeof maybe.message === "string" && maybe.message.trim())
      return maybe.message;
  }
  return fallback;
}

export function hasErrors<T extends object>(errors: T): boolean {
  return Object.values(errors as Record<string, unknown>).some((v) =>
    Boolean(v)
  );
}

/**
 * Map hour_rules -> “Năm học” bằng cách dò academic_years theo effective_from trong khoảng.
 * Assumption: effective_from luôn nằm trong khoảng start_date/end_date.
 */
export function deriveAcademicYearFromEffectiveFrom(
  effectiveFrom: string,
  years: AcademicYearDTO[]
): { academicYearId: number | null; academicYearCode: string } {
  const found = years.find(
    (y) => effectiveFrom >= y.start_date && effectiveFrom <= y.end_date
  );
  return found
    ? { academicYearId: found.id, academicYearCode: found.code }
    : { academicYearId: null, academicYearCode: "—" };
}

export function workConversionRowFromDto(
  dto: HourRuleDerivedDTO,
  years: AcademicYearDTO[],
  kinds: ActivityKindDTO[],
  types: ActivityTypeDTO[]
): WorkConversionRow {
  const year = deriveAcademicYearFromEffectiveFrom(dto.effective_from, years);

  const kind = kinds.find((k) => k.id === dto.kind_id);
  const type = dto.type_id ? types.find((t) => t.id === dto.type_id) : null;

  return {
    id: dto.id,
    academicYearId: year.academicYearId,
    academicYearCode: year.academicYearCode,
    kindId: dto.kind_id,
    kindName: kind?.name ?? `#${dto.kind_id}`,
    typeId: dto.type_id,
    typeName: type?.name ?? (dto.type_id ? `#${dto.type_id}` : "—"),
    hours: parseDecimalToNumber(dto.hours_total_per_activity),
    isActive: dto.is_active,
    isLocked: dto.is_locked,
    effectiveFrom: dto.effective_from,
    effectiveTo: dto.effective_to,

    // schema missing -> UI-only + TODO persist
    notes: "",
  };
}

export function validateWorkConversionDraft(
  d: WorkConversionDraft
): WorkConversionErrors {
  const e: WorkConversionErrors = {};
  if (!d.academicYearId) e.academicYearId = "Năm học là bắt buộc.";
  if (!d.kindId) e.kindId = "Loại công trình là bắt buộc.";
  // typeId can be null (schema allows type_id nullable) but spec wants select; keep optional
  if (d.hours === null || !Number.isFinite(d.hours) || d.hours <= 0)
    e.hours = "Số giờ phải > 0.";
  if (d.notes.length > 500)
    e.notes = "Ghi chú tối đa 500 ký tự. (Cần backend hỗ trợ lưu)";
  return e;
}

export function validateHoursQuotaDraft(d: HoursQuotaDraft): HoursQuotaErrors {
  const e: HoursQuotaErrors = {};
  if (!d.academicYearId) e.academicYearId = "Năm học là bắt buộc.";
  if (!d.academicRankId) e.academicRankId = "Đối tượng là bắt buộc.";
  if (
    d.requiredHours === null ||
    !Number.isFinite(d.requiredHours) ||
    d.requiredHours <= 0
  )
    e.requiredHours = "Định mức phải > 0.";
  if (d.notes.length > 255) e.notes = "Ghi chú tối đa 255 ký tự.";
  return e;
}

/** Payloads (chỉ dùng key schema/DTO hợp lệ) */
export interface UpsertHourRulePayloadDTO {
  // hour_rules columns only
  kind_id: number;
  type_id: number | null;
  distribution_strategy: DistributionStrategyDTO;
  hours_total_per_activity: string | null;
  effective_from: string;
  effective_to: string | null;
  is_active: boolean;
  version: number;

  /**
   * TODO(P1): hour_rules.notes (schema missing) - currently NOT sent.
   */
}

export function upsertHourRulePayloadFromDraft(
  draft: WorkConversionDraft,
  academicYears: AcademicYearDTO[]
): UpsertHourRulePayloadDTO {
  const year = academicYears.find((y) => y.id === draft.academicYearId) ?? null;

  return {
    kind_id: draft.kindId,
    type_id: draft.typeId,
    distribution_strategy: "equal_all_members",
    hours_total_per_activity: numberToDecimalString(draft.hours),
    effective_from:
      year?.start_date ?? draft.academicYearId?.toString() ?? "1970-01-01",
    effective_to: year?.end_date ?? null,
    is_active: draft.isActive,
    version: 1,
  };
}
