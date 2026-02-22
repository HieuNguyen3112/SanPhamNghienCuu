import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  ConferenceDTO,
  ConferenceUpsertDTO,
} from "../contracts/conferences.contract";
import {
  listConferencesApi,
  createConferenceApi,
  updateConferenceApi,
  updateConferenceStatusApi,
} from "../api/conferences.api";

export const conferenceService = {
  async list(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<ConferenceDTO>> {
    return listConferencesApi(params);
  },

  async create(payload: ConferenceUpsertDTO): Promise<ConferenceDTO> {
    return createConferenceApi(payload);
  },

  async update(
    id: number,
    payload: ConferenceUpsertDTO,
  ): Promise<ConferenceDTO> {
    return updateConferenceApi(id, payload);
  },

  async updateStatus(id: number, is_active: boolean): Promise<ConferenceDTO> {
    return updateConferenceStatusApi(id, is_active);
  },
};
