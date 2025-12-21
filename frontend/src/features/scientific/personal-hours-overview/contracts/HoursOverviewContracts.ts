// File: src/features/lecturer-hours-overview/contracts/lecturerHoursOverviewContracts.ts

// NOTE: Các field "batch" là DTO derived / mock vì schema hiện chưa có entity batch.
// TODO(BE): cần endpoint/DTO computed hoặc thêm bảng hour_approval_batches.

export type HoursBatchStatus = "approved" | "pending" | "rejected";

export interface HoursOverview {
  academicYearId: number;
  academicYearCode: string;

  targetHours: number;
  approvedHours: number;
  pendingHours: number;
  rejectedHours: number;
}

export interface HoursDistributionItem {
  kindId: number;
  label: string;
  hours: number;
  percentage: number; // 0..100
}

export interface HoursApprovalBatchSummary {
  batchId: number;
  batchName: string;

  academicYearId: number;
  academicYearCode: string;

  status: HoursBatchStatus;
  submittedAt: string; // ISO
  decidedAt: string | null; // ISO
  totalHours: number;
}

export interface HoursApprovalBatchItem {
  activityId: number;
  title: string;
  kindName: string;
  lecturerHours: number;
  status: HoursBatchStatus;
}

export interface HoursApprovalBatchDetail {
  batchId: number;
  batchName: string;

  academicYearId: number;
  academicYearCode: string;

  status: HoursBatchStatus;
  submittedAt: string;
  decidedAt: string | null;

  totalHours: number;
  items: HoursApprovalBatchItem[];
}
