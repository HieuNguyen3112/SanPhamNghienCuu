import axios from "axios";
import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  HoursWarningsResponseDTO,
  HoursWarningSeenResponseDTO,
} from "../contracts/hoursWarning.contract";

type ApiResponse<T> = {
  data: T;
  message?: string;
  success?: boolean;
};

const extractErrorMessage = (err: unknown, fallback: string) => {
  if (axios.isAxiosError(err)) {
    const status = err.response?.status ?? 0;
    if (status >= 500) return fallback;
    const message = err.response?.data?.message;
    if (typeof message === "string" && message.trim()) return message;
  }

  return err instanceof Error ? err.message : fallback;
};

export async function fetchHoursWarnings(params?: {
  academic_year_id?: number;
  tab?: "all" | "danger" | "warning" | "done";
  page?: number;
  per_page?: number;
}): Promise<HoursWarningsResponseDTO> {
  try {
    const { data } = await http.get<ApiResponse<HoursWarningsResponseDTO>>(
      "/api/lecturer/hours/warnings",
      { params }
    );
    return data.data;
  } catch (err) {
    throw new Error(
      extractErrorMessage(
        err,
        "Không tải được cảnh báo giờ NCKH. Vui lòng thử lại."
      )
    );
  }
}

export async function markHoursWarningSeen(
  warningId: number
): Promise<HoursWarningSeenResponseDTO> {
  try {
    await ensureCsrfCookie();
    const { data } = await http.patch<ApiResponse<HoursWarningSeenResponseDTO>>(
      `/api/lecturer/hours/warnings/${warningId}/seen`,
      { seen: true }
    );
    return data.data;
  } catch (err) {
    throw new Error(
      extractErrorMessage(err, "Không thể cập nhật trạng thái cảnh báo.")
    );
  }
}
