import type {
  AuditLogEntryDTO,
  AuditLogListResponseDTO,
  AuditLogMetaDTO,
  AuditLogQueryDTO,
  AuditLogScope,
} from "../contracts/audit-log.contract";
import {
  getAuditLogDetailApi,
  getAuditLogMetaApi,
  listAuditLogsApi,
} from "../api/auditLogsApi";

function resolveApiErrorMessage(error: unknown, fallback: string): string {
  if (typeof error === "object" && error !== null) {
    const anyError = error as {
      message?: string;
      response?: { data?: { message?: string } };
    };
    return anyError.response?.data?.message || anyError.message || fallback;
  }
  return fallback;
}

export async function fetchAuditLogEntries(
  query: AuditLogQueryDTO
): Promise<AuditLogListResponseDTO> {
  try {
    return await listAuditLogsApi(query.scope, query);
  } catch (error) {
    throw new Error(
      resolveApiErrorMessage(error, "Không tải được danh sách nhật ký.")
    );
  }
}

export async function fetchAuditLogDetail(
  scope: AuditLogScope,
  id: number
): Promise<AuditLogEntryDTO> {
  try {
    return await getAuditLogDetailApi(scope, id);
  } catch (error) {
    throw new Error(
      resolveApiErrorMessage(error, "Không tải được chi tiết nhật ký.")
    );
  }
}

export async function fetchAuditLogMeta(
  scope: AuditLogScope
): Promise<AuditLogMetaDTO> {
  try {
    return await getAuditLogMetaApi(scope);
  } catch (error) {
    throw new Error(
      resolveApiErrorMessage(error, "Không tải được dữ liệu lọc.")
    );
  }
}
