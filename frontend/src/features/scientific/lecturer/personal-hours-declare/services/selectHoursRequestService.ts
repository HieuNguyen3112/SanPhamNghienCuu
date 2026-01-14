import axios from "axios";
import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  ApprovedWorkListResponseDTO,
  WorkDetailDTO,
} from "../contracts/selectHoursRequest.contract";

export interface LoadApprovedWorksParams {
  status?: string;
  q?: string;
  page?: number;
  per_page?: number;
}

const extractErrorMessage = (err: unknown, fallback: string) => {
  if (axios.isAxiosError(err)) {
    const status = err.response?.status ?? 0;
    if (status >= 500) return fallback;
    const message = err.response?.data?.message;
    if (typeof message === "string" && message.trim()) return message;
  }

  return err instanceof Error ? err.message : fallback;
};

export async function loadApprovedWorksDTO(
  params: LoadApprovedWorksParams
): Promise<ApprovedWorkListResponseDTO> {
  try {
    const cleanedParams: Record<string, unknown> = {
      status: params.status ?? "all",
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

export async function loadWorkDetailDTO(
  activityId: number
): Promise<WorkDetailDTO> {
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
    await http.post("/api/lecturer/hours/calculate/submit", payload);
  } catch (err) {
    throw new Error(
      extractErrorMessage(err, "Không gửi được yêu cầu xét duyệt giờ NCKH.")
    );
  }
}
