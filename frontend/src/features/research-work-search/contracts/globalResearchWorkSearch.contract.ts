import type { LucideIcon } from "lucide-vue-next";

/** ===== Enums (UI) ===== */
export type ResearchWorkTypeKey = "article" | "project" | "book" | "conference";
export type ResearchWorkStatusCode = "approved" | "submitted" | "rejected";
export type LecturerRoleKey = "lead" | "coauthor" | "member";
export type ManagementLevelKey =
  | "faculty"
  | "university"
  | "ministry"
  | "other";

/** ===== Options ===== */
export interface FacultyOption {
  id: number;
  name: string;
}

export interface LecturerSuggestion {
  lecturer_id: number;
  lecturer_code: string;
  lecturer_name: string;
  faculty_name: string;
}

/** ===== Filter (UI) ===== */
export interface GlobalResearchWorkSearchFilter {
  keyword: string;
  lecturerKeyword: string; // free text / suggestion pick
  facultyId: number | null;
  typeKey: ResearchWorkTypeKey | "all";
  roleKey: LecturerRoleKey | "all";
  yearFrom: number | null;
  yearTo: number | null;
  statusCode: ResearchWorkStatusCode | "all";
  managementLevel: ManagementLevelKey | "all";
}

/** ===== Filter DTO (snake_case) ===== */
export interface GlobalResearchWorkSearchFilterDTO {
  keyword: string;
  lecturer_keyword: string;
  faculty_id: number | null;
  type_key: ResearchWorkTypeKey | "all";
  role_key: LecturerRoleKey | "all";
  year_from: number | null;
  year_to: number | null;
  status_code: ResearchWorkStatusCode | "all";
  management_level: ManagementLevelKey | "all";
}

export function filterToDto(
  filter: GlobalResearchWorkSearchFilter
): GlobalResearchWorkSearchFilterDTO {
  return {
    keyword: filter.keyword,
    lecturer_keyword: filter.lecturerKeyword,
    faculty_id: filter.facultyId,
    type_key: filter.typeKey,
    role_key: filter.roleKey,
    year_from: filter.yearFrom,
    year_to: filter.yearTo,
    status_code: filter.statusCode,
    management_level: filter.managementLevel,
  };
}

/** ===== Summary DTO / UI ===== */
export interface ResearchWorkSummaryDTO {
  work_id: number;
  title: string;
  subtitle: string; // journal/conf/publisher/level short line
  type_key: ResearchWorkTypeKey;
  faculty_name: string;
  primary_lecturer_name: string;
  primary_lecturer_code: string;
  year: number;
  status_code: ResearchWorkStatusCode;
  has_public_pdf: boolean;
}

export interface ResearchWorkSummary {
  workId: number;
  title: string;
  subtitle: string;
  typeKey: ResearchWorkTypeKey;
  facultyName: string;
  primaryLecturerName: string;
  primaryLecturerCode: string;
  year: number;
  statusCode: ResearchWorkStatusCode;
  hasPublicPdf: boolean;
}

export function summaryFromDto(
  dto: ResearchWorkSummaryDTO
): ResearchWorkSummary {
  return {
    workId: dto.work_id,
    title: dto.title,
    subtitle: dto.subtitle,
    typeKey: dto.type_key,
    facultyName: dto.faculty_name,
    primaryLecturerName: dto.primary_lecturer_name,
    primaryLecturerCode: dto.primary_lecturer_code,
    year: dto.year,
    statusCode: dto.status_code,
    hasPublicPdf: dto.has_public_pdf,
  };
}

/** ===== Detail DTO / UI ===== */
export type ResearchWorkFileKind = "pdf" | "link";

export interface ResearchWorkFileDTO {
  file_id: number;
  kind: ResearchWorkFileKind;
  label: string;
  url: string;
}

export interface ResearchWorkParticipantDTO {
  name: string;
  faculty_name: string;
  role_label: string;
}

export interface ResearchWorkInfoRowDTO {
  label: string;
  value: string;
}

export interface ResearchWorkDetailDTO {
  work_id: number;
  title: string;
  type_key: ResearchWorkTypeKey;
  status_code: ResearchWorkStatusCode;
  year: number;

  abstract: string | null;

  info_rows: ResearchWorkInfoRowDTO[];
  participants: ResearchWorkParticipantDTO[];
  files: ResearchWorkFileDTO[];
}

export interface ResearchWorkFile {
  fileId: number;
  kind: ResearchWorkFileKind;
  label: string;
  url: string;
}

export interface ResearchWorkParticipant {
  name: string;
  facultyName: string;
  roleLabel: string;
}

export interface ResearchWorkInfoRow {
  label: string;
  value: string;
}

export interface ResearchWorkDetail {
  workId: number;
  title: string;
  typeKey: ResearchWorkTypeKey;
  statusCode: ResearchWorkStatusCode;
  year: number;

  abstract: string | null;

  infoRows: ResearchWorkInfoRow[];
  participants: ResearchWorkParticipant[];
  files: ResearchWorkFile[];
}

export function detailFromDto(dto: ResearchWorkDetailDTO): ResearchWorkDetail {
  return {
    workId: dto.work_id,
    title: dto.title,
    typeKey: dto.type_key,
    statusCode: dto.status_code,
    year: dto.year,
    abstract: dto.abstract,
    infoRows: dto.info_rows.map((r) => ({ label: r.label, value: r.value })),
    participants: dto.participants.map((p) => ({
      name: p.name,
      facultyName: p.faculty_name,
      roleLabel: p.role_label,
    })),
    files: dto.files.map((f) => ({
      fileId: f.file_id,
      kind: f.kind,
      label: f.label,
      url: f.url,
    })),
  };
}

/** ===== Helpers ===== */
export function normalizeText(value: string): string {
  return value.trim().toLowerCase();
}

export function formatDateVietnameseFromYear(year: number): string {
  return String(year);
}

export function statusLabel(status: ResearchWorkStatusCode): string {
  if (status === "approved") return "Đã duyệt";
  if (status === "submitted") return "Chờ duyệt";
  return "Bị từ chối";
}

export function statusPillClass(status: ResearchWorkStatusCode): string {
  if (status === "approved")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (status === "submitted")
    return "bg-amber-50 text-amber-700 ring-amber-200";
  return "bg-rose-50 text-rose-700 ring-rose-200";
}

export function typeLabel(typeKey: ResearchWorkTypeKey): string {
  if (typeKey === "article") return "Bài báo";
  if (typeKey === "project") return "Đề tài";
  if (typeKey === "book") return "Sách/Giáo trình";
  return "Hội thảo";
}

export function typeBadgeClass(typeKey: ResearchWorkTypeKey): string {
  if (typeKey === "article") return "bg-sky-50 text-sky-700 ring-sky-200";
  if (typeKey === "project")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (typeKey === "book") return "bg-violet-50 text-violet-700 ring-violet-200";
  return "bg-amber-50 text-amber-700 ring-amber-200";
}

/** Optional: UI can map icon by type */
export interface TypeIconMap {
  [key: string]: LucideIcon | undefined;
}
