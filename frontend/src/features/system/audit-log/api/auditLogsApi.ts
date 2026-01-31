import http from "@/lib/http";
import type {
  AuditLogEntryDTO,
  AuditLogListResponseDTO,
  AuditLogMetaDTO,
  AuditLogQueryDTO,
  AuditLogScope,
} from "../contracts/audit-log.contract";

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

function resolveBasePath(scope: AuditLogScope) {
  return scope === "FACULTY" ? "/api/faculty/audit-logs" : "/api/admin/audit-logs";
}

export async function listAuditLogsApi(
  scope: AuditLogScope,
  params: AuditLogQueryDTO
): Promise<AuditLogListResponseDTO> {
  const { scope: _scope, ...query } = params;
  const { data } = await http.get<ApiResponse<AuditLogListResponseDTO>>(
    resolveBasePath(scope),
    { params: query }
  );
  return data.data;
}

export async function getAuditLogDetailApi(
  scope: AuditLogScope,
  id: number
): Promise<AuditLogEntryDTO> {
  const { data } = await http.get<ApiResponse<AuditLogEntryDTO>>(
    `${resolveBasePath(scope)}/${id}`
  );
  return data.data;
}

export async function getAuditLogMetaApi(
  scope: AuditLogScope
): Promise<AuditLogMetaDTO> {
  const { data } = await http.get<ApiResponse<AuditLogMetaDTO>>(
    `${resolveBasePath(scope)}/meta`
  );
  return data.data;
}
