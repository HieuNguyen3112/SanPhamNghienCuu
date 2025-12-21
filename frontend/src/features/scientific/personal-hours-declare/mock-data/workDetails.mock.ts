// File: src/features/scientific/personal-hours/mock-data/workDetails.mock.ts
import type { WorkDetailDTO } from "../contracts/selectHoursRequest.contract";

export const workDetailsMockDTO: Record<number, WorkDetailDTO> = {
  101: {
    activity_id: 101,
    title: "AI trong giáo dục đại học",
    kind_name: "Bài báo",
    academic_year_code: "2024-2025",
    publication_or_unit: "Tạp chí Khoa học Giáo dục (mock)",
    member_role_name: "Tác giả",
    contribution_share: 0.6,
    rule_summary:
      "Bài báo: 100 giờ / bài. Tác giả chính nhận 60%, đồng tác giả chia đều 40% (mock).",
    hours_for_lecturer: 40,

    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",

    // ✅ trạng thái DUYỆT GIỜ
    hours_request_state: "eligible",
  },

  102: {
    activity_id: 102,
    title: "IoT cho y tế thông minh",
    kind_name: "Đề tài",
    academic_year_code: "2024-2025",
    publication_or_unit: "Mã đề tài: DT-2024-02 (mock)",
    member_role_name: "Chủ nhiệm",
    contribution_share: 1,
    rule_summary:
      "Đề tài: 120 giờ / đề tài. Chủ nhiệm nhận 50%, còn lại phân bổ theo vai trò (mock).",
    hours_for_lecturer: 60,

    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",

    hours_request_state: "submitted",
  },

  103: {
    activity_id: 103,
    title: "Hội thảo chuyển đổi số 2025",
    kind_name: "Hội thảo",
    academic_year_code: "2024-2025",
    publication_or_unit: "Địa điểm: Hà Nội (mock)",
    member_role_name: "Thành viên",
    contribution_share: 0.4,
    rule_summary:
      "Hội thảo: 30 giờ / lần tham dự. Thành viên nhận theo tỷ lệ (mock).",
    hours_for_lecturer: 20,

    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",

    hours_request_state: "hours_approved",
  },

  104: {
    activity_id: 104,
    title: "Giáo trình Nhập môn Khoa học Dữ liệu",
    kind_name: "Sách",
    academic_year_code: "2023-2024",
    publication_or_unit: "NXB Giáo dục (mock)",
    member_role_name: "Chủ biên",
    contribution_share: 0.8,
    rule_summary:
      "Sách/giáo trình: 200 giờ / cuốn. Chủ biên nhận 60–80% tuỳ quy định (mock).",
    hours_for_lecturer: 80,

    activity_status_code: "approved",
    assistant_approval_status: "approved",
    manager_approval_status: "approved",

    hours_request_state: "eligible",
  },
};
