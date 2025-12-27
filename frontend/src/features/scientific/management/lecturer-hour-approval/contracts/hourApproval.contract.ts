// UI model camelCase + API DTO snake_case (mock-ready)

export type HourApprovalRequestStatus = "pending" | "approved" | "rejected";

export type HourApprovalRejectReasonCode =
  | "hours_not_reasonable"
  | "work_not_eligible"
  | "missing_evidence"
  | "other";

export interface FacultyOption {
  id: number;
  name: string;
}

export interface HourApprovalFilter {
  facultyId: number | null; // null = all
  status: "all" | HourApprovalRequestStatus;
  submittedFrom: string | null; // YYYY-MM-DD
  submittedTo: string | null; // YYYY-MM-DD
  searchText: string; // lecturer code or name
}

/** ========== DTOs (snake_case) ========== */

export interface HourApprovalRequestSummaryDTO {
  request_id: number;

  lecturer_id: number;
  lecturer_code: string;
  lecturer_full_name: string;

  faculty_id: number;
  faculty_name: string;

  activity_count: number;
  total_hours: number;

  submitted_at: string; // ISO
  status: HourApprovalRequestStatus;
}

export interface HourApprovalRequestItemDTO {
  activity_id: number;
  activity_title: string;
  activity_kind_name: string;
  member_role_name: string;
  hours_converted: number;
}

export interface HourApprovalRequestDetailDTO {
  request_id: number;

  lecturer_id: number;
  lecturer_code: string;
  lecturer_full_name: string;

  faculty_id: number;
  faculty_name: string;

  submitted_at: string; // ISO
  status: HourApprovalRequestStatus;

  note_from_lecturer: string | null;

  activity_count: number;
  total_hours: number;

  items: HourApprovalRequestItemDTO[];
}

export interface RejectPayloadDTO {
  reason_code: HourApprovalRejectReasonCode;
  reason_note: string | null;
}

/** ========== Models (camelCase) ========== */

export interface HourApprovalRequestSummary {
  requestId: number;

  lecturerId: number;
  lecturerCode: string;
  lecturerFullName: string;

  facultyId: number;
  facultyName: string;

  activityCount: number;
  totalHours: number;

  submittedAt: string; // ISO
  status: HourApprovalRequestStatus;
}

export interface HourApprovalRequestItem {
  activityId: number;
  activityTitle: string;
  activityKindName: string;
  memberRoleName: string;
  hoursConverted: number;
}

export interface HourApprovalRequestDetail {
  requestId: number;

  lecturerId: number;
  lecturerCode: string;
  lecturerFullName: string;

  facultyId: number;
  facultyName: string;

  submittedAt: string;
  status: HourApprovalRequestStatus;

  noteFromLecturer: string | null;

  activityCount: number;
  totalHours: number;

  items: HourApprovalRequestItem[];
}

export interface RejectPayload {
  reasonCode: HourApprovalRejectReasonCode;
  reasonNote: string | null;
}

/** ========== Mappers ========== */

export const hourApprovalMappers = {
  summaryFromDto(
    dto: HourApprovalRequestSummaryDTO
  ): HourApprovalRequestSummary {
    return {
      requestId: dto.request_id,
      lecturerId: dto.lecturer_id,
      lecturerCode: dto.lecturer_code,
      lecturerFullName: dto.lecturer_full_name,
      facultyId: dto.faculty_id,
      facultyName: dto.faculty_name,
      activityCount: dto.activity_count,
      totalHours: dto.total_hours,
      submittedAt: dto.submitted_at,
      status: dto.status,
    };
  },

  detailFromDto(dto: HourApprovalRequestDetailDTO): HourApprovalRequestDetail {
    return {
      requestId: dto.request_id,
      lecturerId: dto.lecturer_id,
      lecturerCode: dto.lecturer_code,
      lecturerFullName: dto.lecturer_full_name,
      facultyId: dto.faculty_id,
      facultyName: dto.faculty_name,
      submittedAt: dto.submitted_at,
      status: dto.status,
      noteFromLecturer: dto.note_from_lecturer,
      activityCount: dto.activity_count,
      totalHours: dto.total_hours,
      items: dto.items.map((item) => ({
        activityId: item.activity_id,
        activityTitle: item.activity_title,
        activityKindName: item.activity_kind_name,
        memberRoleName: item.member_role_name,
        hoursConverted: item.hours_converted,
      })),
    };
  },

  rejectPayloadToDto(payload: RejectPayload): RejectPayloadDTO {
    return {
      reason_code: payload.reasonCode,
      reason_note: payload.reasonNote,
    };
  },
};

/** ========== Small utils ========== */

export function formatDateTimeVietnamese(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  const dd = String(date.getDate()).padStart(2, "0");
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yyyy = date.getFullYear();

  const hh = String(date.getHours()).padStart(2, "0");
  const min = String(date.getMinutes()).padStart(2, "0");

  return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

export function formatDateVietnamese(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  const dd = String(date.getDate()).padStart(2, "0");
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yyyy = date.getFullYear();

  return `${dd}/${mm}/${yyyy}`;
}
