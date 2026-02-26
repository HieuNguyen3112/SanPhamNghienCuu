export type HoursBatchStatus = "approved" | "pending" | "rejected";
export type HoursOverviewMode = "year" | "overall";

export interface HoursOverview {
  mode: HoursOverviewMode;
  academicYearId: number | null;
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
  percentage: number;
}

export interface HoursApprovalBatchSummary {
  batchId: number;
  batchName: string;

  mode: HoursOverviewMode;
  academicYearId: number | null;
  academicYearCode: string | null;

  status: HoursBatchStatus;
  submittedAt: string;
  decidedAt: string | null;
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

  academicYearId: number | null;
  academicYearCode: string;

  status: HoursBatchStatus;
  submittedAt: string;
  decidedAt: string | null;

  totalHours: number;
  items: HoursApprovalBatchItem[];
}
