import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  PublisherDTO,
  PublisherUpsertDTO,
} from "../contracts/publishers.contract";
import {
  listPublishersApi,
  createPublisherApi,
  updatePublisherApi,
  updatePublisherStatusApi,
} from "../api/publishers.api";

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
};
