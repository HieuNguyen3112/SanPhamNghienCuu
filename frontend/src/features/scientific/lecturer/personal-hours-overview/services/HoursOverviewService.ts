import axios from "axios";
import http from "@/lib/http";
import type {
  HoursApprovalBatchDetail,
  HoursApprovalBatchSummary,
  HoursDistributionItem,
  HoursOverview,
  HoursOverviewMode,
} from "../contracts/HoursOverviewContracts";

export interface AcademicYearOption {
  id: number;
  code: string;
  isActive: boolean;
  isCurrent: boolean;
}

export interface HoursOverviewFilter {
  academic_year_id?: number;
  mode?: HoursOverviewMode;
}

type OverviewDTO = {
  mode?: HoursOverviewMode;
  academic_year_id: number | null;
  academic_year_code: string;
  required_hours: number;
  approved_hours: number;
  pending_hours: number;
  rejected_hours: number;
};

type DistributionDTO = {
  mode?: HoursOverviewMode;
  academic_year_id: number | null;
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
  academic_year_id: number | null;
  academic_year_code: string | null;
  status: "approved" | "pending" | "rejected";
  submitted_at: string;
  decided_at: string | null;
  total_hours: number;
};

type BatchListDTO = {
  mode?: HoursOverviewMode;
  items: BatchSummaryDTO[];
};

type BatchDetailDTO = {
  batch_id: number;
  batch_name: string;
  academic_year_id: number | null;
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

type AcademicYearLookupDTO = {
  id: number;
  code: string;
  is_active: boolean;
  is_current: boolean;
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

function buildFilterParams(filter?: HoursOverviewFilter) {
  if (!filter) return undefined;

  const params: Record<string, string | number> = {};
  if (filter.mode === "overall") {
    params.mode = "overall";
  }
  if (typeof filter.academic_year_id === "number") {
    params.academic_year_id = filter.academic_year_id;
  }

  return Object.keys(params).length > 0 ? params : undefined;
}

export const lecturerHoursOverviewService = {
  async getAcademicYears(): Promise<AcademicYearOption[]> {
    try {
      const response = await http.get<{ data: AcademicYearLookupDTO[] }>(
        "/api/lookups/academic-years"
      );

      return response.data.data.map((item) => ({
        id: item.id,
        code: item.code,
        isActive: Boolean(item.is_active),
        isCurrent: Boolean(item.is_current),
      }));
    } catch (err) {
      throw new Error(
        extractErrorMessage(err, "Không tải được danh sách năm học.")
      );
    }
  },

  async getOverview(filter?: HoursOverviewFilter): Promise<HoursOverview> {
    try {
      const response = await http.get<{ data: OverviewDTO }>(
        "/api/lecturer/hours/personal/overview",
        { params: buildFilterParams(filter) }
      );

      const dto = response.data.data;
      return {
        mode: dto.mode ?? "year",
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

  async getDistribution(filter?: HoursOverviewFilter): Promise<HoursDistributionItem[]> {
    try {
      const response = await http.get<{ data: DistributionDTO }>(
        "/api/lecturer/hours/personal/distribution",
        { params: buildFilterParams(filter) }
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

  async getBatches(
    filter?: HoursOverviewFilter & {
      page?: number;
      per_page?: number;
    }
  ): Promise<HoursApprovalBatchSummary[]> {
    try {
      const params = {
        ...buildFilterParams(filter),
        page: filter?.page,
        per_page: filter?.per_page,
      };

      const response = await http.get<{ data: BatchListDTO }>(
        "/api/lecturer/hours/personal/batches",
        { params }
      );

      const mode = response.data.data.mode ?? "year";
      return response.data.data.items.map((item) => ({
        mode,
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