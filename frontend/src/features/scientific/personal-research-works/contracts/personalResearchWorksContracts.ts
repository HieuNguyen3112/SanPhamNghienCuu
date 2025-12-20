// src/features/personal-research-works/contracts/personalResearchWorksContracts.ts

// =====================
// DTO (snake_case)
// =====================
export type PersonalWorkStatusCodeDTO =
  | "draft"
  | "submitted"
  | "approved"
  | "rejected";

// UI tab "pending" sẽ map sang DTO "submitted"
export type PersonalWorkFilterTabDTO =
  | "all"
  | "approved"
  | "submitted"
  | "rejected"
  | "draft";

export interface PersonalStatsDTO {
  total_count: number;
  approved_count: number;
  pending_count: number; // pending == submitted
  rejected_count: number;
  draft_count: number;
}

export interface PersonalWorkRowDTO {
  activity_id: number;
  activity_code: string;
  title: string;

  kind_id: number;
  kind_name: string;

  type_id: number | null;
  type_name: string | null;

  academic_year_id: number;
  academic_year_code: string;

  status_id: number;
  status_code: PersonalWorkStatusCodeDTO;
  status_name: string;

  // Derived by BE DTO (see Field Coverage)
  work_year: number | null;
  venue_name: string | null;

  // Current lecturer membership (derived/joined)
  member_role_id: number | null;
  member_role_name: string | null;

  // Hours for current lecturer (from research_activity_members.hours_assigned)
  lecturer_hours: string | null;

  submitted_at: string | null;
  approved_at: string | null;
  updated_at: string;
}

export interface PersonalWorkAuthorDTO {
  lecturer_id: number;
  lecturer_full_name: string;

  member_role_id: number;
  member_role_name: string;

  department_name: string | null;

  contribution_share: string | null;
}

export interface PersonalWorkEvidenceDTO {
  evidence_file_id: number;
  file_type_id: number;
  file_type_name: string;

  disk: string;
  path: string;

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
  acted_at: string; // activity_status_histories.acted_at
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
  kind_name: string;

  type_id: number | null;
  type_name: string | null;

  academic_year_id: number;
  academic_year_code: string;

  status_id: number;
  status_code: PersonalWorkStatusCodeDTO;
  status_name: string;

  // derived
  work_year: number | null;
  venue_name: string | null;

  // current lecturer data
  member_role_id: number | null;
  member_role_name: string | null;
  lecturer_hours: string | null;

  submitted_at: string | null;
  approved_at: string | null;
  total_hours_calc: string | null;

  // rejected reason (derived)
  rejection_note: string | null;

  authors: PersonalWorkAuthorDTO[];
  evidence_items: PersonalWorkEvidenceDTO[];

  approvals: PersonalWorkApprovalDTO[];
  status_histories: PersonalWorkStatusHistoryDTO[];
}

// =====================
// UI Models (camelCase)
// =====================
export type PersonalWorkStatusCode = PersonalWorkStatusCodeDTO;
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
}

export interface PersonalWorkAuthor {
  lecturerId: number;
  lecturerFullName: string;

  memberRoleId: number;
  memberRoleName: string;

  departmentName: string | null;
  contributionShare: string | null;
}

export interface PersonalWorkEvidence {
  evidenceFileId: number;
  fileTypeId: number;
  fileTypeName: string;

  disk: string;
  path: string;

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
  evidenceItems: PersonalWorkEvidence[];

  approvals: PersonalWorkApproval[];
  statusHistories: PersonalWorkStatusHistory[];
}

// =====================
// Mapper (DTO <-> UI)
// =====================
export const mapper = {
  tab: {
    toDto(tab: PersonalWorkFilterTab): PersonalWorkFilterTabDTO {
      if (tab === "pending") return "submitted";
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
      kindName: dto.kind_name,

      typeId: dto.type_id,
      typeName: dto.type_name,

      academicYearId: dto.academic_year_id,
      academicYearCode: dto.academic_year_code,

      statusId: dto.status_id,
      statusCode: dto.status_code,
      statusName: dto.status_name,

      workYear: dto.work_year,
      venueName: dto.venue_name,

      roleId: dto.member_role_id,
      roleName: dto.member_role_name,

      lecturerHours: dto.lecturer_hours,

      submittedAt: dto.submitted_at,
      approvedAt: dto.approved_at,
      updatedAt: dto.updated_at,
    };
  },

  detailFromDto(dto: PersonalWorkDetailDTO): PersonalWorkDetail {
    return {
      activityId: dto.activity_id,
      activityCode: dto.activity_code,

      title: dto.title,
      abstract: dto.abstract,

      kindId: dto.kind_id,
      kindName: dto.kind_name,

      typeId: dto.type_id,
      typeName: dto.type_name,

      academicYearId: dto.academic_year_id,
      academicYearCode: dto.academic_year_code,

      statusId: dto.status_id,
      statusCode: dto.status_code,
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
        lecturerId: a.lecturer_id,
        lecturerFullName: a.lecturer_full_name,
        memberRoleId: a.member_role_id,
        memberRoleName: a.member_role_name,
        departmentName: a.department_name,
        contributionShare: a.contribution_share,
      })),

      evidenceItems: dto.evidence_items.map((e) => ({
        evidenceFileId: e.evidence_file_id,
        fileTypeId: e.file_type_id,
        fileTypeName: e.file_type_name,
        disk: e.disk,
        path: e.path,
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
        fromStatusCode: h.from_status_code,
        toStatusCode: h.to_status_code,
        note: h.note,
      })),
    };
  },
};
