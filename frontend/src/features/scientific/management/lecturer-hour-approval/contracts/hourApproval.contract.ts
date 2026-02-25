// UI model camelCase + API DTO snake_case (mock-ready)

export type HourApprovalRequestStatus = "pending" | "approved" | "rejected";

export type HourApprovalRejectReasonCode =
  | "hours_not_reasonable"
  | "work_not_eligible"
  | "missing_evidence"
  | "other";

export interface FacultyOption {
  id: number;
  name: string;
}

export interface AcademicYearOption {
  id: number;
  code: string;
  startDate: string;
  endDate: string;
  isActive: boolean;
  isCurrent: boolean;
}

export interface HourApprovalFilter {
  facultyId: number | null; // null = all
  academicYearId: number | null;
  status: "all" | HourApprovalRequestStatus;
  submittedFrom: string | null; // YYYY-MM-DD
  submittedTo: string | null; // YYYY-MM-DD
  searchText: string; // lecturer code or name
}

export interface EvidenceFileDTO {
  id: number;
  activity_id: number;
  file_type_id: number;
  file_type_name?: string | null;
  original_name: string;
  mime_type: string;
  size_bytes: number;
  uploaded_at: string;
  download_url?: string | null;
}

export interface EvidenceFile {
  id: number;
  activityId: number;
  fileTypeId: number;
  fileTypeName: string | null;
  originalName: string;
  mimeType: string;
  sizeBytes: number;
  uploadedAt: string;
  downloadUrl: string | null;
}

export interface FormulaModifierDTO {
  name: string;
  value: string | number | null;
}

export interface FormulaExplanationDTO {
  rule_name: string;
  distribution_strategy: string | null;
  base_hours: number | null;
  modifiers: FormulaModifierDTO[];
  total_hours_activity: number | null;
  member_hours: number | null;
  member_share_percent: number | null;
  member_role_code: string | null;
  contribution_share: number | null;
}

export interface FormulaModifier {
  name: string;
  value: string | number | null;
}

export interface FormulaExplanation {
  ruleName: string;
  distributionStrategy: string | null;
  baseHours: number | null;
  modifiers: FormulaModifier[];
  totalHoursActivity: number | null;
  memberHours: number | null;
  memberSharePercent: number | null;
  memberRoleCode: string | null;
  contributionShare: number | null;
}

/** ========== DTOs (snake_case) ========== */

export interface HourApprovalRequestSummaryDTO {
  request_id: number;

  lecturer_id: number;
  lecturer_code: string;
  lecturer_full_name: string;

  faculty_id: number;
  faculty_name: string;

  activity_count: number;
  works_count?: number;
  total_hours: number;
  total_hours_requested?: number;

  submitted_at: string; // ISO
  status: HourApprovalRequestStatus;
  status_code?: HourApprovalRequestStatus;
  status_label?: string;
}

export interface HourApprovalRequestItemDTO {
  activity_id: number;
  activity_title: string;
  activity_kind_name: string;
  member_role_name: string;
  rule_summary?: string | null;
  conversion_rule_present?: boolean;
  calculated_hours?: number | null;
  proposed_hours?: number | null;
  effective_hours_display?: number | null;
  total_hours_activity?: number | null;
  member_hours?: number | null;
  formula_explanation?: FormulaExplanationDTO | null;
  hours_converted: number;
  approval_status?: HourApprovalRequestStatus;
  rejection_reason?: string | null;
  evidence_files?: EvidenceFileDTO[];
}

export interface HourApprovalRequestDetailDTO {
  request_id: number;

  lecturer_id: number;
  lecturer_code: string;
  lecturer_full_name: string;

  faculty_id: number;
  faculty_name: string;

  submitted_at: string; // ISO
  status: HourApprovalRequestStatus;

  note_from_lecturer: string | null;
  note_from_faculty?: string | null;

  activity_count: number;
  total_hours: number;
  total_hours_requested?: number;
  total_hours_valid?: number;

  items: HourApprovalRequestItemDTO[];
}

export interface RejectPayloadDTO {
  reason_code: HourApprovalRejectReasonCode;
  reason_detail: string | null;
  activity_ids?: number[];
}

export interface ApprovePayloadDTO {
  activity_ids?: number[];
}

export interface HourApprovalListResponseDTO {
  success: boolean;
  message: string;
  data: {
    items: HourApprovalRequestSummaryDTO[];
    pagination: {
      page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  };
}

export interface HourApprovalDetailResponseDTO {
  success: boolean;
  message: string;
  data: HourApprovalRequestDetailDTO;
}

/** ========== Models (camelCase) ========== */

export interface HourApprovalRequestSummary {
  requestId: number;

  lecturerId: number;
  lecturerCode: string;
  lecturerFullName: string;

  facultyId: number;
  facultyName: string;

  activityCount: number;
  totalHours: number;

  submittedAt: string; // ISO
  status: HourApprovalRequestStatus;
}

export interface HourApprovalRequestItem {
  activityId: number;
  activityTitle: string;
  activityKindName: string;
  memberRoleName: string;
  ruleSummary: string | null;
  conversionRulePresent: boolean;
  calculatedHours: number | null;
  proposedHours: number | null;
  effectiveHoursDisplay: number | null;
  totalHoursActivity: number | null;
  memberHours: number | null;
  formulaExplanation: FormulaExplanation | null;
  hoursConverted: number;
  approvalStatus: HourApprovalRequestStatus | null;
  rejectionReason: string | null;
  evidenceFiles: EvidenceFile[];
}

export interface HourApprovalRequestDetail {
  requestId: number;

  lecturerId: number;
  lecturerCode: string;
  lecturerFullName: string;

  facultyId: number;
  facultyName: string;

  submittedAt: string;
  status: HourApprovalRequestStatus;

  noteFromLecturer: string | null;
  noteFromFaculty: string | null;

  activityCount: number;
  totalHours: number;

  items: HourApprovalRequestItem[];
}

export interface RejectPayload {
  reasonCode: HourApprovalRejectReasonCode;
  reasonNote: string | null;
  activityIds?: number[];
}

export interface ApprovePayload {
  activityIds?: number[];
}

/** ========== Mappers ========== */

export const hourApprovalMappers = {
  evidenceFileFromDto(dto: EvidenceFileDTO): EvidenceFile {
    return {
      id: dto.id,
      activityId: dto.activity_id,
      fileTypeId: dto.file_type_id,
      fileTypeName: dto.file_type_name ?? null,
      originalName: dto.original_name,
      mimeType: dto.mime_type,
      sizeBytes: dto.size_bytes,
      uploadedAt: dto.uploaded_at,
      downloadUrl: dto.download_url ?? null,
    };
  },

  formulaExplanationFromDto(
    dto?: FormulaExplanationDTO | null
  ): FormulaExplanation | null {
    if (!dto) return null;

    return {
      ruleName: dto.rule_name,
      distributionStrategy: dto.distribution_strategy,
      baseHours: dto.base_hours,
      modifiers: (dto.modifiers ?? []).map((item) => ({
        name: item.name,
        value: item.value ?? null,
      })),
      totalHoursActivity: dto.total_hours_activity,
      memberHours: dto.member_hours,
      memberSharePercent: dto.member_share_percent,
      memberRoleCode: dto.member_role_code,
      contributionShare: dto.contribution_share,
    };
  },

  summaryFromDto(
    dto: HourApprovalRequestSummaryDTO
  ): HourApprovalRequestSummary {
    return {
      requestId: dto.request_id,
      lecturerId: dto.lecturer_id,
      lecturerCode: dto.lecturer_code,
      lecturerFullName: dto.lecturer_full_name,
      facultyId: dto.faculty_id,
      facultyName: dto.faculty_name,
      activityCount: dto.activity_count,
      totalHours: dto.total_hours ?? dto.total_hours_requested ?? 0,
      submittedAt: dto.submitted_at,
      status: dto.status_code ?? dto.status,
    };
  },

  detailFromDto(dto: HourApprovalRequestDetailDTO): HourApprovalRequestDetail {
    return {
      requestId: dto.request_id,
      lecturerId: dto.lecturer_id,
      lecturerCode: dto.lecturer_code,
      lecturerFullName: dto.lecturer_full_name,
      facultyId: dto.faculty_id,
      facultyName: dto.faculty_name,
      submittedAt: dto.submitted_at,
      status: dto.status,
      noteFromLecturer: dto.note_from_lecturer,
      noteFromFaculty: dto.note_from_faculty ?? null,
      activityCount: dto.activity_count,
      totalHours: dto.total_hours ?? dto.total_hours_requested ?? 0,
      items: dto.items.map((item) => ({
        activityId: item.activity_id,
        activityTitle: item.activity_title,
        activityKindName: item.activity_kind_name,
        memberRoleName: item.member_role_name,
        ruleSummary: item.rule_summary ?? null,
        conversionRulePresent: item.conversion_rule_present ?? false,
        calculatedHours: item.calculated_hours ?? null,
        proposedHours: item.proposed_hours ?? null,
        effectiveHoursDisplay:
          item.effective_hours_display ?? item.hours_converted ?? null,
        totalHoursActivity: item.total_hours_activity ?? null,
        memberHours: item.member_hours ?? null,
        formulaExplanation: hourApprovalMappers.formulaExplanationFromDto(
          item.formula_explanation
        ),
        hoursConverted: item.hours_converted,
        approvalStatus: item.approval_status ?? null,
        rejectionReason: item.rejection_reason ?? null,
        evidenceFiles: (item.evidence_files ?? []).map((e) =>
          hourApprovalMappers.evidenceFileFromDto(e)
        ),
      })),
    };
  },

  rejectPayloadToDto(payload: RejectPayload): RejectPayloadDTO {
    return {
      reason_code: payload.reasonCode,
      reason_detail: payload.reasonNote,
      activity_ids: payload.activityIds,
    };
  },

  approvePayloadToDto(payload: ApprovePayload): ApprovePayloadDTO {
    return {
      activity_ids: payload.activityIds,
    };
  },
};

/** ========== Small utils ========== */

export function formatDateTimeVietnamese(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  const dd = String(date.getDate()).padStart(2, "0");
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yyyy = date.getFullYear();

  const hh = String(date.getHours()).padStart(2, "0");
  const min = String(date.getMinutes()).padStart(2, "0");

  return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

export function formatDateVietnamese(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  const dd = String(date.getDate()).padStart(2, "0");
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yyyy = date.getFullYear();

  return `${dd}/${mm}/${yyyy}`;
}

export function formatBytes(sizeBytes: number): string {
  if (!Number.isFinite(sizeBytes) || sizeBytes < 0) return "0 B";
  if (sizeBytes < 1024) return `${sizeBytes} B`;
  const kb = sizeBytes / 1024;
  if (kb < 1024) return `${kb.toFixed(1)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}
