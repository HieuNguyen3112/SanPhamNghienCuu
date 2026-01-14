export type ActivityStatusCode =
  | "approved"
  | "submitted"
  | "rejected"
  | "draft";

// activity_approvals.status
export type ApprovalStageStatus = "pending" | "approved" | "rejected";

// Hours approval status derived for the current lecturer
export type HoursRequestState =
  | "eligible"
  | "submitted"
  | "hours_approved"
  | "rejected";

export interface ApprovedWorkRowDTO {
  activity_id: number;
  activity_code: string;
  academic_year_code: string;

  title: string;
  kind_name: string;

  member_role_name: string;
  hours_assigned: number | null;

  activity_status_code: ActivityStatusCode;
  assistant_approval_status: ApprovalStageStatus;
  manager_approval_status: ApprovalStageStatus;

  hours_request_state: HoursRequestState;
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
  hours_for_lecturer: number | null;

  activity_status_code: ActivityStatusCode;
  assistant_approval_status: ApprovalStageStatus;
  manager_approval_status: ApprovalStageStatus;

  hours_request_state: HoursRequestState;
}

export interface ApprovedWorkRow {
  activityId: number;
  activityCode: string;
  academicYearCode: string;

  title: string;
  kindName: string;

  memberRoleName: string;
  hoursAssigned: number | null;

  activityStatusCode: ActivityStatusCode;
  assistantApprovalStatus: ApprovalStageStatus;
  managerApprovalStatus: ApprovalStageStatus;

  hoursRequestState: HoursRequestState;
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
  hoursForLecturer: number | null;

  activityStatusCode: ActivityStatusCode;
  assistantApprovalStatus: ApprovalStageStatus;
  managerApprovalStatus: ApprovalStageStatus;

  hoursRequestState: HoursRequestState;
}

export interface WorksFilterState {
  hoursMode: "all" | "not_submitted" | "pending" | "approved" | "rejected";
  keyword: string;
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

    activityStatusCode: dto.activity_status_code,
    assistantApprovalStatus: dto.assistant_approval_status,
    managerApprovalStatus: dto.manager_approval_status,

    hoursRequestState: dto.hours_request_state,
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
    hoursForLecturer: dto.hours_for_lecturer,

    activityStatusCode: dto.activity_status_code,
    assistantApprovalStatus: dto.assistant_approval_status,
    managerApprovalStatus: dto.manager_approval_status,

    hoursRequestState: dto.hours_request_state,
  };
}

export function formatHours(value: number | null): string {
  if (value == null || !Number.isFinite(value)) return "—";
  return value.toFixed(0);
}

export function isContentFullyApproved(
  row: Pick<
    ApprovedWorkRow,
    "assistantApprovalStatus" | "managerApprovalStatus"
  >
): boolean {
  return (
    row.assistantApprovalStatus === "approved" &&
    row.managerApprovalStatus === "approved"
  );
}
