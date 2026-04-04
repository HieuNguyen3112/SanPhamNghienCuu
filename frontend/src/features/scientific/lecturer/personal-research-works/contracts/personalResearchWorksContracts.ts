// src/features/personal-research-works/contracts/personalResearchWorksContracts.ts

// =====================
// DTO (snake_case)
// =====================
export type PersonalWorkStatusCodeDTO =
  | "draft"
  | "pending_member_confirm"
  | "member_rejected"
  | "pending_faculty_review"
  | "need_revision"
  | "submitted"
  | "approved"
  | "rejected";

// UI tab "pending" maps to backend grouped pending filter
export type PersonalWorkFilterTabDTO =
  | "all"
  | "pending"
  | "approved"
  | "rejected"
  | "draft";

export interface PersonalStatsDTO {
  total_count: number;
  approved_count: number;
  pending_count: number;
  rejected_count: number;
  draft_count: number;
}

export interface PersonalWorkPaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface PersonalWorkIndexResponseDTO {
  stats: PersonalStatsDTO;
  items: PersonalWorkRowDTO[];
  pagination: PersonalWorkPaginationDTO;
}

export interface PersonalWorkRowDTO {
  activity_id: number;
  activity_code: string;
  title: string;

  kind_id: number;
  kind_code?: string;
  kind_name: string;

  type_id: number | null;
  type_name: string | null;

  academic_year_id: number;
  academic_year_code: string;

  status_id: number;
  status_code: PersonalWorkStatusCodeDTO;
  status_name: string;

  work_year: number | null;
  venue_name: string | null;

  member_role_id: number | null;
  member_role_name: string | null;

  lecturer_hours: string | null;

  submitted_at: string | null;
  approved_at: string | null;
  updated_at: string;
  actions?: PersonalWorkActionsDTO;
}

export interface PersonalWorkActionsDTO {
  can_edit?: boolean;
  can_submit?: boolean;
  can_delete?: boolean;
  can_view?: boolean;
  can_reinvite?: boolean;
  can_manage_pending_members?: boolean;
  can_remove_pending_member?: boolean;
  can_resend_pending_invitation?: boolean;
}

export interface PersonalWorkAuthorDTO {
  member_id?: number;
  lecturer_id: number | null;
  lecturer_full_name: string;

  member_role_id: number | null;
  member_role_name: string | null;

  department_name: string | null;
  is_external?: boolean | null;

  contribution_share: string | null;
  confirmation_status?: "pending" | "accepted" | "rejected" | null;
  confirmation_note?: string | null;
  responded_at?: string | null;
}

export interface PersonalWorkMemberConfirmationDTO {
  member_id: number;
  lecturer_id: number;
  lecturer_code: string | null;
  lecturer_full_name: string | null;
  member_role_code: string | null;
  member_role_name: string | null;
  confirmation_status: "pending" | "accepted" | "rejected" | null;
  confirmation_note: string | null;
  responded_at: string | null;
}

export interface PersonalWorkEvidenceDTO {
  evidence_file_id: number;
  file_type_id: number;
  file_type_name: string;

  disk: string;
  path: string;
  preview_url?: string | null;
  download_url?: string | null;
  url?: string | null;

  original_name: string;
  mime_type: string;
  size_bytes: number;

  uploaded_at: string;
}

export interface PersonalWorkApprovalDTO {
  stage_code: "assistant" | "manager";
  stage_name: string;

  status: "pending" | "approved" | "rejected";
  decided_by_user_id: number | null;
  decided_by_user_name: string | null;
  decided_at: string | null;

  note: string | null;
}

export interface PersonalWorkStatusHistoryDTO {
  acted_at: string;
  acted_by_user_id: number;
  acted_by_user_name: string;

  from_status_code: PersonalWorkStatusCodeDTO | null;
  to_status_code: PersonalWorkStatusCodeDTO;

  note: string | null;
}

export interface PersonalWorkDetailDTO {
  activity_id: number;
  activity_code: string;

  title: string;
  abstract: string | null;

  kind_id: number;
  kind_code?: string;
  kind_name: string;

  type_id: number | null;
  type_name: string | null;

  academic_year_id: number;
  academic_year_code: string;

  status_id: number;
  status_code: PersonalWorkStatusCodeDTO;
  status_name: string;

  work_year: number | null;
  venue_name: string | null;

  member_role_id: number | null;
  member_role_name: string | null;
  lecturer_hours: string | null;

  submitted_at: string | null;
  approved_at: string | null;
  total_hours_calc: string | null;

  rejection_note: string | null;

  authors: PersonalWorkAuthorDTO[];
  member_confirmations?: PersonalWorkMemberConfirmationDTO[];
  rejected_members?: PersonalWorkMemberConfirmationDTO[];
  evidence_items: PersonalWorkEvidenceDTO[];

  approvals: PersonalWorkApprovalDTO[];
  status_histories: PersonalWorkStatusHistoryDTO[];
  actions?: PersonalWorkActionsDTO;
}

// =====================
// UI Models (camelCase)
// =====================
export type PersonalWorkStatusCode = Exclude<
  PersonalWorkStatusCodeDTO,
  "submitted"
>;
export type PersonalWorkFilterTab =
  | "all"
  | "approved"
  | "pending"
  | "rejected"
  | "draft";

export interface PersonalStats {
  totalCount: number;
  approvedCount: number;
  pendingCount: number;
  rejectedCount: number;
  draftCount: number;
}

export interface PersonalWorkRow {
  activityId: number;
  activityCode: string;
  title: string;

  kindId: number;
  kindCode: string;
  kindName: string;

  typeId: number | null;
  typeName: string | null;

  academicYearId: number;
  academicYearCode: string;

  statusId: number;
  statusCode: PersonalWorkStatusCode;
  statusName: string;

  workYear: number | null;
  venueName: string | null;

  roleId: number | null;
  roleName: string | null;

  lecturerHours: string | null;

  submittedAt: string | null;
  approvedAt: string | null;
  updatedAt: string;
  actions: PersonalWorkActions;
}

export interface PersonalWorkActions {
  canEdit: boolean;
  canSubmit: boolean;
  canDelete: boolean;
  canView: boolean;
  canReinvite: boolean;
  canManagePendingMembers: boolean;
  canRemovePendingMember: boolean;
  canResendPendingInvitation: boolean;
}

export interface PersonalWorkAuthor {
  memberId: number;
  lecturerId: number | null;
  lecturerFullName: string;

  memberRoleId: number | null;
  memberRoleName: string | null;

  departmentName: string | null;
  isExternal: boolean;
  contributionShare: string | null;
  confirmationStatus: "pending" | "accepted" | "rejected" | null;
  confirmationNote: string | null;
  respondedAt: string | null;
}

export interface PersonalWorkMemberConfirmation {
  memberId: number;
  lecturerId: number;
  lecturerCode: string | null;
  lecturerFullName: string | null;
  memberRoleCode: string | null;
  memberRoleName: string | null;
  confirmationStatus: "pending" | "accepted" | "rejected" | null;
  confirmationNote: string | null;
  respondedAt: string | null;
}

export interface PersonalWorkEvidence {
  evidenceFileId: number;
  fileTypeId: number;
  fileTypeName: string;

  disk: string;
  path: string;
  previewUrl?: string | null;
  downloadUrl?: string | null;

  originalName: string;
  mimeType: string;
  sizeBytes: number;

  uploadedAt: string;
}

export interface PersonalWorkApproval {
  stageCode: "assistant" | "manager";
  stageName: string;

  status: "pending" | "approved" | "rejected";
  decidedByUserId: number | null;
  decidedByUserName: string | null;
  decidedAt: string | null;

  note: string | null;
}

export interface PersonalWorkStatusHistory {
  actedAt: string;
  actedByUserId: number;
  actedByUserName: string;

  fromStatusCode: PersonalWorkStatusCode | null;
  toStatusCode: PersonalWorkStatusCode;

  note: string | null;
}

export interface PersonalWorkDetail {
  activityId: number;
  activityCode: string;

  title: string;
  abstract: string | null;

  kindId: number;
  kindCode: string;
  kindName: string;

  typeId: number | null;
  typeName: string | null;

  academicYearId: number;
  academicYearCode: string;

  statusId: number;
  statusCode: PersonalWorkStatusCode;
  statusName: string;

  workYear: number | null;
  venueName: string | null;

  roleId: number | null;
  roleName: string | null;
  lecturerHours: string | null;

  submittedAt: string | null;
  approvedAt: string | null;
  totalHoursCalc: string | null;

  rejectionNote: string | null;

  authors: PersonalWorkAuthor[];
  memberConfirmations: PersonalWorkMemberConfirmation[];
  rejectedMembers: PersonalWorkMemberConfirmation[];
  evidenceItems: PersonalWorkEvidence[];

  approvals: PersonalWorkApproval[];
  statusHistories: PersonalWorkStatusHistory[];
  actions: PersonalWorkActions;
}

// =====================
// Mapper (DTO <-> UI)
// =====================
export const mapper = {
  tab: {
    toDto(tab: PersonalWorkFilterTab): PersonalWorkFilterTabDTO {
      if (tab === "pending") return "pending";
      return tab;
    },
  },

  statsFromDto(dto: PersonalStatsDTO): PersonalStats {
    return {
      totalCount: dto.total_count,
      approvedCount: dto.approved_count,
      pendingCount: dto.pending_count,
      rejectedCount: dto.rejected_count,
      draftCount: dto.draft_count,
    };
  },

  rowFromDto(dto: PersonalWorkRowDTO): PersonalWorkRow {
    return {
      activityId: dto.activity_id,
      activityCode: dto.activity_code,
      title: dto.title,

      kindId: dto.kind_id,
      kindCode: dto.kind_code ?? "",
      kindName: dto.kind_name,

      typeId: dto.type_id,
      typeName: dto.type_name,

      academicYearId: dto.academic_year_id,
      academicYearCode: dto.academic_year_code,

      statusId: dto.status_id,
      statusCode: normalizeStatusCodeFromDto(dto.status_code),
      statusName: dto.status_name,

      workYear: dto.work_year,
      venueName: dto.venue_name,

      roleId: dto.member_role_id,
      roleName: dto.member_role_name,

      lecturerHours: dto.lecturer_hours,

      submittedAt: dto.submitted_at,
      approvedAt: dto.approved_at,
      updatedAt: dto.updated_at,
      actions: mapActionsFromDto(dto.actions),
    };
  },

  detailFromDto(dto: PersonalWorkDetailDTO): PersonalWorkDetail {
    return {
      activityId: dto.activity_id,
      activityCode: dto.activity_code,

      title: dto.title,
      abstract: dto.abstract,

      kindId: dto.kind_id,
      kindCode: dto.kind_code ?? "",
      kindName: dto.kind_name,

      typeId: dto.type_id,
      typeName: dto.type_name,

      academicYearId: dto.academic_year_id,
      academicYearCode: dto.academic_year_code,

      statusId: dto.status_id,
      statusCode: normalizeStatusCodeFromDto(dto.status_code),
      statusName: dto.status_name,

      workYear: dto.work_year,
      venueName: dto.venue_name,

      roleId: dto.member_role_id,
      roleName: dto.member_role_name,

      lecturerHours: dto.lecturer_hours,

      submittedAt: dto.submitted_at,
      approvedAt: dto.approved_at,
      totalHoursCalc: dto.total_hours_calc,

      rejectionNote: dto.rejection_note,

      authors: dto.authors.map((a) => ({
        memberId: a.member_id ?? a.lecturer_id ?? 0,
        lecturerId: a.lecturer_id ?? null,
        lecturerFullName: a.lecturer_full_name,
        memberRoleId: a.member_role_id,
        memberRoleName: a.member_role_name,
        departmentName: a.department_name,
        isExternal: Boolean(a.is_external),
        contributionShare: a.contribution_share,
        confirmationStatus: a.confirmation_status ?? null,
        confirmationNote: a.confirmation_note ?? null,
        respondedAt: a.responded_at ?? null,
      })),

      memberConfirmations: (dto.member_confirmations ?? []).map((m) => ({
        memberId: m.member_id,
        lecturerId: m.lecturer_id,
        lecturerCode: m.lecturer_code,
        lecturerFullName: m.lecturer_full_name,
        memberRoleCode: m.member_role_code,
        memberRoleName: m.member_role_name,
        confirmationStatus: m.confirmation_status,
        confirmationNote: m.confirmation_note,
        respondedAt: m.responded_at,
      })),

      rejectedMembers: (dto.rejected_members ?? []).map((m) => ({
        memberId: m.member_id,
        lecturerId: m.lecturer_id,
        lecturerCode: m.lecturer_code,
        lecturerFullName: m.lecturer_full_name,
        memberRoleCode: m.member_role_code,
        memberRoleName: m.member_role_name,
        confirmationStatus: m.confirmation_status,
        confirmationNote: m.confirmation_note,
        respondedAt: m.responded_at,
      })),

      evidenceItems: dto.evidence_items.map((e) => ({
        evidenceFileId: e.evidence_file_id,
        fileTypeId: e.file_type_id,
        fileTypeName: e.file_type_name,
        disk: e.disk,
        path: e.path,
        previewUrl: e.preview_url ?? e.url ?? null,
        downloadUrl: e.download_url ?? null,
        originalName: e.original_name,
        mimeType: e.mime_type,
        sizeBytes: e.size_bytes,
        uploadedAt: e.uploaded_at,
      })),

      approvals: dto.approvals.map((a) => ({
        stageCode: a.stage_code,
        stageName: a.stage_name,
        status: a.status,
        decidedByUserId: a.decided_by_user_id,
        decidedByUserName: a.decided_by_user_name,
        decidedAt: a.decided_at,
        note: a.note,
      })),

      statusHistories: dto.status_histories.map((h) => ({
        actedAt: h.acted_at,
        actedByUserId: h.acted_by_user_id,
        actedByUserName: h.acted_by_user_name,
        fromStatusCode: normalizeNullableStatusCodeFromDto(h.from_status_code),
        toStatusCode: normalizeStatusCodeFromDto(h.to_status_code),
        note: h.note,
      })),
      actions: mapActionsFromDto(dto.actions),
    };
  },
};

function normalizeStatusCodeFromDto(
  statusCode: PersonalWorkStatusCodeDTO,
): PersonalWorkStatusCode {
  if (statusCode === "submitted") {
    return "pending_faculty_review";
  }

  return statusCode;
}

function normalizeNullableStatusCodeFromDto(
  statusCode: PersonalWorkStatusCodeDTO | null,
): PersonalWorkStatusCode | null {
  if (!statusCode) return null;
  return normalizeStatusCodeFromDto(statusCode);
}

function mapActionsFromDto(
  dto?: PersonalWorkActionsDTO | null,
): PersonalWorkActions {
  return {
    canEdit: dto?.can_edit ?? false,
    canSubmit: dto?.can_submit ?? false,
    canDelete: dto?.can_delete ?? false,
    canView: dto?.can_view ?? true,
    canReinvite: dto?.can_reinvite ?? false,
    canManagePendingMembers: dto?.can_manage_pending_members ?? false,
    canRemovePendingMember: dto?.can_remove_pending_member ?? false,
    canResendPendingInvitation: dto?.can_resend_pending_invitation ?? false,
  };
}
