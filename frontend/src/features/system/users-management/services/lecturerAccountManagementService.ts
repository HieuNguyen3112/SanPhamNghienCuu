import type {
  AssignRolesPayload,
  LecturerAccountDTO,
  LecturerAccountFilterState,
  LecturerAccountScope,
  ToggleAccountStatusPayload,
  UnitOptionDTO,
  UpdateLecturerAccountPayload,
} from "../contracts/lecturerAccountManagement.contract";
import { searchQueryToDto } from "../contracts/lecturerAccountManagement.contract";
import {
  lecturerAccountsMockDTO,
  resolveUnitName,
  unitOptionsMockDTO,
} from "../mock-data/lecturerAccounts.mock";

function delay<T>(data: T, ms = 320): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(data), ms));
}

/**
 * In mock, we treat `unit_id` as the faculty/department id.
 * Faculty scope can only see a fixed unit.
 */
const DEFAULT_FACULTY_UNIT_ID = 1; // TODO: derive from auth context

function normalizeKeyword(value: string): string {
  return value.trim().toLowerCase();
}

function includesKw(haystack: string, kw: string): boolean {
  if (!kw) return true;
  return haystack.toLowerCase().includes(kw);
}

function roleIntersect(list: readonly string[], selected: readonly string[]) {
  if (selected.length === 0) return true;
  return selected.some((x) => list.includes(x));
}

function statusMatch(dtoStatus: "ACTIVE" | "INACTIVE", filter: string) {
  if (filter === "all") return true;
  if (filter === "active") return dtoStatus === "ACTIVE";
  return dtoStatus === "INACTIVE";
}

function applyFilter(
  rows: LecturerAccountDTO[],
  filter: LecturerAccountFilterState
): LecturerAccountDTO[] {
  const q = searchQueryToDto(filter);
  const kw = normalizeKeyword(q.keyword);

  return rows.filter((r) => {
    const matchKw = includesKw(
      `${r.full_name} ${r.email} ${r.lecturer_code} ${r.username}`,
      kw
    );

    const matchUnit = q.unit_id == null ? true : r.unit_id === q.unit_id;
    const matchStatus = statusMatch(r.status, q.status);
    const matchRole = roleIntersect(r.role_keys, q.role_keys);

    return matchKw && matchUnit && matchStatus && matchRole;
  });
}

/**
 * Mock in-memory "DB".
 * NOTE: This will reset on page refresh, which is fine for FE mock.
 */
let mockDb: LecturerAccountDTO[] = lecturerAccountsMockDTO.map((x) => ({
  ...x,
}));

export interface LecturerAccountManagementService {
  getUnitOptionsDTO(): Promise<UnitOptionDTO[]>;
  searchLecturerAccountsDTO(
    filter: LecturerAccountFilterState
  ): Promise<LecturerAccountDTO[]>;
  updateLecturerAccountDTO(
    payload: UpdateLecturerAccountPayload
  ): Promise<void>;
  assignRolesDTO(payload: AssignRolesPayload): Promise<void>;
  toggleAccountStatusDTO(payload: ToggleAccountStatusPayload): Promise<void>;
}

export function createLecturerAccountManagementService(params: {
  scope: LecturerAccountScope;
  faculty_unit_id?: number;
}): LecturerAccountManagementService {
  const facultyUnitId = params.faculty_unit_id ?? DEFAULT_FACULTY_UNIT_ID;

  function scopeRows(rows: LecturerAccountDTO[]): LecturerAccountDTO[] {
    if (params.scope === "FACULTY") {
      return rows.filter((r) => r.unit_id === facultyUnitId);
    }
    return rows;
  }

  return {
    async getUnitOptionsDTO() {
      const list =
        params.scope === "FACULTY"
          ? unitOptionsMockDTO.filter((u) => u.id === facultyUnitId)
          : unitOptionsMockDTO;
      return delay(list.map((x) => ({ ...x })));
    },

    async searchLecturerAccountsDTO(filter) {
      const scoped = scopeRows(mockDb);
      const filtered = applyFilter(scoped, filter);
      // newest updated first
      const sorted = [...filtered].sort((a, b) =>
        b.updated_at.localeCompare(a.updated_at)
      );
      return delay(sorted.map((x) => ({ ...x })));
    },

    async updateLecturerAccountDTO(payload) {
      const idx = mockDb.findIndex((x) => x.id === payload.id);
      if (idx < 0) throw new Error("Không tìm thấy giảng viên.");

      // Faculty scope safety: do not allow moving outside faculty unit
      if (params.scope === "FACULTY" && payload.unit_id !== facultyUnitId) {
        throw new Error("BCN Khoa không thể chuyển giảng viên sang khoa khác.");
      }

      const prev = mockDb[idx]!;
      mockDb[idx] = {
        ...prev,
        full_name: payload.full_name,
        email: payload.email,
        unit_id: payload.unit_id,
        unit_name: resolveUnitName(payload.unit_id),
        position_title: payload.position_title,
        updated_at: new Date().toISOString(),
        updated_by: "mock-user",
      };
      await delay(undefined);
    },

    async assignRolesDTO(payload) {
      const idx = mockDb.findIndex((x) => x.id === payload.id);
      if (idx < 0) throw new Error("Không tìm thấy giảng viên.");

      // Faculty scope safety: BCN khoa usually can't grant SCIENCE_OFFICE role
      if (
        params.scope === "FACULTY" &&
        payload.role_keys.includes("SCIENCE_OFFICE")
      ) {
        throw new Error(
          "BCN Khoa không thể gán quyền cấp trường (QLKH/Admin)."
        );
      }

      const prev = mockDb[idx]!;
      mockDb[idx] = {
        ...prev,
        role_keys: [...payload.role_keys],
        updated_at: new Date().toISOString(),
        updated_by: "mock-user",
      };
      await delay(undefined);
    },

    async toggleAccountStatusDTO(payload) {
      const idx = mockDb.findIndex((x) => x.id === payload.id);
      if (idx < 0) throw new Error("Không tìm thấy giảng viên.");
      const prev = mockDb[idx]!;

      mockDb[idx] = {
        ...prev,
        status: payload.next_status,
        updated_at: new Date().toISOString(),
        updated_by: "mock-user",
      };
      // payload.reason intentionally ignored in mock
      await delay(undefined);
    },
  };
}
