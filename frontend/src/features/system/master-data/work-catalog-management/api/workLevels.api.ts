import http from "@/lib/http";
import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  WorkLevelDTO,
  WorkLevelUpsertDTO,
} from "../contracts/workLevels.contract";

const base = "/api/admin/work-catalog/work-levels";

export const workLevelsApi = {
  async list(params: { keyword?: string; page?: number; per_page?: number }) {
    const res = await http.get<{
      success: boolean;
      data: ListResponseDTO<WorkLevelDTO>;
    }>(base, { params });
    return res.data.data;
  },

  async create(payload: Omit<WorkLevelUpsertDTO, "id">) {
    const res = await http.post<{ success: boolean; data: WorkLevelDTO }>(
      base,
      payload,
    );
    return res.data.data;
  },

  async update(id: number, payload: Omit<WorkLevelUpsertDTO, "id">) {
    const res = await http.put<{ success: boolean; data: WorkLevelDTO }>(
      `${base}/${id}`,
      payload,
    );
    return res.data.data;
  },

  async updateStatus(id: number, payload: { is_active: boolean }) {
    const res = await http.patch<{ success: boolean; data: WorkLevelDTO }>(
      `${base}/${id}/status`,
      payload,
    );
    return res.data.data;
  },
};
