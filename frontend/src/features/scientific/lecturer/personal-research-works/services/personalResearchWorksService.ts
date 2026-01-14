import axios from "axios";
import http from "@/lib/http";
import type {
  PersonalWorkDetailDTO,
  PersonalWorkIndexResponseDTO,
  PersonalWorkRowDTO,
  PersonalStatsDTO,
} from "../contracts/personalResearchWorksContracts";

export interface PersonalWorksIndexParams {
  status?: string;
  q?: string;
  year?: number;
  academic_year_id?: number;
  kind_id?: number;
  type_id?: number;
  role_id?: number;
  sort?: string;
  page?: number;
  per_page?: number;
}

const extractErrorMessage = (err: unknown, fallback: string) => {
  if (axios.isAxiosError(err)) {
    const status = err.response?.status ?? 0;
    if (status >= 500) return fallback;
    const message = err.response?.data?.message;
    if (typeof message === "string" && message.trim()) return message;
  }

  return err instanceof Error ? err.message : fallback;
};

export const personalResearchWorksService = {
  async getIndex(
    params: PersonalWorksIndexParams
  ): Promise<PersonalWorkIndexResponseDTO> {
    try {
      const response = await http.get<{ data: PersonalWorkIndexResponseDTO }>(
        "/api/lecturer/works/my",
        { params }
      );

      return response.data.data;
    } catch (err) {
      throw new Error(extractErrorMessage(err, "Failed to load works."));
    }
  },

  async getDetail(activityId: number): Promise<PersonalWorkDetailDTO> {
    try {
      const response = await http.get<{ data: PersonalWorkDetailDTO }>(
        `/api/lecturer/works/my/${activityId}`
      );

      return response.data.data;
    } catch (err) {
      throw new Error(extractErrorMessage(err, "Failed to load work detail."));
    }
  },
};
