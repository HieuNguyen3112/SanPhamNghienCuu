// src/features/works/types.ts
import type { WorkType } from "@/features/declarations/types";

export type WorkStatus =
  | "ONGOING" // đang thực hiện
  | "COMPLETED" // đã hoàn thành
  | "PENDING_APPROVAL" // chờ duyệt
  | "DRAFT"; // bản nháp / chưa gửi duyệt

export interface WorkItem {
  id: string;
  title: string;
  workType: WorkType;
  status: WorkStatus;
  startYear?: number | null;
  endYear?: number | null;
  role: "" | "MAIN" | "CO";
  totalHours?: number | null; // tổng giờ NCKH được tính
  approved?: boolean;
  // sau này có thể thêm các field như: department, code, journalName, ...
}

export interface FetchMyWorksParams {
  status?: WorkStatus | "ALL";
  search?: string;
  workType?: WorkType | "ALL";
  page?: number;
  pageSize?: number;
}

export interface PagedResult<T> {
  items: T[];
  totalItems: number;
  page: number;
  pageSize: number;
}
