import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  ConferenceDTO,
  JournalDTO,
  JournalRankingDTO,
  ResearchFieldDTO,
  WorkCatalogListResponseDTO,
  WorkLevelDTO,
  WorkTypeDTO,
} from "../contracts/workCatalog.contract";

export interface ApiListResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export interface ApiItemResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export async function listWorkTypesApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<WorkCatalogListResponseDTO<WorkTypeDTO>> {
  const { data } = await http.get<ApiListResponse<WorkCatalogListResponseDTO<WorkTypeDTO>>>(
    "/api/admin/work-catalog/work-types",
    { params }
  );
  return data.data;
}

export async function createWorkTypeApi(
  payload: Omit<WorkTypeDTO, "updated_at">
): Promise<WorkTypeDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<WorkTypeDTO>>(
    "/api/admin/work-catalog/work-types",
    payload
  );
  return data.data;
}

export async function updateWorkTypeApi(
  id: number,
  payload: Omit<WorkTypeDTO, "updated_at">
): Promise<WorkTypeDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<WorkTypeDTO>>(
    `/api/admin/work-catalog/work-types/${id}`,
    payload
  );
  return data.data;
}

export async function updateWorkTypeStatusApi(
  id: number,
  is_active: boolean
): Promise<WorkTypeDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<WorkTypeDTO>>(
    `/api/admin/work-catalog/work-types/${id}/status`,
    { is_active }
  );
  return data.data;
}

export async function listWorkLevelsApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<WorkCatalogListResponseDTO<WorkLevelDTO>> {
  const { data } = await http.get<ApiListResponse<WorkCatalogListResponseDTO<WorkLevelDTO>>>(
    "/api/admin/work-catalog/work-levels",
    { params }
  );
  return data.data;
}

export async function createWorkLevelApi(
  payload: Omit<WorkLevelDTO, "updated_at">
): Promise<WorkLevelDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<WorkLevelDTO>>(
    "/api/admin/work-catalog/work-levels",
    payload
  );
  return data.data;
}

export async function updateWorkLevelApi(
  id: number,
  payload: Omit<WorkLevelDTO, "updated_at">
): Promise<WorkLevelDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<WorkLevelDTO>>(
    `/api/admin/work-catalog/work-levels/${id}`,
    payload
  );
  return data.data;
}

export async function updateWorkLevelStatusApi(
  id: number,
  is_active: boolean
): Promise<WorkLevelDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<WorkLevelDTO>>(
    `/api/admin/work-catalog/work-levels/${id}/status`,
    { is_active }
  );
  return data.data;
}

export async function listJournalsApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<WorkCatalogListResponseDTO<JournalDTO>> {
  const { data } = await http.get<ApiListResponse<WorkCatalogListResponseDTO<JournalDTO>>>(
    "/api/admin/work-catalog/journals",
    { params }
  );
  return data.data;
}

export async function createJournalApi(
  payload: Omit<JournalDTO, "updated_at">
): Promise<JournalDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<JournalDTO>>(
    "/api/admin/work-catalog/journals",
    payload
  );
  return data.data;
}

export async function updateJournalApi(
  id: number,
  payload: Omit<JournalDTO, "updated_at">
): Promise<JournalDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<JournalDTO>>(
    `/api/admin/work-catalog/journals/${id}`,
    payload
  );
  return data.data;
}

export async function updateJournalStatusApi(
  id: number,
  is_active: boolean
): Promise<JournalDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<JournalDTO>>(
    `/api/admin/work-catalog/journals/${id}/status`,
    { is_active }
  );
  return data.data;
}

export async function createJournalRankingApi(
  journalId: number,
  payload: {
    rank: string;
    effective_from: string;
    note: string | null;
  }
): Promise<JournalRankingDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<JournalRankingDTO>>(
    `/api/admin/work-catalog/journals/${journalId}/rankings`,
    payload
  );
  return data.data;
}

export async function listConferencesApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<WorkCatalogListResponseDTO<ConferenceDTO>> {
  const { data } = await http.get<ApiListResponse<WorkCatalogListResponseDTO<ConferenceDTO>>>(
    "/api/admin/work-catalog/conferences",
    { params }
  );
  return data.data;
}

export async function createConferenceApi(
  payload: Omit<ConferenceDTO, "updated_at">
): Promise<ConferenceDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<ConferenceDTO>>(
    "/api/admin/work-catalog/conferences",
    payload
  );
  return data.data;
}

export async function updateConferenceApi(
  id: number,
  payload: Omit<ConferenceDTO, "updated_at">
): Promise<ConferenceDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<ConferenceDTO>>(
    `/api/admin/work-catalog/conferences/${id}`,
    payload
  );
  return data.data;
}

export async function updateConferenceStatusApi(
  id: number,
  is_active: boolean
): Promise<ConferenceDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<ConferenceDTO>>(
    `/api/admin/work-catalog/conferences/${id}/status`,
    { is_active }
  );
  return data.data;
}

export async function listResearchFieldsApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<WorkCatalogListResponseDTO<ResearchFieldDTO>> {
  const { data } = await http.get<ApiListResponse<WorkCatalogListResponseDTO<ResearchFieldDTO>>>(
    "/api/admin/work-catalog/research-fields",
    { params }
  );
  return data.data;
}

export async function createResearchFieldApi(
  payload: Omit<ResearchFieldDTO, "updated_at">
): Promise<ResearchFieldDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<ResearchFieldDTO>>(
    "/api/admin/work-catalog/research-fields",
    payload
  );
  return data.data;
}

export async function updateResearchFieldApi(
  id: number,
  payload: Omit<ResearchFieldDTO, "updated_at">
): Promise<ResearchFieldDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<ResearchFieldDTO>>(
    `/api/admin/work-catalog/research-fields/${id}`,
    payload
  );
  return data.data;
}

export async function updateResearchFieldStatusApi(
  id: number,
  is_active: boolean
): Promise<ResearchFieldDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<ResearchFieldDTO>>(
    `/api/admin/work-catalog/research-fields/${id}/status`,
    { is_active }
  );
  return data.data;
}
