export type DeclarationStatusUi =
  | "DRAFT"
  | "PENDING_MEMBER_CONFIRM"
  | "MEMBER_REJECTED"
  | "PENDING_FACULTY_REVIEW"
  | "SUBMITTED"
  | "APPROVED"
  | "REJECTED";

export type AcademicYearDto = {
  id: number;
  code: string; // VARCHAR(9)
  start_date: string; // DATE
  end_date: string; // DATE
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
};

export type ActivityKindDto = {
  id: number;
  code: string; // paper|book|project|conference
  name: string;
};

export type ActivityTypeDto = {
  id: number;
  kind_id: number;
  code: string;
  name: string;
  research_hours?: number | null;
  max_occurrences_per_year?: number | null;
};

export type ActivityStatusDto = {
  id: number;
  code:
    | "draft"
    | "pending_member_confirm"
    | "member_rejected"
    | "pending_faculty_review"
    | "submitted"
    | "approved"
    | "rejected";
  name: string;
};

export type MemberRoleDto = {
  id: number;
  code: string;
  name: string;
};

export type EvidenceFileTypeDto = {
  id: number;
  code: string;
  name: string;
};

export type LecturerOptionDto = {
  id: number;
  code: string;
  full_name: string;
  department_id: number;
  // derived/read-only (join)
  department_name?: string;
  faculty_id?: number | null;
  faculty_name?: string | null;
};

export type ResearchActivityMemberUpsertDto = {
  lecturer_id: number;
  member_role_id: number;
  contribution_share?: number | null; // DECIMAL(6,4) nullable
};

export type EvidenceFileDto = {
  id: number;
  activity_id: number;
  file_type_id: number;
  disk: string;
  path: string;
  original_name: string;
  mime_type: string;
  size_bytes: number;
  sha256: string;
  uploaded_by_user_id: number;
  uploaded_at: string;
  created_at?: string;
  updated_at?: string;

  // derived (NOT a DB column)
  url?: string;
  preview_url?: string;
  download_url?: string;
  // derived (join)
  file_type_name?: string;
};

export type EvidenceLinkDto = {
  id: number;
  activity_id: number;
  lecturer_id: number;
  lecturer_name?: string | null;
  url: string;
  added_by_user_id: number;
  created_at?: string;
  updated_at?: string;
};

export type HoursDistributionItem = {
  lecturer_id: number;
  lecturer_name: string;
  member_role_id: number;
  member_role_name: string;
  hours: number;
};

export type HoursComputationResult = {
  total_hours: number;
  current_lecturer_hours: number;
  distribution: HoursDistributionItem[];
};

export function mapStatusCodeToUi(
  code: ActivityStatusDto["code"],
): DeclarationStatusUi {
  switch (code) {
    case "draft":
      return "DRAFT";
    case "pending_member_confirm":
      return "PENDING_MEMBER_CONFIRM";
    case "member_rejected":
      return "MEMBER_REJECTED";
    case "pending_faculty_review":
      return "PENDING_FACULTY_REVIEW";
    case "submitted":
      return "PENDING_FACULTY_REVIEW";
    case "approved":
      return "APPROVED";
    case "rejected":
      return "REJECTED";
  }
}

export function mapUiToStatusCode(
  ui: DeclarationStatusUi,
): ActivityStatusDto["code"] {
  switch (ui) {
    case "DRAFT":
      return "draft";
    case "PENDING_MEMBER_CONFIRM":
      return "pending_member_confirm";
    case "MEMBER_REJECTED":
      return "member_rejected";
    case "PENDING_FACULTY_REVIEW":
      return "pending_faculty_review";
    case "SUBMITTED":
      return "pending_faculty_review";
    case "APPROVED":
      return "approved";
    case "REJECTED":
      return "rejected";
  }
}
