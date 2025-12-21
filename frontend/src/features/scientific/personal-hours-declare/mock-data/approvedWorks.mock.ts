import type {
  ApprovedWorkRowDTO,
  WorkDetailDTO,
} from "../contracts/selectHoursRequest.contract";

export const approvedWorkRowDtoListMock: ApprovedWorkRowDTO[] = [
  // Chưa duyệt giờ (eligible)
  {
    activity_id: 201,
    activity_code: "RA-2025-001",
    academic_year_code: "2024-2025",
    title: "Ứng dụng AI trong giáo dục đại học",
    kind_name: "Bài báo",
    member_role_name: "Thành viên",
    hours_assigned: 30,
    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",
    hours_request_state: "eligible",
  },
  {
    activity_id: 202,
    activity_code: "RA-2025-002",
    academic_year_code: "2024-2025",
    title: "Mô hình hoá hệ thống IoT trong y tế",
    kind_name: "Đề tài",
    member_role_name: "Chủ nhiệm",
    hours_assigned: 60,
    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",
    hours_request_state: "eligible",
  },

  // Chờ duyệt giờ (submitted)
  {
    activity_id: 203,
    activity_code: "RA-2025-003",
    academic_year_code: "2024-2025",
    title: "Hệ thống gợi ý học tập cá nhân hoá",
    kind_name: "Bài báo",
    member_role_name: "Tác giả",
    hours_assigned: 40,
    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",
    hours_request_state: "submitted",
  },

  // Đã duyệt giờ (hours_approved)
  {
    activity_id: 204,
    activity_code: "RA-2025-004",
    academic_year_code: "2024-2025",
    title: "Tối ưu hoá lịch học bằng thuật toán",
    kind_name: "Đề tài",
    member_role_name: "Chủ nhiệm",
    hours_assigned: 80,
    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",
    hours_request_state: "hours_approved",
  },

  // thêm dữ liệu để pagination
  {
    activity_id: 205,
    activity_code: "RA-2025-005",
    academic_year_code: "2023-2024",
    title: "Khảo sát mức độ hài lòng người học",
    kind_name: "Bài báo",
    member_role_name: "Thành viên",
    hours_assigned: 20,
    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",
    hours_request_state: "eligible",
  },
  {
    activity_id: 206,
    activity_code: "RA-2025-006",
    academic_year_code: "2023-2024",
    title: "Tổng quan tài liệu về học trực tuyến",
    kind_name: "Sách",
    member_role_name: "Tác giả",
    hours_assigned: null,
    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",
    hours_request_state: "eligible",
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
      rule_summary:
        "Mock rule: Tổng 100 giờ/công trình, Chủ nhiệm 60%, Thành viên chia đều 40%.",
      hours_for_lecturer: row.hours_assigned,
      activity_status_code: row.activity_status_code,
      assistant_approval_status: row.assistant_approval_status,
      manager_approval_status: row.manager_approval_status,
      hours_request_state: row.hours_request_state,
    },
  ])
);

export function getWorkDetailDtoMock(activityId: number): WorkDetailDTO | null {
  return workDetailDtoMap[activityId] ?? null;
}
