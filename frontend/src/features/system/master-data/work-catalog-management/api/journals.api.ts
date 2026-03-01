import http, { ensureCsrfCookie } from "@/lib/http";
import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  JournalDTO,
  JournalUpsertDTO,
} from "../contracts/journals.contract";

const base = "/api/admin/work-catalog/journals";

export const journalsApi = {
  async list(params: { keyword?: string; page?: number; per_page?: number }) {
    const res = await http.get<{
      success: boolean;
      data: ListResponseDTO<JournalDTO>;
    }>(base, { params });
    return res.data.data;
  },

  async create(payload: Omit<JournalUpsertDTO, "id">) {
    await ensureCsrfCookie();
    const res = await http.post<{ success: boolean; data: JournalDTO }>(
      base,
      payload,
    );
    return res.data.data;
  },

  async update(id: number, payload: Omit<JournalUpsertDTO, "id">) {
    await ensureCsrfCookie();
    const res = await http.put<{ success: boolean; data: JournalDTO }>(
      `${base}/${id}`,
      payload,
    );
    return res.data.data;
  },

  async updateStatus(id: number, payload: { is_active: boolean }) {
    await ensureCsrfCookie();
    const res = await http.patch<{ success: boolean; data: JournalDTO }>(
      `${base}/${id}/status`,
      payload,
    );
    return res.data.data;
  },
};
