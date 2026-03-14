/**
 * Lecturer Account Management - Contract
 *
 * - DTO is snake_case (simulating backend)
 * - UI model is camelCase
 */

export type LecturerAccountScope = "FACULTY" | "UNIVERSITY";

export type AccountStatus = "ACTIVE" | "INACTIVE";

export type AccountStatusFilter = "all" | "active" | "inactive";

export type RoleKey =
  | "LECTURER" // GV
  | "DEPARTMENT_BOARD" // DL (BCN khoa)
  | "SCIENCE_OFFICE"; // QL/ADMIN (cấp trường)

export interface UnitOptionDTO {
  id: number;
  name: string;
}

export interface LecturerAccountDTO {
  id: number;

  lecturer_code: string;
  full_name: string;

  email: string;
  username: string;

  unit_id: number;
  unit_name: string;
  faculty_id?: number | null;
  faculty_name?: string | null;

  role_keys: RoleKey[];

  status: AccountStatus;

  position_title: string | null;

  updated_at: string; // ISO
  updated_by: string | null;
}

/** UI model */
export interface LecturerAccount {
  id: number;

  lecturerCode: string;
  fullName: string;

  email: string;
  username: string;

  unitId: number;
  unitName: string;
  facultyId: number | null;
  facultyName: string | null;

  roleKeys: RoleKey[];

  status: AccountStatus;

  positionTitle: string | null;

  updatedAt: string;
  updatedBy: string | null;
}

export interface UnitOption {
  id: number;
  name: string;
}

export interface RoleOption {
  key: RoleKey;
  label: string;
  description?: string;
}

export const DEFAULT_ROLE_OPTIONS: RoleOption[] = [
  {
    key: "LECTURER",
    label: "Giảng viên",
    description: "Quyền kê khai và theo dõi công trình cá nhân.",
  },
  {
    key: "DEPARTMENT_BOARD",
    label: "BCN Khoa",
    description: "Quyền duyệt và giám sát công trình trong phạm vi khoa.",
  },
];

export interface LecturerAccountFilterState {
  keyword: string;
  unitId: number | "ALL";
  roleKeys: RoleKey[];
  status: AccountStatusFilter;
}

export function defaultFilterState(): LecturerAccountFilterState {
  return {
    keyword: "",
    unitId: "ALL",
    roleKeys: [],
    status: "all",
  };
}

/** Query DTO (snake_case) - đề xuất cho BE */
export interface LecturerAccountSearchQueryDTO {
  keyword: string;
  unit_id: number | null;
  faculty_id?: number | null;
  role_keys: RoleKey[];
  status: AccountStatusFilter;
}

export function searchQueryToDto(
  state: LecturerAccountFilterState,
): LecturerAccountSearchQueryDTO {
  return {
    keyword: state.keyword.trim(),
    unit_id: state.unitId === "ALL" ? null : state.unitId,
    role_keys: [...state.roleKeys],
    status: state.status,
  };
}

/** Payloads (snake_case) */
export interface UpdateLecturerAccountPayload {
  id: number;
  full_name: string;
  email: string;
  unit_id: number;
  position_title: string | null;
}

export interface CreateLecturerAccountPayload {
  lecturer_code: string;
  full_name: string;
  email: string;
  unit_id?: number | null;
  faculty_id?: number | null;
  phone_number: string | null;
  academic_title: string | null;
  status: AccountStatus;
}

export interface AssignRolesPayload {
  id: number;
  role_keys: RoleKey[];
}

export interface ToggleAccountStatusPayload {
  id: number;
  is_active: boolean;
  reason: string | null;
}

export interface LecturerAccountPaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface LecturerAccountListResponseDTO {
  items: LecturerAccountDTO[];
  pagination: LecturerAccountPaginationDTO;
}

export interface StatusOption {
  key: AccountStatus;
  label: string;
}

export interface LecturerAccountLookupsDTO {
  units: UnitOptionDTO[];
  faculties?: UnitOptionDTO[];
  roles: RoleOption[];
  statuses: StatusOption[];
}

/** Mapper DTO -> UI */
export function lecturerAccountFromDto(
  dto: LecturerAccountDTO,
): LecturerAccount {
  return {
    id: dto.id,
    lecturerCode: dto.lecturer_code,
    fullName: dto.full_name,
    email: dto.email,
    username: dto.username,
    unitId: dto.unit_id,
    unitName: dto.unit_name,
    facultyId: dto.faculty_id ?? null,
    facultyName: dto.faculty_name ?? null,
    roleKeys: dto.role_keys,
    status: dto.status,
    positionTitle: dto.position_title,
    updatedAt: dto.updated_at,
    updatedBy: dto.updated_by,
  };
}

export function unitOptionFromDto(dto: UnitOptionDTO): UnitOption {
  return { id: dto.id, name: dto.name };
}
