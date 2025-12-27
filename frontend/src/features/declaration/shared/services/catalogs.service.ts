import type {
  AcademicYearDto,
  ActivityKindDto,
  ActivityStatusDto,
  ActivityTypeDto,
  EvidenceFileTypeDto,
  LecturerOptionDto,
  MemberRoleDto,
} from "../contracts/declarationSharedContract";

/**
 * TODO (backend):
 * - GET /api/academic-years
 * - GET /api/activity-kinds
 * - GET /api/activity-types?kind_id=
 * - GET /api/member-roles
 * - GET /api/evidence-file-types
 * - GET /api/activity-statuses
 * - GET /api/lecturers/options?search=
 */

const MOCK = true;

const mock_academic_years: AcademicYearDto[] = [
  {
    id: 1,
    code: "2024-2025",
    start_date: "2024-09-01",
    end_date: "2025-08-31",
    is_active: true,
  },
  {
    id: 2,
    code: "2025-2026",
    start_date: "2025-09-01",
    end_date: "2026-08-31",
    is_active: false,
  },
];

const mock_kinds: ActivityKindDto[] = [
  { id: 10, code: "project", name: "Đề tài KH&CN" },
  { id: 11, code: "paper", name: "Bài báo khoa học" },
  { id: 12, code: "book", name: "Giáo trình / Tài liệu" },
  { id: 13, code: "conference", name: "Hội nghị / Hội thảo" },
];

const mock_statuses: ActivityStatusDto[] = [
  { id: 100, code: "draft", name: "Bản nháp" },
  { id: 101, code: "submitted", name: "Đã gửi duyệt" },
  { id: 102, code: "approved", name: "Được duyệt" },
  { id: 103, code: "rejected", name: "Bị từ chối" },
];

const mock_roles: MemberRoleDto[] = [
  { id: 200, code: "principal", name: "Chủ nhiệm" },
  { id: 201, code: "secretary", name: "Thư ký" }, // P1: cần seed backend nếu chưa có
  { id: 202, code: "member", name: "Thành viên" },
  { id: 203, code: "chief_editor", name: "Chủ biên" }, // P1
  { id: 204, code: "coauthor", name: "Đồng tác giả" }, // P1
  { id: 205, code: "corresponding_author", name: "Tác giả chính" }, // P1
];

const mock_evidence_types: EvidenceFileTypeDto[] = [
  { id: 300, code: "content", name: "Nội dung" },
  { id: 301, code: "cover", name: "Bìa" },
  { id: 302, code: "toc", name: "Mục lục" },
  { id: 303, code: "acceptance_decision", name: "Quyết định/Chấp nhận" },
  { id: 304, code: "publication_decision", name: "Quyết định xuất bản" },
  // P1: có thể cần seed thêm codes theo nghiệp vụ: assignment_decision, nghiệm_thu, ...
];

const mock_types: ActivityTypeDto[] = [
  // paper
  {
    id: 400,
    kind_id: 11,
    code: "hdgsnn_900",
    name: "HDGSNN 1–2 điểm (900 giờ)",
  },
  {
    id: 401,
    kind_id: 11,
    code: "hdgsnn_600",
    name: "HDGSNN ≤ 1 điểm (600 giờ)",
  },
  { id: 402, kind_id: 11, code: "hdgsnn_300", name: "Có ISSN/ISBN (300 giờ)" },

  // book
  { id: 410, kind_id: 12, code: "textbook", name: "Giáo trình (900 giờ)" },
  {
    id: 411,
    kind_id: 12,
    code: "reference",
    name: "Tài liệu tham khảo (600 giờ)",
  },

  // conference
  { id: 420, kind_id: 13, code: "report", name: "Báo cáo (40 giờ/lần)" },
  {
    id: 421,
    kind_id: 13,
    code: "attend",
    name: "Tham dự (4 giờ/lần, tối đa 40)",
  },

  // project (P0: cần seed backend)
  { id: 430, kind_id: 10, code: "ministry", name: "Cấp Bộ" },
  { id: 431, kind_id: 10, code: "province", name: "Cấp Tỉnh" },
  { id: 432, kind_id: 10, code: "university", name: "Cấp Trường" },
  { id: 433, kind_id: 10, code: "faculty", name: "Cấp Khoa" },
  { id: 434, kind_id: 10, code: "other", name: "Khác" },
];

const mock_lecturer_options: LecturerOptionDto[] = [
  {
    id: 1,
    code: "GV001",
    full_name: "Nguyễn Văn A",
    department_id: 10,
    department_name: "Bộ môn CNTT",
  },
  {
    id: 2,
    code: "GV002",
    full_name: "Trần Thị B",
    department_id: 10,
    department_name: "Bộ môn CNTT",
  },
  {
    id: 3,
    code: "GV003",
    full_name: "Lê Văn C",
    department_id: 11,
    department_name: "Bộ môn Toán",
  },
];

export async function fetch_academic_years(): Promise<AcademicYearDto[]> {
  if (MOCK) return mock_academic_years;
  throw new Error("TODO: implement API fetch_academic_years()");
}

export async function fetch_activity_kinds(): Promise<ActivityKindDto[]> {
  if (MOCK) return mock_kinds;
  throw new Error("TODO: implement API fetch_activity_kinds()");
}

export async function fetch_activity_types_by_kind(
  kind_id: number
): Promise<ActivityTypeDto[]> {
  if (MOCK) return mock_types.filter((t) => t.kind_id === kind_id);
  throw new Error("TODO: implement API fetch_activity_types_by_kind()");
}

export async function fetch_member_roles(): Promise<MemberRoleDto[]> {
  if (MOCK) return mock_roles;
  throw new Error("TODO: implement API fetch_member_roles()");
}

export async function fetch_evidence_file_types(): Promise<
  EvidenceFileTypeDto[]
> {
  if (MOCK) return mock_evidence_types;
  throw new Error("TODO: implement API fetch_evidence_file_types()");
}

export async function fetch_activity_statuses(): Promise<ActivityStatusDto[]> {
  if (MOCK) return mock_statuses;
  throw new Error("TODO: implement API fetch_activity_statuses()");
}

export async function search_lecturer_options(
  search: string
): Promise<LecturerOptionDto[]> {
  if (MOCK) {
    const q = search.trim().toLowerCase();
    if (!q) return mock_lecturer_options;
    return mock_lecturer_options.filter(
      (l) =>
        l.full_name.toLowerCase().includes(q) ||
        l.code.toLowerCase().includes(q)
    );
  }
  throw new Error("TODO: implement API search_lecturer_options()");
}
