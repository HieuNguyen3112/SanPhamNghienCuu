/** ===== Enums (UI) ===== */
export type ResearchWorkTypeKey = "paper" | "project" | "book" | "conference";
export type ResearchWorkStatusCode =
  | "draft"
  | "submitted"
  | "approved"
  | "rejected";
export type LecturerRoleKey = string;
export type ManagementLevelKey = string;

/** ===== Options ===== */
export interface FacultyOption {
  id: number;
  code?: string | null;
  name: string;
}

export interface DepartmentOption {
  id: number;
  faculty_id: number;
  code?: string | null;
  name: string;
}

export interface WorkTypeOption {
  id: number;
  code: ResearchWorkTypeKey | string;
  name: string;
}

export interface StatusOption {
  id: number;
  code: ResearchWorkStatusCode | string;
  name: string;
}

export interface AuthorRoleOption {
  id: number;
  code: LecturerRoleKey;
  name: string;
}

export interface ManagementLevelOption {
  id: number;
  code: ManagementLevelKey;
  name: string;
}

export interface LecturerSuggestion {
  lecturer_id: number;
  lecturer_code: string;
  lecturer_name: string;
  unit_name: string | null;
}

export interface WorkSearchLookupsDTO {
  faculties: FacultyOption[];
  departments: DepartmentOption[];
  work_types: WorkTypeOption[];
  author_roles: AuthorRoleOption[];
  statuses: StatusOption[];
  management_levels: ManagementLevelOption[];
  years: number[];
}

/** ===== Filter (UI) ===== */
export interface GlobalResearchWorkSearchFilter {
  keyword: string;
  lecturerKeyword: string;
  facultyId: number | null;
  departmentId: number | null;
  workTypeId: number | null;
  authorRole: LecturerRoleKey | null;
  yearFrom: number | null;
  yearTo: number | null;
  status: ResearchWorkStatusCode | string | null;
  managementLevel: ManagementLevelKey | null;
}

/** ===== Filter DTO (snake_case) ===== */
export interface GlobalResearchWorkSearchFilterDTO {
  q?: string;
  lecturer_q?: string;
  faculty_id?: number | null;
  department_id?: number | null;
  work_type_id?: number | null;
  author_role?: string | null;
  year_from?: number | null;
  year_to?: number | null;
  status?: string | null;
  management_level?: string | null;
  page?: number;
  per_page?: number;
}

export function filterToDto(
  filter: GlobalResearchWorkSearchFilter,
  page?: number,
  perPage?: number
): GlobalResearchWorkSearchFilterDTO {
  return {
    q: filter.keyword || undefined,
    lecturer_q: filter.lecturerKeyword || undefined,
    faculty_id: filter.facultyId,
    department_id: filter.departmentId,
    work_type_id: filter.workTypeId,
    author_role: filter.authorRole,
    year_from: filter.yearFrom,
    year_to: filter.yearTo,
    status: filter.status,
    management_level: filter.managementLevel,
    page,
    per_page: perPage,
  };
}

/** ===== Summary DTO / UI ===== */
export interface ResearchWorkSummaryDTO {
  work_id: number;
  title: string;
  subtitle: string;
  kind_code: ResearchWorkTypeKey | string;
  kind_name: string;
  status_code: ResearchWorkStatusCode | string;
  status_name: string;
  main_lecturer_id: number | null;
  main_lecturer_code: string | null;
  main_lecturer_name: string | null;
  faculty_id: number | null;
  faculty_name: string | null;
  department_id: number | null;
  department_name: string | null;
  year: number | null;
}

export interface ResearchWorkSummary {
  workId: number;
  title: string;
  subtitle: string;
  typeKey: ResearchWorkTypeKey | string;
  typeLabel: string;
  statusCode: ResearchWorkStatusCode | string;
  statusLabel: string;
  mainLecturerName: string;
  mainLecturerCode: string;
  unitName: string;
  year: number | null;
}

export function summaryFromDto(dto: ResearchWorkSummaryDTO): ResearchWorkSummary {
  const unitName = dto.faculty_name || dto.department_name || "";

  return {
    workId: dto.work_id,
    title: dto.title,
    subtitle: dto.subtitle,
    typeKey: dto.kind_code,
    typeLabel: dto.kind_name,
    statusCode: dto.status_code,
    statusLabel: dto.status_name,
    mainLecturerName: dto.main_lecturer_name || "",
    mainLecturerCode: dto.main_lecturer_code || "",
    unitName,
    year: dto.year,
  };
}

/** ===== Detail DTO / UI ===== */
export type ResearchWorkFileKind = "file" | "link";

export interface ResearchWorkFileDTO {
  file_id: string | number;
  kind: ResearchWorkFileKind;
  label: string;
  url: string;
  file_name?: string | null;
  mime_type?: string | null;
  size_bytes?: number | null;
}

export interface ResearchWorkParticipantDTO {
  lecturer_id: number;
  lecturer_code: string;
  lecturer_name: string;
  unit_name: string | null;
  role_key: string;
  role_label: string;
}

export interface ResearchWorkInfoRowDTO {
  label: string;
  value: string;
}

export interface ResearchWorkDetailDTO {
  work_id: number;
  title: string;
  kind_code: ResearchWorkTypeKey | string;
  kind_name: string;
  status_code: ResearchWorkStatusCode | string;
  status_name: string;
  year: number | null;
  abstract: string | null;
  info_rows: ResearchWorkInfoRowDTO[];
  participants: ResearchWorkParticipantDTO[];
  files: ResearchWorkFileDTO[];
}

export interface ResearchWorkFile {
  fileId: string | number;
  kind: ResearchWorkFileKind;
  label: string;
  url: string;
  fileName?: string | null;
  mimeType?: string | null;
  sizeBytes?: number | null;
}

export interface ResearchWorkParticipant {
  lecturerId: number;
  lecturerCode: string;
  lecturerName: string;
  unitName: string;
  roleKey: string;
  roleLabel: string;
}

export interface ResearchWorkInfoRow {
  label: string;
  value: string;
}

export interface ResearchWorkDetail {
  workId: number;
  title: string;
  typeKey: ResearchWorkTypeKey | string;
  typeLabel: string;
  statusCode: ResearchWorkStatusCode | string;
  statusLabel: string;
  year: number | null;
  abstract: string | null;
  infoRows: ResearchWorkInfoRow[];
  participants: ResearchWorkParticipant[];
  files: ResearchWorkFile[];
}

export function detailFromDto(dto: ResearchWorkDetailDTO): ResearchWorkDetail {
  return {
    workId: dto.work_id,
    title: dto.title,
    typeKey: dto.kind_code,
    typeLabel: dto.kind_name,
    statusCode: dto.status_code,
    statusLabel: dto.status_name,
    year: dto.year,
    abstract: dto.abstract,
    infoRows: dto.info_rows.map((r) => ({ label: r.label, value: r.value })),
    participants: dto.participants.map((p) => ({
      lecturerId: p.lecturer_id,
      lecturerCode: p.lecturer_code,
      lecturerName: p.lecturer_name,
      unitName: p.unit_name || "",
      roleKey: p.role_key,
      roleLabel: p.role_label,
    })),
    files: dto.files.map((f) => ({
      fileId: f.file_id,
      kind: f.kind,
      label: f.label,
      url: f.url,
      fileName: f.file_name ?? null,
      mimeType: f.mime_type ?? null,
      sizeBytes: f.size_bytes ?? null,
    })),
  };
}

/** ===== Helpers ===== */
export function normalizeText(value: string): string {
  return value.trim().toLowerCase();
}

export function statusLabel(status: ResearchWorkStatusCode | string): string {
  if (status === "approved") return "Đã duyệt";
  if (status === "submitted") return "Chờ duyệt";
  if (status === "rejected") return "Bị từ chối";
  if (status === "draft") return "Nháp";
  return "Không xác định";
}

export function statusPillClass(status: ResearchWorkStatusCode | string): string {
  if (status === "approved")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (status === "submitted")
    return "bg-amber-50 text-amber-700 ring-amber-200";
  if (status === "draft") return "bg-slate-50 text-slate-700 ring-slate-200";
  return "bg-rose-50 text-rose-700 ring-rose-200";
}

export function typeLabel(typeKey: ResearchWorkTypeKey | string): string {
  if (typeKey === "paper") return "Bài báo";
  if (typeKey === "project") return "Đề tài";
  if (typeKey === "book") return "Sách/Giáo trình";
  if (typeKey === "conference") return "Hội nghị/Hội thảo";
  return "Khác";
}

export function typeBadgeClass(typeKey: ResearchWorkTypeKey | string): string {
  if (typeKey === "paper") return "bg-sky-50 text-sky-700 ring-sky-200";
  if (typeKey === "project")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (typeKey === "book") return "bg-violet-50 text-violet-700 ring-violet-200";
  return "bg-amber-50 text-amber-700 ring-amber-200";
}
