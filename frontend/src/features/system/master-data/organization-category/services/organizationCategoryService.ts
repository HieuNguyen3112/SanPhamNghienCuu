// src/features/organization-category/services/organizationCategoryService.ts
import type {
  DepartmentDTO,
  DepartmentListResponseDTO,
  DepartmentUpsertDTO,
  FacultyDTO,
  FacultyListResponseDTO,
  FacultyOptionDTO,
  FacultyUpsertDTO,
} from "../contracts/organizationCategory.contract";
import {
  createDepartmentApi,
  createFacultyApi,
  createFacultyDepartmentApi,
  fetchFacultyOptionsApi,
  fetchFacultyOrgStructureLookupsApi,
  listDepartmentsApi,
  listFacultyDepartmentsApi,
  listFacultyFacultiesApi,
  listFacultiesApi,
  updateDepartmentApi,
  updateFacultyApi,
  updateFacultyDepartmentApi,
} from "../api/orgStructureApi";

function resolveApiErrorMessage(error: unknown, fallback: string): string {
  if (typeof error === "object" && error !== null) {
    const anyError = error as {
      message?: string;
      response?: { data?: { message?: string } };
    };
    return anyError.response?.data?.message || anyError.message || fallback;
  }
  return fallback;
}

export class OrganizationCategoryService {
  private readonly isFacultyScope: boolean;

  constructor(scope: "FACULTY" | "UNIVERSITY" = "UNIVERSITY") {
    this.isFacultyScope = scope === "FACULTY";
  }

  async listFaculties(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<FacultyListResponseDTO> {
    try {
      return this.isFacultyScope
        ? await listFacultyFacultiesApi(params)
        : await listFacultiesApi(params);
    } catch (error) {
      throw new Error(
        resolveApiErrorMessage(error, "Không tải được danh sách khoa.")
      );
    }
  }

  async createFaculty(payload: FacultyUpsertDTO): Promise<FacultyDTO> {
    try {
      if (this.isFacultyScope) {
        throw new Error("Không có quyền tạo khoa.");
      }
      return await createFacultyApi(payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Không thể tạo khoa."));
    }
  }

  async updateFaculty(id: number, payload: FacultyUpsertDTO): Promise<FacultyDTO> {
    try {
      if (this.isFacultyScope) {
        throw new Error("Không có quyền cập nhật khoa.");
      }
      return await updateFacultyApi(id, payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Không thể cập nhật khoa."));
    }
  }

  async listDepartments(params: {
    keyword?: string;
    faculty_id?: number;
    page?: number;
    per_page?: number;
  }): Promise<DepartmentListResponseDTO> {
    try {
      return this.isFacultyScope
        ? await listFacultyDepartmentsApi(params)
        : await listDepartmentsApi(params);
    } catch (error) {
      throw new Error(
        resolveApiErrorMessage(error, "Không tải được danh sách đơn vị.")
      );
    }
  }

  async createDepartment(payload: DepartmentUpsertDTO): Promise<DepartmentDTO> {
    try {
      return this.isFacultyScope
        ? await createFacultyDepartmentApi(payload)
        : await createDepartmentApi(payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Không thể tạo đơn vị."));
    }
  }

  async updateDepartment(
    id: number,
    payload: DepartmentUpsertDTO
  ): Promise<DepartmentDTO> {
    try {
      return this.isFacultyScope
        ? await updateFacultyDepartmentApi(id, payload)
        : await updateDepartmentApi(id, payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Không thể cập nhật đơn vị."));
    }
  }

  async listFacultyOptions(): Promise<FacultyOptionDTO[]> {
    try {
      if (this.isFacultyScope) {
        const lookups = await fetchFacultyOrgStructureLookupsApi();
        return lookups.faculties;
      }
      return await fetchFacultyOptionsApi();
    } catch (error) {
      throw new Error(
        resolveApiErrorMessage(error, "Không tải được danh sách khoa.")
      );
    }
  }
}
