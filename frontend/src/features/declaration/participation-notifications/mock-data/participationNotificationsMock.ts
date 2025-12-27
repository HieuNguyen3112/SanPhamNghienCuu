import type {
  ParticipationNotificationDto,
  ParticipationMemberDto,
} from "../contracts/participationNotificationsContract";

const CURRENT_USER_ID = 1;

function makeMembers(pendingForYou: boolean): ParticipationMemberDto[] {
  return [
    {
      id: 11,
      full_name: "Nguyễn Văn A",
      unit: "Bộ môn CNTT • Khoa Công nghệ",
      role: "Tác giả chính",
      status: "ACCEPTED",
      is_current_user: false,
    },
    {
      id: CURRENT_USER_ID,
      full_name: "Bạn",
      unit: "Bộ môn CNTT • Khoa Công nghệ",
      role: "Đồng tác giả",
      status: pendingForYou ? "PENDING" : "ACCEPTED",
      is_current_user: true,
    },
    {
      id: 33,
      full_name: "Trần Thị B",
      unit: "Bộ môn HTTT • Khoa Công nghệ",
      role: "Đồng tác giả",
      status: "ACCEPTED",
      is_current_user: false,
    },
  ];
}

/**
 * In-memory store (mock).
 * Service sẽ mutate trực tiếp list này để giả lập backend.
 */
export const participationNotificationMockStore: ParticipationNotificationDto[] =
  [
    {
      id: 1001,
      work_title: "Ứng dụng AI trong phân tích dữ liệu giáo dục",
      work_type: "ARTICLE",
      your_role: "Đồng tác giả",
      owner_name: "Nguyễn Văn A",
      requested_at: "2025-10-01T02:12:00.000Z",
      status: "PENDING",
      work_short_info: "Tạp chí Khoa học Trường X • 2025",
      note_from_owner:
        "Bạn phụ trách phần xử lý dữ liệu và viết mục thí nghiệm.",
      work_system_status: "Đã duyệt nội dung",
      members: makeMembers(true),
      evidences: [
        {
          id: 1,
          type: "FILE",
          label: "PDF bài báo",
          url: "https://example.com/paper.pdf",
        },
        {
          id: 2,
          type: "LINK",
          label: "DOI",
          url: "https://doi.org/10.0000/example",
        },
      ],
    },
    {
      id: 1002,
      work_title: "Hệ thống giám sát chất lượng học tập bằng dữ liệu lớn",
      work_type: "PROJECT",
      your_role: "Thành viên",
      owner_name: "Lê Minh C",
      requested_at: "2025-09-18T08:40:00.000Z",
      status: "ACCEPTED",
      work_short_info: "Đề tài Cấp Trường • 2024–2025",
      work_system_status: "Chờ duyệt",
      confirmation_log: {
        status: "ACCEPTED",
        confirmed_at: "2025-09-18T09:12:00.000Z",
      },
      members: [
        {
          id: 77,
          full_name: "Lê Minh C",
          unit: "Phòng QLKH",
          role: "Chủ nhiệm",
          status: "ACCEPTED",
          is_current_user: false,
        },
        {
          id: CURRENT_USER_ID,
          full_name: "Bạn",
          unit: "Bộ môn CNTT • Khoa Công nghệ",
          role: "Thành viên",
          status: "ACCEPTED",
          is_current_user: true,
        },
      ],
      evidences: [
        {
          id: 3,
          type: "FILE",
          label: "Quyết định phê duyệt",
          url: "https://example.com/decision.pdf",
        },
        {
          id: 4,
          type: "FILE",
          label: "Thuyết minh đề tài",
          url: "https://example.com/proposal.pdf",
        },
      ],
    },
    {
      id: 1003,
      work_title: "Giáo trình Cấu trúc dữ liệu và Giải thuật",
      work_type: "BOOK",
      your_role: "Tham gia biên soạn",
      owner_name: "Phạm Thị D",
      requested_at: "2025-08-06T03:10:00.000Z",
      status: "REJECTED",
      work_short_info: "NXB Giáo dục Việt Nam • 2023",
      work_system_status: "Đã duyệt nội dung",
      confirmation_log: {
        status: "REJECTED",
        confirmed_at: "2025-08-06T04:00:00.000Z",
        reason: "Không tham gia biên soạn phiên bản này.",
      },
      members: [
        {
          id: 55,
          full_name: "Phạm Thị D",
          unit: "Khoa Công nghệ",
          role: "Chủ biên",
          status: "ACCEPTED",
          is_current_user: false,
        },
        {
          id: CURRENT_USER_ID,
          full_name: "Bạn",
          unit: "Bộ môn CNTT • Khoa Công nghệ",
          role: "Tham gia biên soạn",
          status: "REJECTED",
          is_current_user: true,
        },
      ],
      evidences: [
        {
          id: 5,
          type: "LINK",
          label: "Trang NXB",
          url: "https://example.com/book",
        },
      ],
    },
    {
      id: 1004,
      work_title: "Báo cáo: Ứng dụng LLM trong trợ giảng",
      work_type: "CONFERENCE",
      your_role: "Tham dự",
      owner_name: "Nguyễn Văn A",
      requested_at: "2025-07-22T10:25:00.000Z",
      status: "PENDING",
      work_short_info: "Hội thảo Khoa học Quốc gia 2025 • Hà Nội",
      work_system_status: "Đã duyệt nội dung",
      members: [
        {
          id: 11,
          full_name: "Nguyễn Văn A",
          unit: "Bộ môn CNTT • Khoa Công nghệ",
          role: "Báo cáo",
          status: "ACCEPTED",
          is_current_user: false,
        },
        {
          id: CURRENT_USER_ID,
          full_name: "Bạn",
          unit: "Bộ môn CNTT • Khoa Công nghệ",
          role: "Tham dự",
          status: "PENDING",
          is_current_user: true,
        },
      ],
      evidences: [
        {
          id: 6,
          type: "FILE",
          label: "Chương trình hội thảo",
          url: "https://example.com/agenda.pdf",
        },
        {
          id: 7,
          type: "FILE",
          label: "Giấy mời",
          url: "https://example.com/invite.pdf",
        },
      ],
    },
    {
      id: 1005,
      work_title: "A Study on Student Behavior Prediction",
      work_type: "ARTICLE",
      your_role: "Đồng tác giả",
      owner_name: "Trần Thị B",
      requested_at: "2025-06-10T01:15:00.000Z",
      status: "ACCEPTED",
      work_short_info: "Proceedings of ABC Conference • 2024",
      work_system_status: "Đã duyệt nội dung",
      confirmation_log: {
        status: "ACCEPTED",
        confirmed_at: "2025-06-10T02:00:00.000Z",
      },
      members: makeMembers(false),
      evidences: [
        {
          id: 8,
          type: "LINK",
          label: "Trang kỷ yếu",
          url: "https://example.com/proceedings",
        },
      ],
    },
  ];
