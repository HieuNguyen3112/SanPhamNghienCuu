import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  Conference,
  ConferenceDTO,
  ConferenceSuggestion,
  ConferenceUpsertDTO,
} from "../contracts/conferences.contract";
import {
  listConferencesApi,
  createConferenceApi,
  updateConferenceApi,
  updateConferenceStatusApi,
  listConferenceSuggestionsApi,
  approveConferenceSuggestionApi,
  rejectConferenceSuggestionApi,
} from "../api/conferences.api";
import {
  conferenceFromDto,
  conferenceSuggestionFromDto,
} from "../contracts/conferences.contract";

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

  async listSuggestions(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<ConferenceSuggestion>> {
    const data = await listConferenceSuggestionsApi(params);
    return {
      items: data.items.map(conferenceSuggestionFromDto),
      pagination: data.pagination,
    };
  },

  async approveSuggestion(
    id: number,
    review_note?: string,
  ): Promise<{
    suggestion: ConferenceSuggestion | null;
    catalog: Conference | null;
  }> {
    const data = await approveConferenceSuggestionApi(
      id,
      review_note ? { review_note } : undefined,
    );

    return {
      suggestion: data.suggestion
        ? conferenceSuggestionFromDto(data.suggestion)
        : null,
      catalog: data.catalog ? conferenceFromDto(data.catalog) : null,
    };
  },

  async rejectSuggestion(
    id: number,
    review_note?: string,
  ): Promise<{
    suggestion: ConferenceSuggestion | null;
    catalog: Conference | null;
  }> {
    const data = await rejectConferenceSuggestionApi(
      id,
      review_note ? { review_note } : undefined,
    );

    return {
      suggestion: data.suggestion
        ? conferenceSuggestionFromDto(data.suggestion)
        : null,
      catalog: data.catalog ? conferenceFromDto(data.catalog) : null,
    };
  },
};
