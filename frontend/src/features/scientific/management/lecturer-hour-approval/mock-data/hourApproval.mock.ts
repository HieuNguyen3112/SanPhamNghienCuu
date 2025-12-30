import type {
  HourApprovalRequestDetailDTO,
  HourApprovalRequestSummaryDTO,
} from "../contracts/hourApproval.contract";

type HourApprovalMockDatabase = {
  summaries: HourApprovalRequestSummaryDTO[];
  detailsById: Record<number, HourApprovalRequestDetailDTO>;
};

function iso(daysAgo: number, hour: number, minute: number): string {
  const d = new Date();
  d.setDate(d.getDate() - daysAgo);
  d.setHours(hour, minute, 0, 0);
  return d.toISOString();
}

const summaries: HourApprovalRequestSummaryDTO[] = [
  {
    request_id: 1001,
    lecturer_id: 11,
    lecturer_code: "CNTT-011",
    lecturer_full_name: "Nguyễn Văn An",
    faculty_id: 10,
    faculty_name: "Khoa Công nghệ Thông tin",
    activity_count: 4,
    total_hours: 120,
    submitted_at: iso(2, 9, 15),
    status: "pending",
  },
  {
    request_id: 1002,
    lecturer_id: 12,
    lecturer_code: "CNTT-012",
    lecturer_full_name: "Trần Thị Bình",
    faculty_id: 10,
    faculty_name: "Khoa Công nghệ Thông tin",
    activity_count: 2,
    total_hours: 60,
    submitted_at: iso(3, 14, 40),
    status: "rejected",
  },
  {
    request_id: 1003,
    lecturer_id: 21,
    lecturer_code: "TOAN-021",
    lecturer_full_name: "Phạm Quốc Cường",
    faculty_id: 20,
    faculty_name: "Khoa Toán",
    activity_count: 3,
    total_hours: 90,
    submitted_at: iso(1, 10, 5),
    status: "approved",
  },
  {
    request_id: 1004,
    lecturer_id: 22,
    lecturer_code: "TOAN-022",
    lecturer_full_name: "Lê Thị Duyên",
    faculty_id: 20,
    faculty_name: "Khoa Toán",
    activity_count: 1,
    total_hours: 30,
    submitted_at: iso(5, 8, 20),
    status: "pending",
  },
  {
    request_id: 1005,
    lecturer_id: 31,
    lecturer_code: "KTE-031",
    lecturer_full_name: "Hoàng Thị Hạnh",
    faculty_id: 30,
    faculty_name: "Khoa Kinh tế",
    activity_count: 5,
    total_hours: 150,
    submitted_at: iso(4, 16, 10),
    status: "pending",
  },
  {
    request_id: 1006,
    lecturer_id: 32,
    lecturer_code: "KTE-032",
    lecturer_full_name: "Ngô Văn Khoa",
    faculty_id: 30,
    faculty_name: "Khoa Kinh tế",
    activity_count: 2,
    total_hours: 45,
    submitted_at: iso(6, 11, 55),
    status: "approved",
  },
];

const detailsById: Record<number, HourApprovalRequestDetailDTO> = {
  1001: {
    request_id: 1001,
    lecturer_id: 11,
    lecturer_code: "CNTT-011",
    lecturer_full_name: "Nguyễn Văn An",
    faculty_id: 10,
    faculty_name: "Khoa Công nghệ Thông tin",
    submitted_at: summaries[0]!.submitted_at,
    status: "pending",
    note_from_lecturer:
      "Em xin gửi xét duyệt giờ NCKH theo các công trình đã được phê duyệt nội dung.",
    activity_count: 4,
    total_hours: 120,
    items: [
      {
        activity_id: 501,
        activity_title: "Nghiên cứu tối ưu hóa truy vấn cơ sở dữ liệu",
        activity_kind_name: "Bài báo",
        member_role_name: "Chủ nhiệm",
        hours_converted: 45,
      },
      {
        activity_id: 502,
        activity_title: "Ứng dụng học sâu trong nhận dạng ảnh",
        activity_kind_name: "Đề tài",
        member_role_name: "Thành viên",
        hours_converted: 30,
      },
      {
        activity_id: 503,
        activity_title: "Hệ thống gợi ý nội dung cá nhân hóa",
        activity_kind_name: "Hội thảo",
        member_role_name: "Thành viên",
        hours_converted: 20,
      },
      {
        activity_id: 504,
        activity_title: "Giáo trình nhập môn Khoa học dữ liệu",
        activity_kind_name: "Sách",
        member_role_name: "Chủ biên",
        hours_converted: 25,
      },
    ],
  },
  1002: {
    request_id: 1002,
    lecturer_id: 12,
    lecturer_code: "CNTT-012",
    lecturer_full_name: "Trần Thị Bình",
    faculty_id: 10,
    faculty_name: "Khoa Công nghệ Thông tin",
    submitted_at: summaries[1]!.submitted_at,
    status: "rejected",
    note_from_lecturer: null,
    activity_count: 2,
    total_hours: 60,
    items: [
      {
        activity_id: 510,
        activity_title: "Khảo sát mô hình đánh giá chất lượng phần mềm",
        activity_kind_name: "Bài báo",
        member_role_name: "Thành viên",
        hours_converted: 30,
      },
      {
        activity_id: 511,
        activity_title: "Phương pháp kiểm thử tự động trong CI/CD",
        activity_kind_name: "Hội thảo",
        member_role_name: "Chủ nhiệm",
        hours_converted: 30,
      },
    ],
  },
  1003: {
    request_id: 1003,
    lecturer_id: 21,
    lecturer_code: "TOAN-021",
    lecturer_full_name: "Phạm Quốc Cường",
    faculty_id: 20,
    faculty_name: "Khoa Toán",
    submitted_at: summaries[2]!.submitted_at,
    status: "approved",
    note_from_lecturer: "Các giờ quy đổi được trích từ báo cáo phân bổ.",
    activity_count: 3,
    total_hours: 90,
    items: [
      {
        activity_id: 601,
        activity_title: "Ứng dụng giải tích số trong mô phỏng",
        activity_kind_name: "Đề tài",
        member_role_name: "Chủ nhiệm",
        hours_converted: 50,
      },
      {
        activity_id: 602,
        activity_title: "Tối ưu hóa lũy thừa và ứng dụng",
        activity_kind_name: "Bài báo",
        member_role_name: "Thành viên",
        hours_converted: 20,
      },
      {
        activity_id: 603,
        activity_title: "Hội thảo Toán ứng dụng 2025",
        activity_kind_name: "Hội thảo",
        member_role_name: "Thành viên",
        hours_converted: 20,
      },
    ],
  },
  1004: {
    request_id: 1004,
    lecturer_id: 22,
    lecturer_code: "TOAN-022",
    lecturer_full_name: "Lê Thị Duyên",
    faculty_id: 20,
    faculty_name: "Khoa Toán",
    submitted_at: summaries[3]!.submitted_at,
    status: "pending",
    note_from_lecturer:
      "Em gửi 1 công trình đã duyệt nội dung để xét duyệt giờ.",
    activity_count: 1,
    total_hours: 30,
    items: [
      {
        activity_id: 610,
        activity_title: "Phương pháp giải gần đúng cho PDE",
        activity_kind_name: "Bài báo",
        member_role_name: "Thành viên",
        hours_converted: 30,
      },
    ],
  },
  1005: {
    request_id: 1005,
    lecturer_id: 31,
    lecturer_code: "KTE-031",
    lecturer_full_name: "Hoàng Thị Hạnh",
    faculty_id: 30,
    faculty_name: "Khoa Kinh tế",
    submitted_at: summaries[4]!.submitted_at,
    status: "pending",
    note_from_lecturer:
      "Danh sách công trình đã duyệt nội dung, kính nhờ xét duyệt giờ.",
    activity_count: 5,
    total_hours: 150,
    items: [
      {
        activity_id: 701,
        activity_title: "Phân tích chuỗi cung ứng trong bối cảnh mới",
        activity_kind_name: "Đề tài",
        member_role_name: "Chủ nhiệm",
        hours_converted: 60,
      },
      {
        activity_id: 702,
        activity_title: "Tài chính hành vi và quyết định đầu tư",
        activity_kind_name: "Bài báo",
        member_role_name: "Thành viên",
        hours_converted: 25,
      },
      {
        activity_id: 703,
        activity_title: "Báo cáo chuyên đề kinh tế vĩ mô",
        activity_kind_name: "Sách",
        member_role_name: "Chủ biên",
        hours_converted: 30,
      },
      {
        activity_id: 704,
        activity_title: "Hội thảo Kinh tế 2025",
        activity_kind_name: "Hội thảo",
        member_role_name: "Thành viên",
        hours_converted: 15,
      },
      {
        activity_id: 705,
        activity_title: "Nghiên cứu tác động lạm phát đến tăng trưởng",
        activity_kind_name: "Bài báo",
        member_role_name: "Thành viên",
        hours_converted: 20,
      },
    ],
  },
  1006: {
    request_id: 1006,
    lecturer_id: 32,
    lecturer_code: "KTE-032",
    lecturer_full_name: "Ngô Văn Khoa",
    faculty_id: 30,
    faculty_name: "Khoa Kinh tế",
    submitted_at: summaries[5]!.submitted_at,
    status: "approved",
    note_from_lecturer: null,
    activity_count: 2,
    total_hours: 45,
    items: [
      {
        activity_id: 720,
        activity_title: "Quản trị rủi ro tài chính",
        activity_kind_name: "Bài báo",
        member_role_name: "Chủ nhiệm",
        hours_converted: 25,
      },
      {
        activity_id: 721,
        activity_title: "Hội thảo tài chính doanh nghiệp",
        activity_kind_name: "Hội thảo",
        member_role_name: "Thành viên",
        hours_converted: 20,
      },
    ],
  },
};

export const hourApprovalMockDb: HourApprovalMockDatabase = {
  summaries,
  detailsById,
};

/** dataset cho BCN khoa: lọc theo faculty_id */
export function buildFacultyDb(facultyId: number): HourApprovalMockDatabase {
  const filteredSummaries = summaries.filter((s) => s.faculty_id === facultyId);
  const filteredDetailsById: Record<number, HourApprovalRequestDetailDTO> = {};
  for (const s of filteredSummaries) {
    filteredDetailsById[s.request_id] = detailsById[s.request_id]!;
  }
  return { summaries: filteredSummaries, detailsById: filteredDetailsById };
}

/**
 * dataset cho QLKH trường:
 * giả định đây là “yêu cầu đã qua khoa” (mock). Hiển thị toàn bộ.
 * TODO(BE): khi có stage, API trường sẽ chỉ trả request đã được khoa forward.
 */
export function buildUniversityDb(): HourApprovalMockDatabase {
  return { summaries: [...summaries], detailsById: { ...detailsById } };
}