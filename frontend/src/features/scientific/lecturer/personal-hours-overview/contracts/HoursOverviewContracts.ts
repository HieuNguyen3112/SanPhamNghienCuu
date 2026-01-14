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
  percentage: number;
}

export interface HoursApprovalBatchSummary {
  batchId: number;
  batchName: string;

  academicYearId: number;
  academicYearCode: string;

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

  academicYearId: number;
  academicYearCode: string;

  status: HoursBatchStatus;
  submittedAt: string;
  decidedAt: string | null;

  totalHours: number;
  items: HoursApprovalBatchItem[];
}
