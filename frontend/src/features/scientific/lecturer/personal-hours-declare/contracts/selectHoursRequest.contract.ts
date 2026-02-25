export type ActivityStatusCode =
  | "approved"
  | "submitted"
  | "rejected"
  | "draft"
  | "pending_faculty_review"
  | "pending_member_confirm"
  | "member_rejected";

export type HoursRequestState =
  | "hours_not_submitted"
  | "hours_pending_faculty"
  | "hours_approved"
  | "hours_rejected";

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

export interface EvidenceFileTypeDTO {
  id: number;
  code: string;
  name: string;
}

export interface EvidenceFileType {
  id: number;
  code: string;
  name: string;
}

export interface AcademicYearOptionDTO {
  id: number;
  code: string;
  start_date: string;
  end_date: string;
  is_active?: boolean;
  is_current?: boolean;
}

export interface AcademicYearOption {
  id: number;
  code: string;
  startDate: string;
  endDate: string;
  isActive: boolean;
  isCurrent: boolean;
}

export interface ApprovedWorkRowDTO {
  activity_id: number;
  activity_code: string;
  academic_year_code: string;
  title: string;
  kind_name: string;
  member_role_name: string;
  hours_assigned: number | null;
  rule_summary?: string | null;
  conversion_rule_present?: boolean;
  calculated_hours?: number | null;
  proposed_hours?: number | null;
  effective_hours_display?: number | null;
  total_hours_activity?: number | null;
  member_hours?: number | null;
  formula_explanation?: FormulaExplanationDTO | null;
  can_edit_proposed_hours?: boolean;
  evidence_count?: number;
  activity_status_code: ActivityStatusCode;
  hours_request_state: HoursRequestState;
  hours_rejection_reason?: string | null;
  next_action_code?: string | null;
  next_action_text?: string | null;
}

export interface ApprovedWorkPaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface ApprovedWorkListSummaryDTO {
  approved_count: number;
}

export interface ApprovedWorkListResponseDTO {
  items: ApprovedWorkRowDTO[];
  pagination: ApprovedWorkPaginationDTO;
  summary: ApprovedWorkListSummaryDTO;
}

export interface WorkDetailDTO {
  activity_id: number;
  title: string;
  kind_name: string;
  academic_year_code: string;
  publication_or_unit: string;
  member_role_name: string;
  contribution_share: number | null;
  rule_summary: string;
  conversion_rule_present?: boolean;
  calculated_hours?: number | null;
  proposed_hours?: number | null;
  effective_hours_display?: number | null;
  total_hours_activity?: number | null;
  member_hours?: number | null;
  formula_explanation?: FormulaExplanationDTO | null;
  can_edit_proposed_hours?: boolean;
  hours_for_lecturer: number | null;
  activity_status_code: ActivityStatusCode;
  hours_request_state: HoursRequestState;
  hours_rejection_reason?: string | null;
  next_action_code?: string | null;
  next_action_text?: string | null;
  evidence_files?: EvidenceFileDTO[];
}

export interface ApprovedWorkRow {
  activityId: number;
  activityCode: string;
  academicYearCode: string;
  title: string;
  kindName: string;
  memberRoleName: string;
  hoursAssigned: number | null;
  ruleSummary: string | null;
  conversionRulePresent: boolean;
  calculatedHours: number | null;
  proposedHours: number | null;
  effectiveHoursDisplay: number | null;
  totalHoursActivity: number | null;
  memberHours: number | null;
  formulaExplanation: FormulaExplanation | null;
  canEditProposedHours: boolean;
  evidenceCount: number;
  activityStatusCode: ActivityStatusCode;
  hoursRequestState: HoursRequestState;
  hoursRejectionReason: string | null;
  nextActionCode: string | null;
  nextActionText: string | null;
}

export interface WorkDetail {
  activityId: number;
  title: string;
  kindName: string;
  academicYearCode: string;
  publicationOrUnit: string;
  memberRoleName: string;
  contributionShare: number | null;
  ruleSummary: string;
  conversionRulePresent: boolean;
  calculatedHours: number | null;
  proposedHours: number | null;
  effectiveHoursDisplay: number | null;
  totalHoursActivity: number | null;
  memberHours: number | null;
  formulaExplanation: FormulaExplanation | null;
  canEditProposedHours: boolean;
  hoursForLecturer: number | null;
  activityStatusCode: ActivityStatusCode;
  hoursRequestState: HoursRequestState;
  hoursRejectionReason: string | null;
  nextActionCode: string | null;
  nextActionText: string | null;
  evidenceFiles: EvidenceFile[];
}

export interface WorksFilterState {
  academicYearId: number | null;
  hoursMode:
    | "all"
    | "hours_not_submitted"
    | "hours_pending_faculty"
    | "hours_approved"
    | "hours_rejected";
  keyword: string;
}

export function evidenceFileFromDto(dto: EvidenceFileDTO): EvidenceFile {
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
}

export function academicYearOptionFromDto(
  dto: AcademicYearOptionDTO
): AcademicYearOption {
  return {
    id: dto.id,
    code: dto.code,
    startDate: dto.start_date,
    endDate: dto.end_date,
    isActive: dto.is_active ?? false,
    isCurrent: dto.is_current ?? false,
  };
}

export function formulaExplanationFromDto(
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
}

export function approvedWorkRowFromDto(
  dto: ApprovedWorkRowDTO
): ApprovedWorkRow {
  return {
    activityId: dto.activity_id,
    activityCode: dto.activity_code,
    academicYearCode: dto.academic_year_code,
    title: dto.title,
    kindName: dto.kind_name,
    memberRoleName: dto.member_role_name,
    hoursAssigned: dto.hours_assigned,
    ruleSummary: dto.rule_summary ?? null,
    conversionRulePresent: dto.conversion_rule_present ?? false,
    calculatedHours: dto.calculated_hours ?? null,
    proposedHours: dto.proposed_hours ?? null,
    effectiveHoursDisplay: dto.effective_hours_display ?? dto.hours_assigned ?? null,
    totalHoursActivity: dto.total_hours_activity ?? null,
    memberHours: dto.member_hours ?? null,
    formulaExplanation: formulaExplanationFromDto(dto.formula_explanation),
    canEditProposedHours: dto.can_edit_proposed_hours ?? false,
    evidenceCount: dto.evidence_count ?? 0,
    activityStatusCode: dto.activity_status_code,
    hoursRequestState: dto.hours_request_state,
    hoursRejectionReason: dto.hours_rejection_reason ?? null,
    nextActionCode: dto.next_action_code ?? null,
    nextActionText: dto.next_action_text ?? null,
  };
}

export function workDetailFromDto(dto: WorkDetailDTO): WorkDetail {
  return {
    activityId: dto.activity_id,
    title: dto.title,
    kindName: dto.kind_name,
    academicYearCode: dto.academic_year_code,
    publicationOrUnit: dto.publication_or_unit,
    memberRoleName: dto.member_role_name,
    contributionShare: dto.contribution_share,
    ruleSummary: dto.rule_summary,
    conversionRulePresent: dto.conversion_rule_present ?? false,
    calculatedHours: dto.calculated_hours ?? null,
    proposedHours: dto.proposed_hours ?? null,
    effectiveHoursDisplay: dto.effective_hours_display ?? dto.hours_for_lecturer ?? null,
    totalHoursActivity: dto.total_hours_activity ?? null,
    memberHours: dto.member_hours ?? null,
    formulaExplanation: formulaExplanationFromDto(dto.formula_explanation),
    canEditProposedHours: dto.can_edit_proposed_hours ?? false,
    hoursForLecturer: dto.hours_for_lecturer,
    activityStatusCode: dto.activity_status_code,
    hoursRequestState: dto.hours_request_state,
    hoursRejectionReason: dto.hours_rejection_reason ?? null,
    nextActionCode: dto.next_action_code ?? null,
    nextActionText: dto.next_action_text ?? null,
    evidenceFiles: (dto.evidence_files ?? []).map(evidenceFileFromDto),
  };
}

export function formatHours(value: number | null): string {
  if (value == null || !Number.isFinite(value)) return "—";
  return value.toFixed(0);
}

export function formatBytes(sizeBytes: number): string {
  if (!Number.isFinite(sizeBytes) || sizeBytes < 0) return "0 B";
  if (sizeBytes < 1024) return `${sizeBytes} B`;
  const kb = sizeBytes / 1024;
  if (kb < 1024) return `${kb.toFixed(1)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}

export function isWorkEligibleForSubmit(
  row: Pick<
    ApprovedWorkRow,
    "hoursRequestState" | "effectiveHoursDisplay" | "evidenceCount"
  >
): boolean {
  return (
    (row.hoursRequestState === "hours_not_submitted" ||
      row.hoursRequestState === "hours_rejected") &&
    row.effectiveHoursDisplay !== null &&
    row.evidenceCount > 0
  );
}
