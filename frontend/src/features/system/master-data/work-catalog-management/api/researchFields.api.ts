import http, { ensureCsrfCookie } from "@/lib/http";
import type { ApiItemResponse, ApiListResponse } from "./workCatalogApi.types";
import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  ResearchFieldDTO,
  ResearchFieldUpsertDTO,
} from "../contracts/researchFields.contract";

const BASE = "/api/admin/work-catalog/research-fields";

export async function listResearchFieldsApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<ListResponseDTO<ResearchFieldDTO>> {
  const { data } = await http.get<
    ApiListResponse<ListResponseDTO<ResearchFieldDTO>>
  >(BASE, { params });
  return data.data;
}

export async function createResearchFieldApi(
  payload: ResearchFieldUpsertDTO,
): Promise<ResearchFieldDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<ResearchFieldDTO>>(
    BASE,
    payload,
  );
  return data.data;
}

export async function updateResearchFieldApi(
  id: number,
  payload: ResearchFieldUpsertDTO,
): Promise<ResearchFieldDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<ResearchFieldDTO>>(
    `${BASE}/${id}`,
    payload,
  );
  return data.data;
}

export async function updateResearchFieldStatusApi(
  id: number,
  is_active: boolean,
): Promise<ResearchFieldDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<ResearchFieldDTO>>(
    `${BASE}/${id}/status`,
    { is_active },
  );
  return data.data;
}
