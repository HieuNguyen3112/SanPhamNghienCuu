// src/features/works/types.ts
import type { WorkType } from "@/features/declarations/types";

/* ========= 1. Công trình của cá nhân giảng viên ========= */

export type WorkStatus =
  | "ONGOING" // đang thực hiện
  | "COMPLETED" // đã hoàn thành
  | "PENDING_APPROVAL" // chờ duyệt
  | "DRAFT"; // bản nháp / chưa gửi duyệt

export interface WorkItem {
  id: string;
  title: string;
  workType: WorkType;
  status: WorkStatus;
  startYear?: number | null;
  endYear?: number | null;
  role: "" | "MAIN" | "CO";
  totalHours?: number | null; // tổng giờ NCKH được tính
  approved?: boolean;
  // sau này có thể thêm các field như: department, code, journalName, ...
}

export interface FetchMyWorksParams {
  status?: WorkStatus | "ALL";
  search?: string;
  workType?: WorkType | "ALL";
  page?: number;
  pageSize?: number;
}

export interface PagedResult<T> {
  items: T[];
  totalItems: number;
  page: number;
  pageSize: number;
}

/* ========= 2. Tổng hợp công trình theo giảng viên (QLKH / BCN) ========= */

export type SemesterValue = "all" | "HK1" | "HK2" | "HK3";
export type ScopeValue = "faculty" | "university";

export interface SelectOption<T = string> {
  label: string;
  value: T;
}

export interface ScopeOption extends SelectOption<ScopeValue> {}

export interface DepartmentOption {
  id: string;
  name: string;
}

export interface ProjectsFilter {
  academicYear: string;
  semester: SemesterValue;
  scope: ScopeValue | "";
  departmentId: string; // 'all' | departmentId
  lecturerName: string;
}

export interface Lecturer {
  id: string;
  code: string;
  fullName: string;
  departmentName: string;
}

export type ProjectStatus = "approved" | "pending" | "rejected";

export interface ResearchProject {
  id: string;
  title: string;
  lecturerId: string;
  academicYear: string;
  semester: SemesterValue;
  status: ProjectStatus;
  hours: number;
}

export interface LecturerProjectsSummary {
  lecturer: Lecturer;
  totalProjects: number;
  approvedProjects: number;
  pendingProjects: number;
  rejectedProjects: number;
  totalHours: number;
}

export interface OverviewStats {
  lecturerCount: number;
  totalProjectsCount: number;
  pendingProjectsCount: number;
}

export interface PaginationState {
  page: number;
  pageSize: number;
  totalItems: number;
}

export interface FetchOverviewParams {
  filters: ProjectsFilter;
  pagination: PaginationState;
}

export interface FetchOverviewResult {
  items: LecturerProjectsSummary[];
  stats: OverviewStats;
  pagination: PaginationState;
}
