export type ActivityStatusCode =
  | "approved"
  | "submitted"
  | "rejected"
  | "draft";

/**
 * activity_approvals.status (enum ở DB của bạn):
 * pending | approved | rejected
 */
export type ApprovalStageStatus = "pending" | "approved" | "rejected";

/**
 * Trạng thái “DUYỆT GIỜ” của công trình (derived)
 * - eligible: chưa gửi duyệt giờ
 * - submitted: đã gửi yêu cầu duyệt giờ (đang chờ)
 * - hours_approved: đã duyệt giờ
 */
export type HoursRequestState = "eligible" | "submitted" | "hours_approved";

/**
 * DTO (snake_case) - giả lập backend trả về
 * P0: assistant_approval_status + manager_approval_status lấy từ activity_approvals (join theo stage)
 * P0: hours_request_state là derived field (backend cần trả về)
 */
export interface ApprovedWorkRowDTO {
  activity_id: number;
  activity_code: string; // research_activities.activity_code
  academic_year_code: string; // join academic_years.code

  title: string;
  kind_name: string;

  member_role_name: string;
  hours_assigned: number | null; // research_activity_members.hours_assigned (nullable)

  activity_status_code: ActivityStatusCode; // join activity_statuses.code (tham chiếu)
  assistant_approval_status: ApprovalStageStatus; // activity_approvals.status where stage=assistant
  manager_approval_status: ApprovalStageStatus; // activity_approvals.status where stage=manager

  hours_request_state: HoursRequestState; // derived
}

export interface WorkDetailDTO {
  activity_id: number;
  title: string;
  kind_name: string;

  academic_year_code: string;
  publication_or_unit: string;

  member_role_name: string;
  contribution_share: number | null;

  rule_summary: string; // derived
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

  // ✅ dùng để xác định “đã duyệt nội dung đủ 2 tầng”
  assistantApprovalStatus: ApprovalStageStatus;
  managerApprovalStatus: ApprovalStageStatus;

  // ✅ trạng thái duyệt giờ
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

/**
 * Filter ở màn này: lọc theo “duyệt giờ”
 * (vì list đã là công trình hợp lệ nội dung rồi)
 */
export interface WorksFilterState {
  hoursMode: "all" | "not_reviewed_hours" | "waiting_hours" | "hours_approved";
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

/** ✅ điều kiện hợp lệ nội dung: khoa + trường đều approved */
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
