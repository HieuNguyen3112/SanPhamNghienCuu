import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  ResearchFieldDTO,
  ResearchFieldUpsertDTO,
} from "../contracts/researchFields.contract";
import {
  listResearchFieldsApi,
  createResearchFieldApi,
  updateResearchFieldApi,
  updateResearchFieldStatusApi,
} from "../api/researchFields.api";

export const researchFieldService = {
  async list(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<ResearchFieldDTO>> {
    return listResearchFieldsApi(params);
  },

  async create(payload: ResearchFieldUpsertDTO): Promise<ResearchFieldDTO> {
    return createResearchFieldApi(payload);
  },

  async update(
    id: number,
    payload: ResearchFieldUpsertDTO,
  ): Promise<ResearchFieldDTO> {
    return updateResearchFieldApi(id, payload);
  },

  async updateStatus(
    id: number,
    is_active: boolean,
  ): Promise<ResearchFieldDTO> {
    return updateResearchFieldStatusApi(id, is_active);
  },
};
