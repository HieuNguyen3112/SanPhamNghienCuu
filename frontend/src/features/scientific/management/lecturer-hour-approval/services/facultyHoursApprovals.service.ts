import http from "@/lib/http";
import type {
  ApprovePayloadDTO,
  HourApprovalFilter,
  HourApprovalListResponseDTO,
  HourApprovalRequestDetailDTO,
  RejectPayloadDTO,
} from "../contracts/hourApproval.contract";
import type { HourApprovalService } from "./hourApprovalService";

function normalizeParams(params: Record<string, string | number | null>) {
  const cleaned: Record<string, string | number> = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined) return;
    if (typeof value === "string" && value.trim() === "") return;
    cleaned[key] = typeof value === "string" ? value.trim() : value;
  });
  return cleaned;
}

export function createFacultyHourApprovalService(): HourApprovalService {
  return {
    async getRequests(filter: HourApprovalFilter, page: number, perPage: number) {
      const params = normalizeParams({
        faculty_id: filter.facultyId,
        academic_year_id: filter.academicYearId,
        include_all_years: filter.academicYearId === null ? 1 : null,
        status: filter.status === "all" ? null : filter.status,
        from_date: filter.submittedFrom,
        to_date: filter.submittedTo,
        keyword: filter.searchText,
        page,
        per_page: perPage,
      });

      const { data } = await http.get<HourApprovalListResponseDTO>(
        "/api/faculty/hours/approvals",
        { params }
      );
      return data;
    },

    async getRequestDetail(requestId: number) {
      const { data } = await http.get<{ data: HourApprovalRequestDetailDTO }>(
        `/api/faculty/hours/approvals/${requestId}`
      );
      return data.data;
    },

    async approve(requestId: number, payload?: ApprovePayloadDTO) {
      const { data } = await http.put<{ data: HourApprovalRequestDetailDTO }>(
        `/api/faculty/hours/approvals/${requestId}/approve`,
        payload ?? {}
      );
      return data.data;
    },

    async reject(requestId: number, payload: RejectPayloadDTO) {
      const { data } = await http.put<{ data: HourApprovalRequestDetailDTO }>(
        `/api/faculty/hours/approvals/${requestId}/reject`,
        payload
      );
      return data.data;
    },
  };
}
