export type FacultyIdentifier = string;
export type AcademicYearIdentifier = string;

export type ResearchHourShortfallSeverity = "MILD" | "MODERATE" | "SEVERE";

export type ResearchHourShortfallSeverityFilterCondition =
  | "ALL"
  | "MILD"
  | "MODERATE"
  | "SEVERE";

export type WarningNotificationRequestStateFilterCondition =
  | "ALL"
  | "NOT_REQUESTED"
  | "REQUESTED";

/**
 * Lý do cảnh báo (manager chọn trước khi bấm "Cảnh báo").
 * BE có thể lưu reason_code + reason_note vào log/notifications.
 */
export type ResearchHourWarningReasonCode =
  | "MISSING_HOURS"
  | "DEADLINE_NEAR"
  | "DEADLINE_PASSED"
  | "MISSING_EVIDENCE"
  | "OTHER";

export interface ResearchHourWarningReasonOption {
  code: ResearchHourWarningReasonCode;
  label: string;
  description: string;
}

export const RESEARCH_HOUR_WARNING_REASON_OPTIONS: ResearchHourWarningReasonOption[] =
  [
    {
      code: "MISSING_HOURS",
      label: "Thiếu giờ NCKH",
      description: "Giảng viên chưa đạt định mức giờ NCKH theo năm học.",
    },
    {
      code: "DEADLINE_NEAR",
      label: "Sắp hết hạn kê khai",
      description: "Gần đến hạn chốt kê khai/xét duyệt giờ NCKH.",
    },
    {
      code: "DEADLINE_PASSED",
      label: "Đã hết hạn kê khai",
      description: "Đã quá hạn; cần xử lý theo quy định/biên bản.",
    },
    {
      code: "MISSING_EVIDENCE",
      label: "Thiếu minh chứng",
      description:
        "Cần bổ sung minh chứng hoặc hồ sơ liên quan để được ghi nhận.",
    },
    {
      code: "OTHER",
      label: "Lý do khác",
      description: "Nhập ghi chú cụ thể.",
    },
  ];

/* =========================
 * DTO (snake_case)
 * ========================= */

export interface FacultyOptionDTO {
  faculty_identifier: FacultyIdentifier;
  faculty_short_name: string;
  faculty_full_name: string;
}

export interface AcademicYearOptionDTO {
  academic_year_identifier: AcademicYearIdentifier;
  label: string;
  isActive: boolean;
}

export interface LecturerResearchHourSummaryStatisticsDTO {
  total_lecturers_not_meeting_standard: number;
  total_remaining_hours_to_meet_standard: number;
  average_remaining_hours_to_meet_standard: number;
  minimum_required_hours: number;
}

export interface LecturerResearchHourShortfallWarningEntryDTO {
  lecturer_identifier: string;
  lecturer_code: string;
  lecturer_full_name: string;

  faculty_identifier: FacultyIdentifier;
  faculty_short_name: string;

  academic_year_identifier: AcademicYearIdentifier;

  required_hours: number;
  current_hours: number;
  remaining_hours: number;

  severity: ResearchHourShortfallSeverity;

  has_requested_warning: boolean;
  last_requested_at: string | null;

  last_requested_reason_code: ResearchHourWarningReasonCode | null;
  last_requested_reason_note: string | null;
}

export interface LecturerResearchHourWarningOverviewDTO {
  faculty_option_list: FacultyOptionDTO[];
  academic_year_option_list: AcademicYearOptionDTO[];
  summary_statistics: LecturerResearchHourSummaryStatisticsDTO;
  entry_list: LecturerResearchHourShortfallWarningEntryDTO[];
}

export interface ResearchHourWarningFilterDTO {
  faculty_identifier: FacultyIdentifier | "ALL_FACULTIES";
  academic_year_identifier: AcademicYearIdentifier;
  severity_filter: ResearchHourShortfallSeverityFilterCondition;
  notification_state_filter: WarningNotificationRequestStateFilterCondition;
  keyword: string;
}

export interface RequestWarningNotificationDTO {
  lecturer_identifier: string;
  academic_year_identifier: AcademicYearIdentifier;
  reason_code: ResearchHourWarningReasonCode;
  reason_note: string | null;
}

/* =========================
 * Model/UI (camelCase)
 * ========================= */

export interface FacultyOption {
  facultyIdentifier: FacultyIdentifier;
  facultyShortName: string;
  facultyFullName: string;
}

export interface AcademicYearOption {
  academicYearIdentifier: AcademicYearIdentifier;
  label: string;
  isActive: boolean;
}

export interface LecturerResearchHourSummaryStatistics {
  totalLecturersNotMeetingStandard: number;
  totalRemainingHoursToMeetStandard: number;
  averageRemainingHoursToMeetStandard: number;
  minimumRequiredHours: number;
}

export interface LecturerResearchHourShortfallWarningEntry {
  lecturerIdentifier: string;
  lecturerCode: string;
  lecturerFullName: string;

  facultyIdentifier: FacultyIdentifier;
  facultyShortName: string;

  academicYearIdentifier: AcademicYearIdentifier;

  requiredHours: number;
  currentHours: number;
  remainingHours: number;

  severity: ResearchHourShortfallSeverity;

  hasRequestedWarning: boolean;
  lastRequestedAt: string | null;

  lastRequestedReasonCode: ResearchHourWarningReasonCode | null;
  lastRequestedReasonNote: string | null;
}

export interface LecturerResearchHourWarningOverview {
  facultyOptionList: FacultyOption[];
  academicYearOptionList: AcademicYearOption[];
  summaryStatistics: LecturerResearchHourSummaryStatistics;
  entryList: LecturerResearchHourShortfallWarningEntry[];
}

/* =========================
 * Mapper
 * ========================= */

export function facultyOptionFromDto(dto: FacultyOptionDTO): FacultyOption {
  return {
    facultyIdentifier: dto.faculty_identifier,
    facultyShortName: dto.faculty_short_name,
    facultyFullName: dto.faculty_full_name,
  };
}

export function academicYearOptionFromDto(
  dto: AcademicYearOptionDTO,
): AcademicYearOption {
  return {
    academicYearIdentifier: dto.academic_year_identifier,
    label: dto.label,
    isActive: dto.isActive ?? false,
  };
}

export function summaryStatisticsFromDto(
  dto: LecturerResearchHourSummaryStatisticsDTO,
): LecturerResearchHourSummaryStatistics {
  return {
    totalLecturersNotMeetingStandard: dto.total_lecturers_not_meeting_standard,
    totalRemainingHoursToMeetStandard:
      dto.total_remaining_hours_to_meet_standard,
    averageRemainingHoursToMeetStandard:
      dto.average_remaining_hours_to_meet_standard,
    minimumRequiredHours: dto.minimum_required_hours,
  };
}

export function warningEntryFromDto(
  dto: LecturerResearchHourShortfallWarningEntryDTO,
): LecturerResearchHourShortfallWarningEntry {
  return {
    lecturerIdentifier: dto.lecturer_identifier,
    lecturerCode: dto.lecturer_code,
    lecturerFullName: dto.lecturer_full_name,

    facultyIdentifier: dto.faculty_identifier,
    facultyShortName: dto.faculty_short_name,

    academicYearIdentifier: dto.academic_year_identifier,

    requiredHours: dto.required_hours,
    currentHours: dto.current_hours,
    remainingHours: dto.remaining_hours,

    severity: dto.severity,

    hasRequestedWarning: dto.has_requested_warning,
    lastRequestedAt: dto.last_requested_at,

    lastRequestedReasonCode: dto.last_requested_reason_code,
    lastRequestedReasonNote: dto.last_requested_reason_note,
  };
}

export function overviewFromDto(
  dto: LecturerResearchHourWarningOverviewDTO,
): LecturerResearchHourWarningOverview {
  return {
    facultyOptionList: dto.faculty_option_list.map(facultyOptionFromDto),
    academicYearOptionList: dto.academic_year_option_list.map(
      academicYearOptionFromDto,
    ),
    summaryStatistics: summaryStatisticsFromDto(dto.summary_statistics),
    entryList: dto.entry_list.map(warningEntryFromDto),
  };
}

/* =========================
 * Helpers
 * ========================= */

export function formatHours(value: number | null | undefined): string {
  if (value == null || !Number.isFinite(value)) return "—";
  return Math.round(value).toString();
}

export function formatDateTimeVi(value: string | null): string {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "—";
  return new Intl.DateTimeFormat("vi-VN", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(d);
}

export function severityLabel(sev: ResearchHourShortfallSeverity): string {
  if (sev === "SEVERE") return "Thiếu nhiều";
  if (sev === "MODERATE") return "Thiếu vừa";
  return "Thiếu ít";
}

export function severityPillClass(sev: ResearchHourShortfallSeverity): string {
  if (sev === "SEVERE") return "bg-rose-50 text-rose-700 ring-rose-200";
  if (sev === "MODERATE") return "bg-amber-50 text-amber-700 ring-amber-200";
  return "bg-sky-50 text-sky-700 ring-sky-200";
}

export function warningRequestStateLabel(hasRequested: boolean): string {
  return hasRequested ? "Đã cảnh báo" : "Chưa cảnh báo";
}

export function warningRequestStatePillClass(hasRequested: boolean): string {
  return hasRequested
    ? "bg-slate-50 text-slate-700 ring-slate-200"
    : "bg-emerald-50 text-emerald-700 ring-emerald-200";
}
