import axios from "axios";
import http from "@/lib/http";
import type {
  HoursApprovalBatchDetail,
  HoursApprovalBatchSummary,
  HoursDistributionItem,
  HoursOverview,
} from "../contracts/HoursOverviewContracts";

type OverviewDTO = {
  academic_year_id: number;
  academic_year_code: string;
  required_hours: number;
  approved_hours: number;
  pending_hours: number;
  rejected_hours: number;
};

type DistributionDTO = {
  academic_year_id: number;
  academic_year_code: string;
  total_approved_hours: number;
  items: Array<{
    kind_id: number;
    label: string;
    hours: number;
    percentage: number;
  }>;
};

type BatchSummaryDTO = {
  batch_id: number;
  batch_name: string;
  academic_year_id: number;
  academic_year_code: string;
  status: "approved" | "pending" | "rejected";
  submitted_at: string;
  decided_at: string | null;
  total_hours: number;
};

type BatchDetailDTO = {
  batch_id: number;
  batch_name: string;
  academic_year_id: number;
  academic_year_code: string;
  status: "approved" | "pending" | "rejected";
  submitted_at: string;
  decided_at: string | null;
  total_hours: number;
  items: Array<{
    activity_id: number;
    title: string;
    kind_name: string;
    lecturer_hours: number;
    status: "approved" | "pending" | "rejected";
  }>;
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

export const lecturerHoursOverviewService = {
  async getOverview(params?: {
    academic_year_id?: number;
  }): Promise<HoursOverview> {
    try {
      const response = await http.get<{ data: OverviewDTO }>(
        "/api/lecturer/hours/personal/overview",
        { params }
      );

      const dto = response.data.data;
      return {
        academicYearId: dto.academic_year_id,
        academicYearCode: dto.academic_year_code,
        targetHours: dto.required_hours,
        approvedHours: dto.approved_hours,
        pendingHours: dto.pending_hours,
        rejectedHours: dto.rejected_hours,
      };
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không tải được tổng quan giờ NCKH.")
      );
    }
  },

  async getDistribution(params?: {
    academic_year_id?: number;
  }): Promise<HoursDistributionItem[]> {
    try {
      const response = await http.get<{ data: DistributionDTO }>(
        "/api/lecturer/hours/personal/distribution",
        { params }
      );

      return response.data.data.items.map((item) => ({
        kindId: item.kind_id,
        label: item.label,
        hours: item.hours,
        percentage: item.percentage,
      }));
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không tải được phân bố giờ NCKH.")
      );
    }
  },

  async getBatches(params?: {
    academic_year_id?: number;
    page?: number;
    per_page?: number;
  }): Promise<HoursApprovalBatchSummary[]> {
    try {
      const response = await http.get<{ data: { items: BatchSummaryDTO[] } }>(
        "/api/lecturer/hours/personal/batches",
        { params }
      );

      return response.data.data.items.map((item) => ({
        batchId: item.batch_id,
        batchName: item.batch_name,
        academicYearId: item.academic_year_id,
        academicYearCode: item.academic_year_code,
        status: item.status,
        submittedAt: item.submitted_at,
        decidedAt: item.decided_at,
        totalHours: item.total_hours,
      }));
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không tải được lịch sử xét duyệt.")
      );
    }
  },

  async getBatchDetail(batchId: number): Promise<HoursApprovalBatchDetail> {
    try {
      const response = await http.get<{ data: BatchDetailDTO }>(
        `/api/lecturer/hours/personal/batches/${batchId}`
      );

      const dto = response.data.data;
      return {
        batchId: dto.batch_id,
        batchName: dto.batch_name,
        academicYearId: dto.academic_year_id,
        academicYearCode: dto.academic_year_code,
        status: dto.status,
        submittedAt: dto.submitted_at,
        decidedAt: dto.decided_at,
        totalHours: dto.total_hours,
        items: dto.items.map((item) => ({
          activityId: item.activity_id,
          title: item.title,
          kindName: item.kind_name,
          lecturerHours: item.lecturer_hours,
          status: item.status,
        })),
      };
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không tải được chi tiết đợt xét duyệt.")
      );
    }
  },
};
