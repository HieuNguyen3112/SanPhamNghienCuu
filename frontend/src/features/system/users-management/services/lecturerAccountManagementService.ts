import type {
  AssignRolesPayload,
  LecturerAccountFilterState,
  LecturerAccountListResponseDTO,
  LecturerAccountLookupsDTO,
  LecturerAccountScope,
  ToggleAccountStatusPayload,
  UpdateLecturerAccountPayload,
} from "../contracts/lecturerAccountManagement.contract";
import { searchQueryToDto } from "../contracts/lecturerAccountManagement.contract";
import {
  fetchLecturerAccountLookupsApi,
  fetchLecturerAccountsApi,
  fetchFacultyLecturerAccountLookupsApi,
  fetchFacultyLecturerAccountsApi,
  updateLecturerAccountApi,
  updateFacultyLecturerAccountApi,
  updateLecturerRolesApi,
  updateFacultyLecturerRolesApi,
  updateLecturerStatusApi,
  updateFacultyLecturerStatusApi,
} from "../api/lecturerAccountsApi";

function resolveApiErrorMessage(error: unknown, fallback: string): string {
  if (typeof error === "object" && error !== null) {
    const anyError = error as {
      message?: string;
      response?: { data?: { message?: string } };
    };
    return (
      anyError.response?.data?.message || anyError.message || fallback
    );
  }
  return fallback;
}

export interface LecturerAccountManagementService {
  getLookupsDTO(): Promise<LecturerAccountLookupsDTO>;
  searchLecturerAccountsDTO(
    filter: LecturerAccountFilterState,
    options: { page: number; per_page: number; sort?: string }
  ): Promise<LecturerAccountListResponseDTO>;
  updateLecturerAccountDTO(
    payload: UpdateLecturerAccountPayload
  ): Promise<void>;
  assignRolesDTO(payload: AssignRolesPayload): Promise<void>;
  toggleAccountStatusDTO(payload: ToggleAccountStatusPayload): Promise<void>;
}

export function createLecturerAccountManagementService(_params: {
  scope: LecturerAccountScope;
  faculty_unit_id?: number;
}): LecturerAccountManagementService {
  const isFacultyScope = _params.scope === "FACULTY";
  return {
    async getLookupsDTO() {
      try {
        return isFacultyScope
          ? await fetchFacultyLecturerAccountLookupsApi()
          : await fetchLecturerAccountLookupsApi();
      } catch (error) {
        throw new Error(
          resolveApiErrorMessage(error, "Không tải được danh mục.")
        );
      }
    },

    async searchLecturerAccountsDTO(filter, options) {
      try {
        const query: Record<string, unknown> = {
          ...searchQueryToDto(filter),
          page: options.page,
          per_page: options.per_page,
          sort: options.sort,
        };
        if (!query.keyword) delete query.keyword;
        if (query.unit_id == null) delete query.unit_id;
        if (Array.isArray(query.role_keys) && query.role_keys.length === 0) {
          delete query.role_keys;
        }
        if (query.status === "all") delete query.status;
        return isFacultyScope
          ? await fetchFacultyLecturerAccountsApi(query)
          : await fetchLecturerAccountsApi(query);
      } catch (error) {
        throw new Error(
          resolveApiErrorMessage(error, "Không tải được danh sách.")
        );
      }
    },

    async updateLecturerAccountDTO(payload) {
      try {
        if (isFacultyScope) {
          await updateFacultyLecturerAccountApi(payload.id, payload);
        } else {
          await updateLecturerAccountApi(payload.id, payload);
        }
      } catch (error) {
        throw new Error(
          resolveApiErrorMessage(error, "Không thể lưu thay đổi.")
        );
      }
    },

    async assignRolesDTO(payload) {
      try {
        if (isFacultyScope) {
          await updateFacultyLecturerRolesApi(payload.id, payload);
        } else {
          await updateLecturerRolesApi(payload.id, payload);
        }
      } catch (error) {
        throw new Error(
          resolveApiErrorMessage(error, "Không thể lưu phân quyền.")
        );
      }
    },

    async toggleAccountStatusDTO(payload) {
      try {
        if (isFacultyScope) {
          await updateFacultyLecturerStatusApi(payload.id, payload);
        } else {
          await updateLecturerStatusApi(payload.id, payload);
        }
      } catch (error) {
        throw new Error(
          resolveApiErrorMessage(error, "Không thể cập nhật trạng thái.")
        );
      }
    },
  };
}
