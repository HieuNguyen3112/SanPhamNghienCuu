import http from "@/lib/http";
import type {
  GlobalResearchWorkSearchFilterDTO,
  LecturerSuggestion,
  ResearchWorkDetailDTO,
  ResearchWorkSummaryDTO,
  WorkSearchLookupsDTO,
} from "../contracts/globalResearchWorkSearch.contract";

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export type ResearchWorkSearchScope = "admin" | "lecturer";

export interface PaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface WorkSearchListResponseDTO {
  summary: { total: number };
  table: {
    items: ResearchWorkSummaryDTO[];
    pagination: PaginationDTO;
  };
  applied_filters?: Record<string, unknown>;
}

interface LecturerSuggestionDTO {
  id: number;
  code: string;
  full_name: string;
  department_name: string | null;
}

function normalizeParams(params: GlobalResearchWorkSearchFilterDTO) {
  const cleaned: Record<string, string | number> = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined) return;
    if (typeof value === "string" && value.trim() === "") return;
    cleaned[key] = typeof value === "string" ? value.trim() : value;
  });
  return cleaned;
}

function parseFilename(disposition?: string | null): string | null {
  if (!disposition) return null;
  const match =
    /filename\*=UTF-8''([^;]+)|filename=\"?([^\";]+)\"?/i.exec(disposition);
  const raw = match?.[1] ?? match?.[2];
  if (!raw) return null;

  try {
    return decodeURIComponent(raw);
  } catch {
    return raw;
  }
}

function resolveBasePath(scope: ResearchWorkSearchScope) {
  return scope === "lecturer" ? "/api/lecturer/works" : "/api/admin/works";
}

export async function getWorkSearchLookups(
  scope: ResearchWorkSearchScope = "admin"
): Promise<WorkSearchLookupsDTO> {
  const basePath = resolveBasePath(scope);
  const { data } = await http.get<ApiResponse<WorkSearchLookupsDTO>>(
    `${basePath}/lookups`
  );
  return data.data;
}

export async function searchWorks(
  params: GlobalResearchWorkSearchFilterDTO,
  scope: ResearchWorkSearchScope = "admin"
): Promise<WorkSearchListResponseDTO> {
  const basePath = resolveBasePath(scope);
  const { data } = await http.get<ApiResponse<WorkSearchListResponseDTO>>(
    `${basePath}/search`,
    { params: normalizeParams(params) }
  );
  return data.data;
}

export async function getWorkDetail(
  workId: number,
  scope: ResearchWorkSearchScope = "admin"
): Promise<ResearchWorkDetailDTO> {
  const basePath = resolveBasePath(scope);
  const { data } = await http.get<ApiResponse<ResearchWorkDetailDTO>>(
    `${basePath}/${workId}`
  );
  return data.data;
}

export async function getLecturerSuggestions(
  search?: string
): Promise<LecturerSuggestion[]> {
  const { data } = await http.get<{ data: LecturerSuggestionDTO[] }>(
    "/api/lookups/lecturers",
    {
      params: search ? { search } : undefined,
    }
  );
  return data.data.map((item) => ({
    lecturer_id: item.id,
    lecturer_code: item.code,
    lecturer_name: item.full_name,
    unit_name: item.department_name,
  }));
}

export async function downloadWorkAttachment(
  attachmentId: string | number,
  scope: ResearchWorkSearchScope = "admin"
) {
  const basePath = resolveBasePath(scope);
  const response = await http.get(
    `${basePath}/attachments/${attachmentId}/download`,
    { responseType: "blob" }
  );

  const disposition = response.headers?.["content-disposition"] as
    | string
    | undefined;
  const filename =
    parseFilename(disposition) ?? `attachment-${attachmentId}.pdf`;

  return {
    blob: response.data as Blob,
    filename,
  };
}
