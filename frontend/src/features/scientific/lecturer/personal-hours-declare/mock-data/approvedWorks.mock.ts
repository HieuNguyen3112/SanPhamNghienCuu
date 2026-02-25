import type {
  ApprovedWorkRowDTO,
  WorkDetailDTO,
} from "../contracts/selectHoursRequest.contract";

export const approvedWorkRowDtoListMock: ApprovedWorkRowDTO[] = [
  {
    activity_id: 201,
    activity_code: "ACT-2025-001",
    academic_year_code: "2024-2025",
    title: "Ứng dụng AI trong giáo dục đại học",
    kind_name: "Bài báo",
    member_role_name: "Thành viên",
    hours_assigned: 30,
    activity_status_code: "approved",
    hours_request_state: "hours_not_submitted",
    next_action_code: "submit_hours",
    next_action_text: "Gửi duyệt giờ",
  },
  {
    activity_id: 202,
    activity_code: "ACT-2025-002",
    academic_year_code: "2024-2025",
    title: "Mô hình hóa hệ thống IoT trong y tế",
    kind_name: "Đề tài",
    member_role_name: "Chủ nhiệm",
    hours_assigned: 60,
    activity_status_code: "approved",
    hours_request_state: "hours_pending_faculty",
    next_action_code: "wait_faculty",
    next_action_text: "Chờ khoa duyệt giờ",
  },
  {
    activity_id: 203,
    activity_code: "ACT-2025-003",
    academic_year_code: "2024-2025",
    title: "Hệ thống gợi ý học tập cá nhân hóa",
    kind_name: "Bài báo",
    member_role_name: "Tác giả",
    hours_assigned: 40,
    activity_status_code: "approved",
    hours_request_state: "hours_approved",
    next_action_code: "none",
    next_action_text: "Đã duyệt",
  },
  {
    activity_id: 204,
    activity_code: "ACT-2025-004",
    academic_year_code: "2023-2024",
    title: "Khảo sát mức độ hài lòng người học",
    kind_name: "Bài báo",
    member_role_name: "Thành viên",
    hours_assigned: 20,
    activity_status_code: "approved",
    hours_request_state: "hours_rejected",
    hours_rejection_reason: "Thiếu biên bản nghiệm thu.",
    next_action_code: "resubmit_hours",
    next_action_text: "Điều chỉnh và gửi lại duyệt giờ",
  },
];

const workDetailDtoMap: Record<number, WorkDetailDTO> = Object.fromEntries(
  approvedWorkRowDtoListMock.map((row) => [
    row.activity_id,
    {
      activity_id: row.activity_id,
      title: row.title,
      kind_name: row.kind_name,
      academic_year_code: row.academic_year_code,
      publication_or_unit: "Tạp chí/Đơn vị (mock)",
      member_role_name: row.member_role_name,
      contribution_share: 0.6,
      rule_summary: "Mock rule: tổng 100 giờ/công trình.",
      hours_for_lecturer: row.hours_assigned,
      activity_status_code: row.activity_status_code,
      hours_request_state: row.hours_request_state,
      hours_rejection_reason: row.hours_rejection_reason ?? null,
      next_action_code: row.next_action_code ?? null,
      next_action_text: row.next_action_text ?? null,
    },
  ])
);

export function getWorkDetailDtoMock(activityId: number): WorkDetailDTO | null {
  return workDetailDtoMap[activityId] ?? null;
}
