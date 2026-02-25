import axios from "axios";
import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  AcademicYearOptionDTO,
  ApprovedWorkListResponseDTO,
  EvidenceFileDTO,
  EvidenceFileTypeDTO,
  WorkDetailDTO,
} from "../contracts/selectHoursRequest.contract";

export interface LoadApprovedWorksParams {
  academic_year_id?: number;
  status?: string;
  q?: string;
  page?: number;
  per_page?: number;
}

const extractErrorMessage = (err: unknown, fallback: string) => {
  if (axios.isAxiosError(err)) {
    const status = err.response?.status ?? 0;
    if (status >= 500) return fallback;

    const validationErrors = err.response?.data?.errors;
    if (validationErrors && typeof validationErrors === "object") {
      const firstFieldErrors = Object.values(validationErrors)[0];
      if (Array.isArray(firstFieldErrors) && firstFieldErrors.length > 0) {
        const firstMessage = firstFieldErrors.find(
          (item) => typeof item === "string" && item.trim()
        );
        if (firstMessage) return firstMessage;
      }
    }

    const message = err.response?.data?.message;
    if (typeof message === "string" && message.trim()) return message;
  }

  return err instanceof Error ? err.message : fallback;
};

export async function loadApprovedWorksDTO(
  params: LoadApprovedWorksParams
): Promise<ApprovedWorkListResponseDTO> {
  try {
    const normalizedStatus =
      !params.status || params.status === "all" ? undefined : params.status;
    const includeAllYears =
      params.academic_year_id === undefined || params.academic_year_id === null;

    const cleanedParams: Record<string, unknown> = {
      academic_year_id: params.academic_year_id,
      include_all_years: includeAllYears ? 1 : undefined,
      status: normalizedStatus,
      q: params.q?.trim() || undefined,
      page: params.page,
      per_page: params.per_page,
    };

    const response = await http.get<{ data: ApprovedWorkListResponseDTO }>(
      "/api/lecturer/hours/calculate",
      { params: cleanedParams }
    );

    return response.data.data;
  } catch (err) {
    throw new Error(extractErrorMessage(err, "Không tải được danh sách."));
  }
}

export async function loadAcademicYearsDTO(): Promise<AcademicYearOptionDTO[]> {
  try {
    const response = await http.get<{ data: AcademicYearOptionDTO[] }>(
      "/api/lookups/academic-years"
    );
    return response.data.data ?? [];
  } catch (err) {
    throw new Error(extractErrorMessage(err, "Không tải được năm học."));
  }
}

export async function loadWorkDetailDTO(activityId: number): Promise<WorkDetailDTO> {
  try {
    const response = await http.get<{ data: WorkDetailDTO }>(
      `/api/lecturer/hours/calculate/${activityId}`
    );

    return response.data.data;
  } catch (err) {
    throw new Error(extractErrorMessage(err, "Không tải được chi tiết."));
  }
}

export async function submitHoursApprovalRequestDTO(payload: {
  activity_ids: number[];
}): Promise<void> {
  try {
    await ensureCsrfCookie();
    await http.post("/api/lecturer/hours/submit", payload);
  } catch (err) {
    throw new Error(
      extractErrorMessage(err, "Không gửi được yêu cầu xét duyệt giờ NCKH.")
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
    throw new Error(extractErrorMessage(err, "Không tải được loại minh chứng."));
  }
}

export async function loadHoursEvidenceDTO(activityId: number): Promise<EvidenceFileDTO[]> {
  try {
    const response = await http.get<{ data: EvidenceFileDTO[] }>(
      `/api/lecturer/hours/calculate/${activityId}/evidence`
    );
    return response.data.data ?? [];
  } catch (err) {
    throw new Error(extractErrorMessage(err, "Không tải được danh sách minh chứng."));
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
    throw new Error(extractErrorMessage(err, "Không tải lên được minh chứng."));
  }
}

export async function deleteHoursEvidenceDTO(evidenceId: number): Promise<void> {
  try {
    await ensureCsrfCookie();
    await http.delete(`/api/lecturer/hours/evidence/${evidenceId}`);
  } catch (err) {
    throw new Error(extractErrorMessage(err, "Không xóa được minh chứng."));
  }
}
