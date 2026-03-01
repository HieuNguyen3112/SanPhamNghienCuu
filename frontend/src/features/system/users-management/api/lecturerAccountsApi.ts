import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  AssignRolesPayload,
  LecturerAccountDTO,
  LecturerAccountListResponseDTO,
  LecturerAccountLookupsDTO,
  ToggleAccountStatusPayload,
  UpdateLecturerAccountPayload,
} from "../contracts/lecturerAccountManagement.contract";

export interface LecturerAccountListApiResponse {
  success: boolean;
  message?: string;
  data: LecturerAccountListResponseDTO;
  meta?: Record<string, unknown>;
}

export interface LecturerAccountLookupsApiResponse {
  success: boolean;
  message?: string;
  data: LecturerAccountLookupsDTO;
}

export interface LecturerAccountItemApiResponse {
  success: boolean;
  message?: string;
  data: LecturerAccountDTO;
}

export async function fetchLecturerAccountsApi(
  query: Record<string, unknown>
): Promise<LecturerAccountListResponseDTO> {
  const { data } = await http.get<LecturerAccountListApiResponse>(
    "/api/admin/lecturer-accounts",
    { params: query }
  );
  return data.data;
}

export async function fetchLecturerAccountLookupsApi(): Promise<LecturerAccountLookupsDTO> {
  const { data } = await http.get<LecturerAccountLookupsApiResponse>(
    "/api/admin/lecturer-accounts/lookups"
  );
  return data.data;
}

export async function fetchFacultyLecturerAccountsApi(
  query: Record<string, unknown>
): Promise<LecturerAccountListResponseDTO> {
  const { data } = await http.get<LecturerAccountListApiResponse>(
    "/api/faculty/users/lecturer-accounts",
    { params: query }
  );
  return data.data;
}

export async function fetchFacultyLecturerAccountLookupsApi(): Promise<LecturerAccountLookupsDTO> {
  const { data } = await http.get<LecturerAccountLookupsApiResponse>(
    "/api/faculty/users/lecturer-accounts/lookups"
  );
  return data.data;
}

export async function updateLecturerAccountApi(
  lecturerId: number,
  payload: UpdateLecturerAccountPayload
): Promise<LecturerAccountDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<LecturerAccountItemApiResponse>(
    `/api/admin/lecturer-accounts/${lecturerId}`,
    payload
  );
  return data.data;
}

export async function updateLecturerRolesApi(
  lecturerId: number,
  payload: AssignRolesPayload
): Promise<LecturerAccountDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<LecturerAccountItemApiResponse>(
    `/api/admin/lecturer-accounts/${lecturerId}/roles`,
    payload
  );
  return data.data;
}

export async function updateLecturerStatusApi(
  lecturerId: number,
  payload: ToggleAccountStatusPayload
): Promise<LecturerAccountDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<LecturerAccountItemApiResponse>(
    `/api/admin/lecturer-accounts/${lecturerId}/status`,
    payload
  );
  return data.data;
}

export async function updateFacultyLecturerAccountApi(
  lecturerId: number,
  payload: UpdateLecturerAccountPayload
): Promise<LecturerAccountDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<LecturerAccountItemApiResponse>(
    `/api/faculty/users/lecturer-accounts/${lecturerId}`,
    payload
  );
  return data.data;
}

export async function updateFacultyLecturerRolesApi(
  lecturerId: number,
  payload: AssignRolesPayload
): Promise<LecturerAccountDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<LecturerAccountItemApiResponse>(
    `/api/faculty/users/lecturer-accounts/${lecturerId}/roles`,
    payload
  );
  return data.data;
}

export async function updateFacultyLecturerStatusApi(
  lecturerId: number,
  payload: ToggleAccountStatusPayload
): Promise<LecturerAccountDTO> {
  await ensureCsrfCookie();
  const { data } = await http.put<LecturerAccountItemApiResponse>(
    `/api/faculty/users/lecturer-accounts/${lecturerId}/status`,
    payload
  );
  return data.data;
}
