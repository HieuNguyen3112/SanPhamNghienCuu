import http, { ensureCsrfCookie } from "@/lib/http";
import type { ApiItemResponse, ApiListResponse } from "./workCatalogApi.types";
import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  PublisherDTO,
  PublisherUpsertDTO,
} from "../contracts/publishers.contract";

const BASE = "/api/admin/work-catalog/publishers";

export async function listPublishersApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<ListResponseDTO<PublisherDTO>> {
  const { data } = await http.get<
    ApiListResponse<ListResponseDTO<PublisherDTO>>
  >(BASE, { params });
  return data.data;
}

export async function createPublisherApi(
  payload: PublisherUpsertDTO,
): Promise<PublisherDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<PublisherDTO>>(
    BASE,
    payload,
  );
  return data.data;
}

export async function updatePublisherApi(
  id: number,
  payload: PublisherUpsertDTO,
): Promise<PublisherDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<PublisherDTO>>(
    `${BASE}/${id}`,
    payload,
  );
  return data.data;
}

export async function updatePublisherStatusApi(
  id: number,
  is_active: boolean,
): Promise<PublisherDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<PublisherDTO>>(
    `${BASE}/${id}/status`,
    { is_active },
  );
  return data.data;
}
