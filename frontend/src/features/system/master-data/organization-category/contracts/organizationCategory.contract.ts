// src/features/organization-category/contracts/organizationCategory.contract.ts

export type TabKey = "faculties" | "departments";

/** ========= DTOs (snake_case) ========= */
export interface FacultyDTO {
  id: number;
  code: string; // VARCHAR50
  name: string; // VARCHAR255
  created_at: string;
  updated_at: string;
  lecturers_count?: number;
}

export interface FacultyOptionDTO {
  id: number;
  code: string;
  name: string;
}

export interface DepartmentDTO {
  id: number;
  faculty_id: number; // FK faculties.id
  code: string; // VARCHAR50 (unique within faculty)
  name: string; // VARCHAR255
  created_at: string;
  updated_at: string;
  lecturers_count?: number;
  faculty?: FacultyOptionDTO | null;
}

/** ========= Upsert payloads (snake_case) ========= */
export interface FacultyUpsertDTO {
  code: string;
  name: string;
}

export interface DepartmentUpsertDTO {
  faculty_id: number;
  code: string;
  name: string;
}

export interface PaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface FacultyListResponseDTO {
  items: FacultyDTO[];
  pagination: PaginationDTO;
}

export interface DepartmentListResponseDTO {
  items: DepartmentDTO[];
  pagination: PaginationDTO;
}

/** ========= UI Models (camelCase) ========= */
export interface Faculty {
  id: number;
  code: string;
  name: string;
  createdAt: string;
  updatedAt: string;
  lecturersCount: number;
}

export interface FacultyOption {
  id: number;
  code: string;
  name: string;
}

export interface Department {
  id: number;
  facultyId: number;
  code: string;
  name: string;
  createdAt: string;
  updatedAt: string;
  lecturersCount: number;

  /** join display only */
  facultyName?: string;
  facultyCode?: string;
}

/** ========= Mappers ========= */
export function facultyFromDto(dto: FacultyDTO): Faculty {
  return {
    id: dto.id,
    code: dto.code,
    name: dto.name,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
    lecturersCount: dto.lecturers_count ?? 0,
  };
}

export function facultyOptionFromDto(dto: FacultyOptionDTO): FacultyOption {
  return {
    id: dto.id,
    code: dto.code,
    name: dto.name,
  };
}

export function departmentFromDto(dto: DepartmentDTO): Department {
  return {
    id: dto.id,
    facultyId: dto.faculty_id,
    code: dto.code,
    name: dto.name,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
    lecturersCount: dto.lecturers_count ?? 0,
    facultyName: dto.faculty?.name,
    facultyCode: dto.faculty?.code,
  };
}

export function facultyToUpsertDto(
  input: Pick<Faculty, "code" | "name">
): FacultyUpsertDTO {
  return {
    code: input.code.trim(),
    name: input.name.trim(),
  };
}

export function departmentToUpsertDto(input: {
  facultyId: number;
  code: string;
  name: string;
}): DepartmentUpsertDTO {
  return {
    faculty_id: input.facultyId,
    code: input.code.trim(),
    name: input.name.trim(),
  };
}

/** ========= Helpers ========= */
export function formatDateTime(value: string | null | undefined): string {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "—";
  return new Intl.DateTimeFormat("vi-VN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(d);
}
