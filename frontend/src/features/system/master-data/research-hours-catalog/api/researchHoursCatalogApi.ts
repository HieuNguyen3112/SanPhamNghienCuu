import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  AcademicYearDerivedDTO,
  HourRuleDerivedDTO,
  ListResponseDTO,
  ResearchHoursMetaDTO,
  WorkloadQuotaDerivedDTO,
} from "../contracts/researchHoursCatalog.contract";

interface ApiListResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

interface ApiItemResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export async function fetchResearchHoursMeta(): Promise<ResearchHoursMetaDTO> {
  const { data } = await http.get<ApiItemResponse<ResearchHoursMetaDTO>>(
    "/api/admin/research-hours/meta"
  );
  return data.data;
}

export async function listHourRulesApi(params: {
  academic_year_id?: number;
  status?: string;
  q?: string;
  page?: number;
  per_page?: number;
}): Promise<ListResponseDTO<HourRuleDerivedDTO>> {
  const { data } = await http.get<ApiListResponse<ListResponseDTO<HourRuleDerivedDTO>>>(
    "/api/admin/research-hours/hour-rules",
    { params }
  );
  return data.data;
}

export async function createHourRuleApi(payload: {
  kind_id: number;
  type_id: number | null;
  distribution_strategy: string;
  hours_total_per_activity: string | null;
  hours_per_occurrence: string | null;
  principal_fraction: string | null;
  others_fraction_total: string | null;
  max_occurrences_per_year: number | null;
  effective_from: string;
  effective_to: string | null;
  is_active: boolean;
  version: number;
}): Promise<HourRuleDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<HourRuleDerivedDTO>>(
    "/api/admin/research-hours/hour-rules",
    payload
  );
  return data.data;
}

export async function updateHourRuleApi(
  id: number,
  payload: {
    kind_id: number;
    type_id: number | null;
    distribution_strategy: string;
    hours_total_per_activity: string | null;
    hours_per_occurrence: string | null;
    principal_fraction: string | null;
    others_fraction_total: string | null;
    max_occurrences_per_year: number | null;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    version: number;
  }
): Promise<HourRuleDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<HourRuleDerivedDTO>>(
    `/api/admin/research-hours/hour-rules/${id}`,
    payload
  );
  return data.data;
}

export async function updateHourRuleStatusApi(
  id: number,
  isActive: boolean
): Promise<HourRuleDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<HourRuleDerivedDTO>>(
    `/api/admin/research-hours/hour-rules/${id}/status`,
    { is_active: isActive }
  );
  return data.data;
}

export async function listWorkloadQuotasApi(params: {
  academic_year_id?: number;
  status?: string;
  q?: string;
  page?: number;
  per_page?: number;
}): Promise<ListResponseDTO<WorkloadQuotaDerivedDTO>> {
  const { data } = await http.get<ApiListResponse<ListResponseDTO<WorkloadQuotaDerivedDTO>>>(
    "/api/admin/research-hours/workload-quotas",
    { params }
  );
  return data.data;
}

export async function createWorkloadQuotaApi(payload: {
  academic_year_id: number;
  required_hours: number;
  notes?: string | null;
}): Promise<WorkloadQuotaDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<WorkloadQuotaDerivedDTO>>(
    "/api/admin/research-hours/workload-quotas",
    payload
  );
  return data.data;
}

export async function updateWorkloadQuotaApi(
  id: number,
  payload: {
    required_hours: number;
    notes?: string | null;
  }
): Promise<WorkloadQuotaDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<WorkloadQuotaDerivedDTO>>(
    `/api/admin/research-hours/workload-quotas/${id}`,
    payload
  );
  return data.data;
}

export async function listAcademicYearsApi(params: {
  status?: string;
  q?: string;
  page?: number;
  per_page?: number;
}): Promise<ListResponseDTO<AcademicYearDerivedDTO>> {
  const { data } = await http.get<ApiListResponse<ListResponseDTO<AcademicYearDerivedDTO>>>(
    "/api/admin/research-hours/academic-years",
    { params }
  );
  return data.data;
}

export async function createAcademicYearApi(payload: {
  code: string;
  start_date: string;
  end_date: string;
  is_active: boolean;
}): Promise<AcademicYearDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<AcademicYearDerivedDTO>>(
    "/api/admin/research-hours/academic-years",
    payload
  );
  return data.data;
}

export async function updateAcademicYearApi(
  id: number,
  payload: {
    code: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
  }
): Promise<AcademicYearDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<AcademicYearDerivedDTO>>(
    `/api/admin/research-hours/academic-years/${id}`,
    payload
  );
  return data.data;
}

export async function applyAcademicYearApi(
  id: number
): Promise<AcademicYearDerivedDTO> {
  await ensureCsrfCookie();
  const { data } = await http.patch<ApiItemResponse<AcademicYearDerivedDTO>>(
    `/api/admin/research-hours/academic-years/${id}/apply`
  );
  return data.data;
}
