import http, { ensureCsrfCookie } from "@/lib/http";
import type { ApiItemResponse, ApiListResponse } from "./workCatalogApi.types";
import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  ConferenceDTO,
  ConferenceUpsertDTO,
} from "../contracts/conferences.contract";

const BASE = "/api/admin/work-catalog/conferences";

export async function listConferencesApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<ListResponseDTO<ConferenceDTO>> {
  const { data } = await http.get<
    ApiListResponse<ListResponseDTO<ConferenceDTO>>
  >(BASE, { params });
  return data.data;
}

export async function createConferenceApi(
  payload: ConferenceUpsertDTO,
): Promise<ConferenceDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<ConferenceDTO>>(
    BASE,
    payload,
  );
  return data.data;
}

export async function updateConferenceApi(
  id: number,
  payload: ConferenceUpsertDTO,
): Promise<ConferenceDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<ConferenceDTO>>(
    `${BASE}/${id}`,
    payload,
  );
  return data.data;
}

export async function updateConferenceStatusApi(
  id: number,
  is_active: boolean,
): Promise<ConferenceDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<ConferenceDTO>>(
    `${BASE}/${id}/status`,
    { is_active },
  );
  return data.data;
}
