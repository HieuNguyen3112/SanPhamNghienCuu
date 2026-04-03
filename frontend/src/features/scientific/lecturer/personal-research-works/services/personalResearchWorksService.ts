import axios from "axios";
import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  PersonalWorkDetailDTO,
  PersonalWorkIndexResponseDTO,
} from "../contracts/personalResearchWorksContracts";

export interface PersonalWorksIndexParams {
  status?: string;
  q?: string;
  year?: number;
  academic_year_id?: number;
  kind_id?: number;
  type_id?: number;
  role_id?: number;
  sort?: string;
  page?: number;
  per_page?: number;
}

const normalizeText = (value: string): string =>
  value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "");

const normalizeWorkStatusParam = (status?: string): string | undefined => {
  if (!status || !status.trim()) return undefined;

  const normalized = normalizeText(status);
  switch (normalized) {
    case "all":
    case "tat_ca":
      return "all";
    case "pending":
      return "pending";
    case "draft":
    case "ban_nhap":
      return "draft";
    case "pending_member_confirm":
    case "cho_thanh_vien_xac_nhan":
      return "pending_member_confirm";
    case "member_rejected":
    case "thanh_vien_tu_choi":
      return "member_rejected";
    case "pending_faculty_review":
    case "cho_khoa_duyet":
      return "pending_faculty_review";
    case "need_revision":
    case "can_chinh_sua":
    case "yeu_cau_chinh_sua":
      return "need_revision";
    case "submitted":
    case "da_gui_duyet":
      return "pending_faculty_review";
    case "approved":
    case "da_duyet":
    case "khoa_duyet":
      return "approved";
    case "rejected":
    case "tu_choi":
    case "khoa_tu_choi":
      return "rejected";
    default:
      return status;
  }
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

export const personalResearchWorksService = {
  async getIndex(
    params: PersonalWorksIndexParams,
  ): Promise<PersonalWorkIndexResponseDTO> {
    try {
      const response = await http.get<{ data: PersonalWorkIndexResponseDTO }>(
        "/api/lecturer/works/my",
        {
          params: {
            ...params,
            status: normalizeWorkStatusParam(params.status),
          },
        },
      );

      return response.data.data;
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không tải được danh sách công trình."),
      );
    }
  },

  async getDetail(activityId: number): Promise<PersonalWorkDetailDTO> {
    try {
      const response = await http.get<{ data: PersonalWorkDetailDTO }>(
        `/api/lecturer/works/my/${activityId}`,
      );

      return response.data.data;
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không tải được chi tiết công trình."),
      );
    }
  },

  async reinviteMember(activityId: number, memberId: number): Promise<void> {
    try {
      await ensureCsrfCookie();
      await http.post(
        `/api/research-activities/${activityId}/members/${memberId}/reinvite`,
      );
    } catch (err) {
      throw new Error(
        extractErrorMessage(
          err,
          "Không thể gửi lại yêu cầu xác nhận tham gia.",
        ),
      );
    }
  },

  async resendPendingInvitation(
    activityId: number,
    memberId: number,
  ): Promise<void> {
    try {
      await ensureCsrfCookie();
      await http.post(
        `/api/research-activities/${activityId}/members/${memberId}/pending/resend`,
      );
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không thể gửi lại lời mời xác nhận."),
      );
    }
  },

  async removePendingMember(
    activityId: number,
    memberId: number,
  ): Promise<void> {
    try {
      await ensureCsrfCookie();
      await http.delete(
        `/api/research-activities/${activityId}/members/${memberId}/pending`,
      );
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không thể xóa thành viên đang chờ xác nhận."),
      );
    }
  },
};
