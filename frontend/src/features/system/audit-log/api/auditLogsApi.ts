import http from "@/lib/http";
import type {
  AuditLogEntryDTO,
  AuditLogListResponseDTO,
  AuditLogMetaDTO,
  AuditLogQueryDTO,
} from "../contracts/audit-log.contract";

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export async function listAuditLogsApi(
  params: AuditLogQueryDTO
): Promise<AuditLogListResponseDTO> {
  const { data } = await http.get<ApiResponse<AuditLogListResponseDTO>>(
    "/api/admin/audit-logs",
    { params }
  );
  return data.data;
}

export async function getAuditLogDetailApi(
  id: number
): Promise<AuditLogEntryDTO> {
  const { data } = await http.get<ApiResponse<AuditLogEntryDTO>>(
    `/api/admin/audit-logs/${id}`
  );
  return data.data;
}

export async function getAuditLogMetaApi(): Promise<AuditLogMetaDTO> {
  const { data } = await http.get<ApiResponse<AuditLogMetaDTO>>(
    "/api/admin/audit-logs/meta"
  );
  return data.data;
}
