import http from "@/lib/http";
import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  WorkTypeDTO,
  WorkTypeUpsertDTO,
} from "../contracts/workTypes.contract";

const base = "/api/admin/work-catalog/work-types";

export const workTypesApi = {
  async list(params: { keyword?: string; page?: number; per_page?: number }) {
    const res = await http.get<{
      success: boolean;
      data: ListResponseDTO<WorkTypeDTO>;
    }>(base, { params });
    return res.data.data;
  },

  async create(payload: Omit<WorkTypeUpsertDTO, "id">) {
    const res = await http.post<{ success: boolean; data: WorkTypeDTO }>(
      base,
      payload,
    );
    return res.data.data;
  },

  async update(id: number, payload: Omit<WorkTypeUpsertDTO, "id">) {
    const res = await http.put<{ success: boolean; data: WorkTypeDTO }>(
      `${base}/${id}`,
      payload,
    );
    return res.data.data;
  },

  async updateStatus(id: number, payload: { is_active: boolean }) {
    const res = await http.patch<{ success: boolean; data: WorkTypeDTO }>(
      `${base}/${id}/status`,
      payload,
    );
    return res.data.data;
  },
};
