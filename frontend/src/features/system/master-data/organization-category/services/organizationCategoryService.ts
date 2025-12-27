// src/features/organization-category/services/organizationCategoryService.ts
import type {
  FacultyDTO,
  FacultyUpsertDTO,
  DepartmentDTO,
  DepartmentUpsertDTO,
} from "../contracts/organizationCategory.contract";
import {
  facultiesMock,
  departmentsMock,
} from "../mock-data/organizationCategory.mock";

function sleep(ms: number) {
  return new Promise((r) => setTimeout(r, ms));
}

/**
 * TODO(API):
 * - GET /api/catalog/faculties
 * - POST /api/catalog/faculties
 * - PUT /api/catalog/faculties/{id}
 * - GET /api/catalog/departments
 * - POST /api/catalog/departments
 * - PUT /api/catalog/departments/{id}
 *
 * Note: Không có delete theo yêu cầu nghiệp vụ.
 */
export class OrganizationCategoryService {
  private faculties: FacultyDTO[] = structuredClone(facultiesMock);
  private departments: DepartmentDTO[] = structuredClone(departmentsMock);

  async listFaculties(): Promise<FacultyDTO[]> {
    await sleep(350);
    return structuredClone(this.faculties).sort((a, b) =>
      a.updated_at < b.updated_at ? 1 : -1
    );
  }

  async createFaculty(payload: FacultyUpsertDTO): Promise<FacultyDTO> {
    await sleep(450);

    // Backend should enforce unique(code). Mock check:
    if (
      this.faculties.some(
        (f) => f.code.toLowerCase() === payload.code.toLowerCase()
      )
    ) {
      throw new Error("Mã khoa đã tồn tại (unique).");
    }

    const now = new Date().toISOString();
    const nextId = Math.max(...this.faculties.map((x) => x.id), 0) + 1;

    const created: FacultyDTO = {
      id: nextId,
      code: payload.code,
      name: payload.name,
      created_at: now,
      updated_at: now,
    };
    this.faculties = [created, ...this.faculties];
    return structuredClone(created);
  }

  async updateFaculty(
    id: number,
    payload: FacultyUpsertDTO
  ): Promise<FacultyDTO> {
    await sleep(450);

    const idx = this.faculties.findIndex((x) => x.id === id);
    if (idx < 0) throw new Error("Không tìm thấy khoa.");

    const current = this.faculties[idx];
    if (!current) throw new Error("Không tìm thấy khoa."); // ✅ narrow (TS)

    const conflict = this.faculties.some(
      (f) => f.id !== id && f.code.toLowerCase() === payload.code.toLowerCase()
    );
    if (conflict) throw new Error("Mã khoa đã tồn tại (unique).");

    const now = new Date().toISOString();

    const updated: FacultyDTO = {
      ...current, // ✅ giờ current chắc chắn là FacultyDTO
      code: payload.code,
      name: payload.name,
      updated_at: now,
    };

    this.faculties.splice(idx, 1, updated);
    return structuredClone(updated);
  }

  async listDepartments(): Promise<DepartmentDTO[]> {
    await sleep(350);
    return structuredClone(this.departments).sort((a, b) =>
      a.updated_at < b.updated_at ? 1 : -1
    );
  }

  async createDepartment(payload: DepartmentUpsertDTO): Promise<DepartmentDTO> {
    await sleep(450);

    // Backend unique(faculty_id, code). Mock check:
    if (
      this.departments.some(
        (d) =>
          d.faculty_id === payload.faculty_id &&
          d.code.toLowerCase() === payload.code.toLowerCase()
      )
    ) {
      throw new Error(
        "Mã đơn vị đã tồn tại trong khoa (unique(faculty_id, code))."
      );
    }

    const now = new Date().toISOString();
    const nextId = Math.max(...this.departments.map((x) => x.id), 0) + 1;

    const created: DepartmentDTO = {
      id: nextId,
      faculty_id: payload.faculty_id,
      code: payload.code,
      name: payload.name,
      created_at: now,
      updated_at: now,
    };
    this.departments = [created, ...this.departments];
    return structuredClone(created);
  }

  async updateDepartment(
    id: number,
    payload: DepartmentUpsertDTO
  ): Promise<DepartmentDTO> {
    await sleep(450);

    const idx = this.departments.findIndex((x) => x.id === id);
    if (idx < 0) throw new Error("Không tìm thấy đơn vị.");

    const current = this.departments[idx];
    if (!current) throw new Error("Không tìm thấy đơn vị."); // ✅ narrow (TS)

    const conflict = this.departments.some(
      (d) =>
        d.id !== id &&
        d.faculty_id === payload.faculty_id &&
        d.code.toLowerCase() === payload.code.toLowerCase()
    );
    if (conflict)
      throw new Error(
        "Mã đơn vị đã tồn tại trong khoa (unique(faculty_id, code))."
      );

    const now = new Date().toISOString();

    const updated: DepartmentDTO = {
      ...current,
      faculty_id: payload.faculty_id,
      code: payload.code,
      name: payload.name,
      updated_at: now,
    };

    this.departments.splice(idx, 1, updated);
    return structuredClone(updated);
  }
}
