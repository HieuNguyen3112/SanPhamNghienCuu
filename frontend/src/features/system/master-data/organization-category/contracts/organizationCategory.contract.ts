// src/features/organization-category/contracts/organizationCategory.contract.ts

export type TabKey = "faculties" | "departments";

/** ========= DTOs (snake_case) — KHỚP schema ========= */
export interface FacultyDTO {
  id: number;
  code: string; // VARCHAR50
  name: string; // VARCHAR255
  created_at: string;
  updated_at: string;
}

export interface DepartmentDTO {
  id: number;
  faculty_id: number; // FK faculties.id
  code: string; // VARCHAR50 (unique within faculty)
  name: string; // VARCHAR255
  created_at: string;
  updated_at: string;
}

/** ========= Upsert payloads (snake_case) — chỉ dùng cột thật ========= */
export interface FacultyUpsertDTO {
  code: string;
  name: string;
}

export interface DepartmentUpsertDTO {
  faculty_id: number;
  code: string;
  name: string;
}

/** ========= UI Models (camelCase) ========= */
export interface Faculty {
  id: number;
  code: string;
  name: string;
  createdAt: string;
  updatedAt: string;
}

export interface Department {
  id: number;
  facultyId: number;
  code: string;
  name: string;
  createdAt: string;
  updatedAt: string;

  /** join display only */
  facultyName?: string;
}

/** ========= Mappers ========= */
export function facultyFromDto(dto: FacultyDTO): Faculty {
  return {
    id: dto.id,
    code: dto.code,
    name: dto.name,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
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
