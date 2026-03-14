import axios from "axios";
import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  AcademicYearOptionDTO,
  ApprovedWorkListResponseDTO,
  EvidenceFileDTO,
  EvidenceFileTypeDTO,
  WorkDetailDTO,
} from "../contracts/selectHoursRequest.contract";

type ApiValidationErrors = Record<string, string[]>;

export interface HoursRequestApiErrorShape {
  status: number;
  code: string | null;
  message: string;
  errors: ApiValidationErrors;
  invalidActivityIds: number[];
  invalidItems: unknown[];
}

export class HoursRequestApiError extends Error {
  status: number;

  code: string | null;

  errors: ApiValidationErrors;

  invalidActivityIds: number[];

  invalidItems: unknown[];

  constructor(payload: HoursRequestApiErrorShape) {
    super(payload.message);
    this.name = "HoursRequestApiError";
    this.status = payload.status;
    this.code = payload.code;
    this.errors = payload.errors;
    this.invalidActivityIds = payload.invalidActivityIds;
    this.invalidItems = payload.invalidItems;
  }
}

export function isHoursRequestApiError(
  error: unknown
): error is HoursRequestApiError {
  return error instanceof HoursRequestApiError;
}

export interface LoadApprovedWorksParams {
  academic_year_id?: number;
  include_all_years?: boolean;
  status?: string;
  q?: string;
  page?: number;
  per_page?: number;
  missing_evidence_only?: boolean;
}

function extractValidationErrors(data: unknown): ApiValidationErrors {
  if (!data || typeof data !== "object") {
    return {};
  }

  const candidate = (data as { errors?: unknown }).errors;
  if (!candidate || typeof candidate !== "object") {
    return {};
  }

  return Object.fromEntries(
    Object.entries(candidate as Record<string, unknown>).map(([key, value]) => [
      key,
      Array.isArray(value)
        ? value.filter((item): item is string => typeof item === "string")
        : [],
    ])
  );
}

function extractMessageFromValidationErrors(
  errors: ApiValidationErrors
): string | null {
  const firstFieldErrors = Object.values(errors)[0];
  if (!Array.isArray(firstFieldErrors) || firstFieldErrors.length === 0) {
    return null;
  }

  const firstMessage = firstFieldErrors.find(
    (item) => typeof item === "string" && item.trim()
  );

  return firstMessage?.trim() ?? null;
}

function toNumberArray(value: unknown): number[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((item) => Number(item))
    .filter((item) => Number.isInteger(item) && item > 0);
}

function toHoursRequestApiError(
  err: unknown,
  fallback: string
): HoursRequestApiError {
  if (axios.isAxiosError(err)) {
    const status = err.response?.status ?? 0;
    const data = err.response?.data;
    const payload =
      data && typeof data === "object"
        ? (data as Record<string, unknown>)
        : null;
    const errors = extractValidationErrors(data);
    const messageFromErrors = extractMessageFromValidationErrors(errors);
    const messageFromResponse =
      payload && typeof payload.message === "string"
        ? payload.message.trim()
        : "";

    return new HoursRequestApiError({
      status,
      code: payload && typeof payload.code === "string" ? payload.code : null,
      message:
        messageFromErrors ||
        messageFromResponse ||
        err.message ||
        fallback,
      errors,
      invalidActivityIds: payload ? toNumberArray(payload.invalid_activity_ids) : [],
      invalidItems:
        payload && Array.isArray(payload.invalid_items)
          ? payload.invalid_items
          : [],
    });
  }

  if (err instanceof HoursRequestApiError) {
    return err;
  }

  return new HoursRequestApiError({
    status: 0,
    code: null,
    message: err instanceof Error && err.message.trim() ? err.message : fallback,
    errors: {},
    invalidActivityIds: [],
    invalidItems: [],
  });
}

export async function loadApprovedWorksDTO(
  params: LoadApprovedWorksParams
): Promise<ApprovedWorkListResponseDTO> {
  try {
    const cleanedParams: Record<string, unknown> = {
      academic_year_id: params.academic_year_id,
      include_all_years: params.include_all_years ? 1 : undefined,
      status: params.status,
      q: params.q,
      page: params.page,
      per_page: params.per_page,
      missing_evidence_only: params.missing_evidence_only ? 1 : undefined,
    };

    const response = await http.get<{ data: ApprovedWorkListResponseDTO }>(
      "/api/lecturer/hours/calculate",
      { params: cleanedParams }
    );

    return response.data.data;
  } catch (err) {
    throw toHoursRequestApiError(err, "Không tải được danh sách.");
  }
}

export async function loadAcademicYearsDTO(): Promise<AcademicYearOptionDTO[]> {
  try {
    const response = await http.get<{ data: AcademicYearOptionDTO[] }>(
      "/api/lookups/academic-years"
    );

    return response.data.data ?? [];
  } catch (err) {
    throw toHoursRequestApiError(err, "Không tải được năm học.");
  }
}

export async function loadWorkDetailDTO(activityId: number): Promise<WorkDetailDTO> {
  try {
    const response = await http.get<{ data: WorkDetailDTO }>(
      `/api/lecturer/hours/calculate/${activityId}`
    );

    return response.data.data;
  } catch (err) {
    throw toHoursRequestApiError(err, "Không tải được chi tiết.");
  }
}

export async function submitHoursApprovalRequestDTO(payload: {
  activity_ids: number[];
}): Promise<void> {
  try {
    await ensureCsrfCookie();
    await http.post("/api/lecturer/hours/submit", payload);
  } catch (err) {
    throw toHoursRequestApiError(
      err,
      "Không gửi được yêu cầu xét duyệt giờ NCKH."
    );
  }
}

export async function loadEvidenceFileTypesDTO(): Promise<EvidenceFileTypeDTO[]> {
  try {
    const response = await http.get<{ data: EvidenceFileTypeDTO[] }>(
      "/api/lookups/evidence-file-types"
    );

    return response.data.data ?? [];
  } catch (err) {
    throw toHoursRequestApiError(err, "Không tải được loại minh chứng.");
  }
}

export async function loadHoursEvidenceDTO(activityId: number): Promise<EvidenceFileDTO[]> {
  try {
    const response = await http.get<{ data: EvidenceFileDTO[] }>(
      `/api/lecturer/hours/calculate/${activityId}/evidence`
    );

    return response.data.data ?? [];
  } catch (err) {
    throw toHoursRequestApiError(
      err,
      "Không tải được danh sách minh chứng."
    );
  }
}

export async function uploadHoursEvidenceDTO(payload: {
  activityId: number;
  fileTypeId: number;
  file: File;
}): Promise<EvidenceFileDTO> {
  try {
    await ensureCsrfCookie();
    const form = new FormData();
    form.append("file", payload.file);
    form.append("file_type_id", String(payload.fileTypeId));

    const response = await http.post<{ data: EvidenceFileDTO }>(
      `/api/lecturer/hours/calculate/${payload.activityId}/evidence`,
      form,
      {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      }
    );

    return response.data.data;
  } catch (err) {
    throw toHoursRequestApiError(err, "Không tải lên được minh chứng.");
  }
}

export async function deleteHoursEvidenceDTO(evidenceId: number): Promise<void> {
  try {
    await ensureCsrfCookie();
    await http.delete(`/api/lecturer/hours/evidence/${evidenceId}`);
  } catch (err) {
    throw toHoursRequestApiError(err, "Không xóa được minh chứng.");
  }
}
