// src/features/hours/types.ts
import type { WorkType } from "@/features/declarations/types";

export interface HoursQuota {
  academicYear: string;
  semester?: string;
  requiredHours: number;
}

export interface HoursSummary {
  teacherId: string;
  teacherName: string;
  departmentName?: string;
  quota: HoursQuota;
  completedHours: number;
  pendingHours: number;
}

export interface FacultyHoursItem extends HoursSummary {}

export type HoursWarningLevel = "INFO" | "WARNING" | "CRITICAL";

export interface HoursWarning {
  id: string;
  level: HoursWarningLevel;
  message: string;
  detail?: string;
  suggestedAction?: string;
}

/** Item dùng cho màn duyệt giờ NCKH */
export interface HoursApprovalItem {
  id: string;
  lecturerId: string;
  lecturerName: string;
  departmentName: string;
  workTitle: string;
  workType: WorkType;
  year: number;
  hours: number;
  status: "PENDING" | "APPROVED" | "REJECTED";
}
