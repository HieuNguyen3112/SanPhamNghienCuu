import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  Publisher,
  PublisherDTO,
  PublisherSuggestion,
  PublisherSuggestionDTO,
  PublisherUpsertDTO,
} from "../contracts/publishers.contract";
import {
  approvePublisherSuggestionApi,
  listPublishersApi,
  listPublisherSuggestionsApi,
  rejectPublisherSuggestionApi,
  createPublisherApi,
  updatePublisherApi,
  updatePublisherStatusApi,
} from "../api/publishers.api";
import {
  publisherFromDto,
  publisherSuggestionFromDto,
} from "../contracts/publishers.contract";

export const publisherService = {
  async list(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<PublisherDTO>> {
    return listPublishersApi(params);
  },

  async create(payload: PublisherUpsertDTO): Promise<PublisherDTO> {
    return createPublisherApi(payload);
  },

  async update(id: number, payload: PublisherUpsertDTO): Promise<PublisherDTO> {
    return updatePublisherApi(id, payload);
  },

  async updateStatus(id: number, is_active: boolean): Promise<PublisherDTO> {
    return updatePublisherStatusApi(id, is_active);
  },

  async listSuggestions(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<PublisherSuggestion>> {
    const data = await listPublisherSuggestionsApi(params);
    return {
      items: data.items.map(publisherSuggestionFromDto),
      pagination: data.pagination,
    };
  },

  async approveSuggestion(
    id: number,
    review_note?: string,
  ): Promise<{
    suggestion: PublisherSuggestion | null;
    catalog: Publisher | null;
  }> {
    const data = await approvePublisherSuggestionApi(
      id,
      review_note ? { review_note } : undefined,
    );

    return {
      suggestion: data.suggestion
        ? publisherSuggestionFromDto(data.suggestion as PublisherSuggestionDTO)
        : null,
      catalog: data.catalog ? publisherFromDto(data.catalog) : null,
    };
  },

  async rejectSuggestion(
    id: number,
    review_note?: string,
  ): Promise<{
    suggestion: PublisherSuggestion | null;
    catalog: Publisher | null;
  }> {
    const data = await rejectPublisherSuggestionApi(
      id,
      review_note ? { review_note } : undefined,
    );

    return {
      suggestion: data.suggestion
        ? publisherSuggestionFromDto(data.suggestion as PublisherSuggestionDTO)
        : null,
      catalog: data.catalog ? publisherFromDto(data.catalog) : null,
    };
  },
};
