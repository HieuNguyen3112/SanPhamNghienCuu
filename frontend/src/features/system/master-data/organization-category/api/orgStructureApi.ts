import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  DepartmentDTO,
  DepartmentListResponseDTO,
  DepartmentUpsertDTO,
  FacultyDTO,
  FacultyListResponseDTO,
  FacultyOptionDTO,
  FacultyUpsertDTO,
} from "../contracts/organizationCategory.contract";

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

export async function listFacultiesApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<FacultyListResponseDTO> {
  const { data } = await http.get<ApiListResponse<FacultyListResponseDTO>>(
    "/api/admin/org-structure/faculties",
    { params }
  );
  return data.data;
}

export async function listFacultyFacultiesApi(params: {
  keyword?: string;
  page?: number;
  per_page?: number;
}): Promise<FacultyListResponseDTO> {
  const { data } = await http.get<ApiListResponse<FacultyListResponseDTO>>(
    "/api/faculty/org-structure/faculties",
    { params }
  );
  return data.data;
}

export async function createFacultyApi(
  payload: FacultyUpsertDTO
): Promise<FacultyDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<FacultyDTO>>(
    "/api/admin/org-structure/faculties",
    payload
  );
  return data.data;
}

export async function updateFacultyApi(
  id: number,
  payload: FacultyUpsertDTO
): Promise<FacultyDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<FacultyDTO>>(
    `/api/admin/org-structure/faculties/${id}`,
    payload
  );
  return data.data;
}

export async function listDepartmentsApi(params: {
  keyword?: string;
  faculty_id?: number;
  page?: number;
  per_page?: number;
}): Promise<DepartmentListResponseDTO> {
  const { data } = await http.get<ApiListResponse<DepartmentListResponseDTO>>(
    "/api/admin/org-structure/departments",
    { params }
  );
  return data.data;
}

export async function listFacultyDepartmentsApi(params: {
  keyword?: string;
  faculty_id?: number;
  page?: number;
  per_page?: number;
}): Promise<DepartmentListResponseDTO> {
  const { data } = await http.get<ApiListResponse<DepartmentListResponseDTO>>(
    "/api/faculty/org-structure/departments",
    { params }
  );
  return data.data;
}

export async function createDepartmentApi(
  payload: DepartmentUpsertDTO
): Promise<DepartmentDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<DepartmentDTO>>(
    "/api/admin/org-structure/departments",
    payload
  );
  return data.data;
}

export async function createFacultyDepartmentApi(
  payload: DepartmentUpsertDTO
): Promise<DepartmentDTO> {
  await ensureCsrfCookie();
  const { data } = await http.post<ApiItemResponse<DepartmentDTO>>(
    "/api/faculty/org-structure/departments",
    payload
  );
  return data.data;
}

export async function updateDepartmentApi(
  id: number,
  payload: DepartmentUpsertDTO
): Promise<DepartmentDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<DepartmentDTO>>(
    `/api/admin/org-structure/departments/${id}`,
    payload
  );
  return data.data;
}

export async function updateFacultyDepartmentApi(
  id: number,
  payload: DepartmentUpsertDTO
): Promise<DepartmentDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<ApiItemResponse<DepartmentDTO>>(
    `/api/faculty/org-structure/departments/${id}`,
    payload
  );
  return data.data;
}

export async function fetchFacultyOptionsApi(): Promise<FacultyOptionDTO[]> {
  const { data } = await http.get<{ data: FacultyOptionDTO[] }>(
    "/api/lookups/faculties"
  );
  return data.data;
}

export async function fetchFacultyOrgStructureLookupsApi(): Promise<{
  scope: { faculty_id: number; faculty_name: string };
  faculties: FacultyOptionDTO[];
}> {
  const { data } = await http.get<{
    data: { scope: { faculty_id: number; faculty_name: string }; faculties: FacultyOptionDTO[] };
  }>("/api/faculty/org-structure/lookups");
  return data.data;
}
