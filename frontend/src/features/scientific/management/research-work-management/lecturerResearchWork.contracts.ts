// DTO (snake_case) + UI models (camelCase) + shared mapper

// =====================
// DTO (snake_case)
// =====================
export type StatusModeDTO = "approved" | "pending" | "rejected" | "all";

export interface PaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface FilterDTO {
  faculty_id: number | null;
  academic_year_id: number | null;
  lecturer_name: string;
  status_mode: StatusModeDTO;
}

export interface FacultyOptionDTO {
  id: number;
  name: string;
}

export interface AcademicYearOptionDTO {
  id: number;
  code: string;
  is_active: boolean;
}

export interface OverviewDTO {
  lecturer_id: number;
  lecturer_code: string;
  lecturer_full_name: string;

  department_id: number;
  department_name: string;

  faculty_id: number;
  faculty_name: string;

  degree_id: number | null;
  degree_name: string | null;

  academic_rank_id: number | null;
  academic_rank_name: string | null;

  total_declared_research_work_count: number;
  approved_research_work_count: number;
  pending_research_work_count: number;
  rejected_research_work_count: number;
}

export interface ApprovedSummaryDTO {
  activity_id: number;
  activity_code: string;

  title: string;

  kind_id: number;
  kind_name: string;

  type_id: number | null;
  type_name: string | null;

  academic_year_id: number;
  academic_year_code: string;

  approved_at: string;
}

export interface AuthorDTO {
  lecturer_id: number;
  lecturer_full_name: string;

  member_role_id: number;
  member_role_name: string;

  contribution_share: string | null;
}

export interface EvidenceDTO {
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
  size_bytes: number | null;

  uploaded_at: string;
}

export interface FinalApprovalDTO {
  stage_code: "manager";
  status: "approved";
  decided_by_user_id: number | null;
  decided_by_user_name: string | null;
  decided_at: string | null;
  note: string | null;
}

export interface ApprovedDetailDTO {
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

  approved_at: string;

  authors: AuthorDTO[];
  evidence_items: EvidenceDTO[];

  final_approval: FinalApprovalDTO | null;
}

// =====================
// UI Models (camelCase)
// =====================
export type StatusMode = "approved" | "pending" | "rejected" | "all";

export interface Pagination {
  page: number;
  perPage: number;
  total: number;
  lastPage: number;
}

export interface PaginatedResult<T> {
  items: T[];
  pagination: Pagination;
}

export interface FilterState {
  facultyId: number | null;
  academicYearId: number | null;
  lecturerName: string;
  statusMode: StatusMode;
}

export interface FacultyOption {
  id: number;
  name: string;
}

export interface AcademicYearOption {
  id: number;
  code: string;
  isActive: boolean;
}

export interface OverviewItem {
  lecturerId: number;
  lecturerCode: string;
  lecturerFullName: string;

  departmentId: number;
  departmentName: string;

  facultyId: number;
  facultyName: string;

  degreeId: number | null;
  degreeName: string | null;

  academicRankId: number | null;
  academicRankName: string | null;

  totalCount: number;
  approvedCount: number;
  pendingCount: number;
  rejectedCount: number;
}

export interface ApprovedSummary {
  activityId: number;
  activityCode: string;

  title: string;

  kindId: number;
  kindName: string;

  typeId: number | null;
  typeName: string | null;

  academicYearId: number;
  academicYearCode: string;

  approvedAt: string;
}

export interface Author {
  lecturerId: number;
  lecturerFullName: string;

  memberRoleId: number;
  memberRoleName: string;

  contributionShare: string | null;
}

export interface Evidence {
  evidenceFileId: number;
  fileTypeId: number;
  fileTypeName: string;

  disk: string;
  path: string;
  previewUrl?: string | null;
  downloadUrl?: string | null;

  originalName: string;
  mimeType: string;
  sizeBytes: number | null;

  uploadedAt: string;
}

export interface FinalApproval {
  stageCode: "manager";
  status: "approved";
  decidedByUserId: number | null;
  decidedByUserName: string | null;
  decidedAt: string | null;
  note: string | null;
}

export interface ApprovedDetail {
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

  approvedAt: string;

  authors: Author[];
  evidenceItems: Evidence[];

  finalApproval: FinalApproval | null;
}

// =====================
// Mapper (gộp trong contracts)
// =====================
export const mapper = {
  filter: {
    toDto(filter: FilterState): FilterDTO {
      return {
        faculty_id: filter.facultyId,
        academic_year_id: filter.academicYearId,
        lecturer_name: filter.lecturerName,
        status_mode: filter.statusMode,
      };
    },
  },

  facultyOptionFromDto(dto: FacultyOptionDTO): FacultyOption {
    return { id: dto.id, name: dto.name };
  },

  academicYearOptionFromDto(dto: AcademicYearOptionDTO): AcademicYearOption {
    return { id: dto.id, code: dto.code, isActive: dto.is_active };
  },

  paginationFromDto(dto: PaginationDTO): Pagination {
    return {
      page: dto.page,
      perPage: dto.per_page,
      total: dto.total,
      lastPage: dto.last_page,
    };
  },

  overviewFromDto(dto: OverviewDTO): OverviewItem {
    return {
      lecturerId: dto.lecturer_id,
      lecturerCode: dto.lecturer_code,
      lecturerFullName: dto.lecturer_full_name,

      departmentId: dto.department_id,
      departmentName: dto.department_name,

      facultyId: dto.faculty_id,
      facultyName: dto.faculty_name,

      degreeId: dto.degree_id,
      degreeName: dto.degree_name,

      academicRankId: dto.academic_rank_id,
      academicRankName: dto.academic_rank_name,

      totalCount: dto.total_declared_research_work_count,
      approvedCount: dto.approved_research_work_count,
      pendingCount: dto.pending_research_work_count,
      rejectedCount: dto.rejected_research_work_count,
    };
  },

  approvedSummaryFromDto(dto: ApprovedSummaryDTO): ApprovedSummary {
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
      approvedAt: dto.approved_at,
    };
  },

  approvedDetailFromDto(dto: ApprovedDetailDTO): ApprovedDetail {
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
      approvedAt: dto.approved_at,
      authors: dto.authors.map((a) => ({
        lecturerId: a.lecturer_id,
        lecturerFullName: a.lecturer_full_name,
        memberRoleId: a.member_role_id,
        memberRoleName: a.member_role_name,
        contributionShare: a.contribution_share,
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
      finalApproval: dto.final_approval
        ? {
            stageCode: "manager",
            status: "approved",
            decidedByUserId: dto.final_approval.decided_by_user_id,
            decidedByUserName: dto.final_approval.decided_by_user_name,
            decidedAt: dto.final_approval.decided_at,
            note: dto.final_approval.note,
          }
        : null,
    };
  },
};
