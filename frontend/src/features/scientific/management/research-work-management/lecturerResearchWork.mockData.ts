import type {
  AcademicYearOptionDTO,
  ApprovedDetailDTO,
  ApprovedSummaryDTO,
  FacultyOptionDTO,
  OverviewDTO,
} from "./lecturerResearchWork.contracts";

export const mockFacultyOptions: FacultyOptionDTO[] = [
  { id: 1, name: "Khoa Kỹ thuật" },
  { id: 2, name: "Khoa Kinh tế" },
];

export const mockAcademicYearOptions: AcademicYearOptionDTO[] = [
  { id: 1, code: "2024-2025", is_active: true },
  { id: 2, code: "2023-2024", is_active: false },
];

export const mockLecturerDirectory: Array<{
  lecturer_id: number;
  lecturer_code: string;
  lecturer_full_name: string;
  department_id: number;
  department_name: string;
  faculty_id: number;
  faculty_name: string;
  degree_id: number | null;
  degree_name: string | null;
  academic_rank_id: number | null;
  academic_rank_name: string | null;
}> = [
  // faculty 1 (>=6)
  {
    lecturer_id: 101,
    lecturer_code: "GV-KT-001",
    lecturer_full_name: "Nguyễn Văn An",
    department_id: 11,
    department_name: "Bộ môn Cơ khí",
    faculty_id: 1,
    faculty_name: "Khoa Kỹ thuật",
    degree_id: 1,
    degree_name: "Tiến sĩ",
    academic_rank_id: 2,
    academic_rank_name: "PGS",
  },
  {
    lecturer_id: 102,
    lecturer_code: "GV-KT-002",
    lecturer_full_name: "Trần Thị Bình",
    department_id: 11,
    department_name: "Bộ môn Cơ khí",
    faculty_id: 1,
    faculty_name: "Khoa Kỹ thuật",
    degree_id: 2,
    degree_name: "Thạc sĩ",
    academic_rank_id: null,
    academic_rank_name: null,
  },
  {
    lecturer_id: 103,
    lecturer_code: "GV-KT-003",
    lecturer_full_name: "Lê Quốc Cường",
    department_id: 12,
    department_name: "Bộ môn CNTT",
    faculty_id: 1,
    faculty_name: "Khoa Kỹ thuật",
    degree_id: 1,
    degree_name: "Tiến sĩ",
    academic_rank_id: null,
    academic_rank_name: null,
  },
  {
    lecturer_id: 104,
    lecturer_code: "GV-KT-004",
    lecturer_full_name: "Phạm Thị Duyên",
    department_id: 12,
    department_name: "Bộ môn CNTT",
    faculty_id: 1,
    faculty_name: "Khoa Kỹ thuật",
    degree_id: 2,
    degree_name: "Thạc sĩ",
    academic_rank_id: null,
    academic_rank_name: null,
  },
  {
    lecturer_id: 105,
    lecturer_code: "GV-KT-005",
    lecturer_full_name: "Võ Minh Đức",
    department_id: 12,
    department_name: "Bộ môn CNTT",
    faculty_id: 1,
    faculty_name: "Khoa Kỹ thuật",
    degree_id: null,
    degree_name: null,
    academic_rank_id: null,
    academic_rank_name: null,
  },
  {
    lecturer_id: 106,
    lecturer_code: "GV-KT-006",
    lecturer_full_name: "Đặng Thị Em",
    department_id: 11,
    department_name: "Bộ môn Cơ khí",
    faculty_id: 1,
    faculty_name: "Khoa Kỹ thuật",
    degree_id: 2,
    degree_name: "Thạc sĩ",
    academic_rank_id: null,
    academic_rank_name: null,
  },

  // faculty 2 (đủ để overview >=12)
  {
    lecturer_id: 201,
    lecturer_code: "GV-KT-201",
    lecturer_full_name: "Nguyễn Hải Hà",
    department_id: 21,
    department_name: "Bộ môn Quản trị",
    faculty_id: 2,
    faculty_name: "Khoa Kinh tế",
    degree_id: 1,
    degree_name: "Tiến sĩ",
    academic_rank_id: 1,
    academic_rank_name: "GS",
  },
  {
    lecturer_id: 202,
    lecturer_code: "GV-KT-202",
    lecturer_full_name: "Trần Quốc Huy",
    department_id: 21,
    department_name: "Bộ môn Quản trị",
    faculty_id: 2,
    faculty_name: "Khoa Kinh tế",
    degree_id: 2,
    degree_name: "Thạc sĩ",
    academic_rank_id: null,
    academic_rank_name: null,
  },
  {
    lecturer_id: 203,
    lecturer_code: "GV-KT-203",
    lecturer_full_name: "Lê Thị Khánh",
    department_id: 22,
    department_name: "Bộ môn Tài chính",
    faculty_id: 2,
    faculty_name: "Khoa Kinh tế",
    degree_id: 1,
    degree_name: "Tiến sĩ",
    academic_rank_id: 2,
    academic_rank_name: "PGS",
  },
  {
    lecturer_id: 204,
    lecturer_code: "GV-KT-204",
    lecturer_full_name: "Phạm Văn Long",
    department_id: 22,
    department_name: "Bộ môn Tài chính",
    faculty_id: 2,
    faculty_name: "Khoa Kinh tế",
    degree_id: 2,
    degree_name: "Thạc sĩ",
    academic_rank_id: null,
    academic_rank_name: null,
  },
  {
    lecturer_id: 205,
    lecturer_code: "GV-KT-205",
    lecturer_full_name: "Vũ Thị Mai",
    department_id: 21,
    department_name: "Bộ môn Quản trị",
    faculty_id: 2,
    faculty_name: "Khoa Kinh tế",
    degree_id: null,
    degree_name: null,
    academic_rank_id: null,
    academic_rank_name: null,
  },
  {
    lecturer_id: 206,
    lecturer_code: "GV-KT-206",
    lecturer_full_name: "Đỗ Minh Nam",
    department_id: 22,
    department_name: "Bộ môn Tài chính",
    faculty_id: 2,
    faculty_name: "Khoa Kinh tế",
    degree_id: 1,
    degree_name: "Tiến sĩ",
    academic_rank_id: null,
    academic_rank_name: null,
  },
];

export const mockActivityIndex: Array<{
  activity_id: number;
  activity_code: string;
  owner_lecturer_id: number;
  academic_year_id: number;
  academic_year_code: string;
  kind_id: number;
  kind_name: string;
  type_id: number | null;
  type_name: string | null;
  title: string;
  abstract: string | null;
  status_code: "approved" | "submitted" | "rejected";
  approved_at: string | null;

  authors: ApprovedDetailDTO["authors"];
  evidence_items: ApprovedDetailDTO["evidence_items"];
  final_approval: ApprovedDetailDTO["final_approval"];
}> = [
  {
    activity_id: 9001,
    activity_code: "RA-2024-0001",
    owner_lecturer_id: 101,
    academic_year_id: 1,
    academic_year_code: "2024-2025",
    kind_id: 1,
    kind_name: "Bài báo",
    type_id: 10,
    type_name: "HDGSNN 900",
    title: "Tối ưu hóa cơ cấu robot song song trong môi trường bất định",
    abstract:
      "Nghiên cứu đề xuất phương pháp tối ưu hóa cơ cấu robot song song...",
    status_code: "approved",
    approved_at: "2025-03-12T09:30:00Z",
    authors: [
      {
        lecturer_id: 101,
        lecturer_full_name: "Nguyễn Văn An",
        member_role_id: 1,
        member_role_name: "Chủ nhiệm",
        contribution_share: "0.6000",
      },
      {
        lecturer_id: 102,
        lecturer_full_name: "Trần Thị Bình",
        member_role_id: 2,
        member_role_name: "Thành viên",
        contribution_share: "0.4000",
      },
    ],
    evidence_items: [
      {
        evidence_file_id: 70001,
        file_type_id: 1,
        file_type_name: "Nội dung",
        disk: "s3",
        path: "evidence/2025/03/ra-2024-0001-content.pdf",
        original_name: "Noi_dung_bai_bao.pdf",
        mime_type: "application/pdf",
        size_bytes: 1240021,
        uploaded_at: "2025-03-01T08:10:00Z",
      },
      {
        evidence_file_id: 70002,
        file_type_id: 2,
        file_type_name: "Bìa",
        disk: "url",
        path: "https://example.com/journal/ra-2024-0001",
        original_name: "Link trang tạp chí",
        mime_type: "text/uri-list",
        size_bytes: 0,
        uploaded_at: "2025-03-01T08:12:00Z",
      },
    ],
    final_approval: {
      stage_code: "manager",
      status: "approved",
      decided_by_user_id: 5001,
      decided_by_user_name: "QLKH Trường",
      decided_at: "2025-03-12T09:30:00Z",
      note: "Đủ minh chứng và đúng quy trình.",
    },
  },
  {
    activity_id: 9002,
    activity_code: "RA-2024-0002",
    owner_lecturer_id: 101,
    academic_year_id: 1,
    academic_year_code: "2024-2025",
    kind_id: 4,
    kind_name: "Hội thảo",
    type_id: 40,
    type_name: "Tham dự",
    title: "Trình bày tham luận tại Hội thảo Cơ điện tử 2025",
    abstract: null,
    status_code: "approved",
    approved_at: "2025-05-18T14:00:00Z",
    authors: [
      {
        lecturer_id: 101,
        lecturer_full_name: "Nguyễn Văn An",
        member_role_id: 1,
        member_role_name: "Chủ nhiệm",
        contribution_share: "1.0000",
      },
    ],
    evidence_items: [
      {
        evidence_file_id: 70003,
        file_type_id: 5,
        file_type_name: "Quyết định công bố",
        disk: "s3",
        path: "evidence/2025/05/ra-2024-0002-decision.pdf",
        original_name: "QD_cong_bo.pdf",
        mime_type: "application/pdf",
        size_bytes: 532000,
        uploaded_at: "2025-05-10T10:00:00Z",
      },
    ],
    final_approval: {
      stage_code: "manager",
      status: "approved",
      decided_by_user_id: 5001,
      decided_by_user_name: "QLKH Trường",
      decided_at: "2025-05-18T14:00:00Z",
      note: null,
    },
  },
  {
    activity_id: 9003,
    activity_code: "RA-2024-0003",
    owner_lecturer_id: 101,
    academic_year_id: 1,
    academic_year_code: "2024-2025",
    kind_id: 3,
    kind_name: "Đề tài",
    type_id: 30,
    type_name: "Cấp cơ sở",
    title: "Ứng dụng cảm biến trong giám sát thiết bị công nghiệp",
    abstract: null,
    status_code: "submitted",
    approved_at: null,
    authors: [],
    evidence_items: [],
    final_approval: null,
  },

  {
    activity_id: 9101,
    activity_code: "RA-2024-0101",
    owner_lecturer_id: 103,
    academic_year_id: 1,
    academic_year_code: "2024-2025",
    kind_id: 1,
    kind_name: "Bài báo",
    type_id: 11,
    type_name: "HDGSNN 600",
    title: "Phân tích hiệu năng thuật toán nén ảnh dựa trên wavelet",
    abstract: null,
    status_code: "rejected",
    approved_at: null,
    authors: [],
    evidence_items: [],
    final_approval: null,
  },
  {
    activity_id: 9201,
    activity_code: "RA-2023-0201",
    owner_lecturer_id: 104,
    academic_year_id: 2,
    academic_year_code: "2023-2024",
    kind_id: 2,
    kind_name: "Sách",
    type_id: 20,
    type_name: "Giáo trình",
    title: "Giáo trình Nhập môn Lập trình",
    abstract: "Giáo trình trình bày các khái niệm nền tảng...",
    status_code: "approved",
    approved_at: "2024-01-22T08:00:00Z",
    authors: [
      {
        lecturer_id: 104,
        lecturer_full_name: "Phạm Thị Duyên",
        member_role_id: 1,
        member_role_name: "Chủ nhiệm",
        contribution_share: "1.0000",
      },
    ],
    evidence_items: [
      {
        evidence_file_id: 70101,
        file_type_id: 2,
        file_type_name: "Bìa",
        disk: "s3",
        path: "evidence/2024/01/giao-trinh-bia.png",
        original_name: "bia_sach.png",
        mime_type: "image/png",
        size_bytes: 210334,
        uploaded_at: "2024-01-10T11:20:00Z",
      },
      {
        evidence_file_id: 70102,
        file_type_id: 1,
        file_type_name: "Nội dung",
        disk: "url",
        path: "https://example.com/books/intro-programming",
        original_name: "Link sách online",
        mime_type: "text/uri-list",
        size_bytes: 0,
        uploaded_at: "2024-01-10T11:21:00Z",
      },
    ],
    final_approval: {
      stage_code: "manager",
      status: "approved",
      decided_by_user_id: 5002,
      decided_by_user_name: "ADMIN",
      decided_at: "2024-01-22T08:00:00Z",
      note: "Hồ sơ đầy đủ.",
    },
  },
];

function countByLecturer(lecturerId: number, academicYearId: number | null) {
  const filtered = mockActivityIndex.filter((a) => {
    const matchLecturer = a.owner_lecturer_id === lecturerId;
    const matchYear = academicYearId
      ? a.academic_year_id === academicYearId
      : true;
    return matchLecturer && matchYear;
  });

  const approved = filtered.filter((a) => a.status_code === "approved").length;
  const pending = filtered.filter((a) => a.status_code === "submitted").length;
  const rejected = filtered.filter((a) => a.status_code === "rejected").length;

  return { total: filtered.length, approved, pending, rejected };
}

export function buildOverviewDtos(
  academicYearId: number | null
): OverviewDTO[] {
  return mockLecturerDirectory.map((l) => {
    const c = countByLecturer(l.lecturer_id, academicYearId);
    return {
      lecturer_id: l.lecturer_id,
      lecturer_code: l.lecturer_code,
      lecturer_full_name: l.lecturer_full_name,
      department_id: l.department_id,
      department_name: l.department_name,
      faculty_id: l.faculty_id,
      faculty_name: l.faculty_name,
      degree_id: l.degree_id,
      degree_name: l.degree_name,
      academic_rank_id: l.academic_rank_id,
      academic_rank_name: l.academic_rank_name,
      total_declared_research_work_count: c.total,
      approved_research_work_count: c.approved,
      pending_research_work_count: c.pending,
      rejected_research_work_count: c.rejected,
    };
  });
}

export function buildApprovedSummaries(
  lecturerId: number,
  academicYearId: number | null
): ApprovedSummaryDTO[] {
  return mockActivityIndex
    .filter((a) => {
      const matchLecturer = a.owner_lecturer_id === lecturerId;
      const matchYear = academicYearId
        ? a.academic_year_id === academicYearId
        : true;
      return matchLecturer && matchYear && a.status_code === "approved";
    })
    .map((a) => ({
      activity_id: a.activity_id,
      activity_code: a.activity_code,
      title: a.title,
      kind_id: a.kind_id,
      kind_name: a.kind_name,
      type_id: a.type_id,
      type_name: a.type_name,
      academic_year_id: a.academic_year_id,
      academic_year_code: a.academic_year_code,
      approved_at: a.approved_at ?? new Date().toISOString(),
    }));
}

export function buildApprovedDetail(activityId: number): ApprovedDetailDTO {
  const found = mockActivityIndex.find((a) => a.activity_id === activityId);
  if (!found)
    throw new Error(`Approved activity not found: activity_id=${activityId}`);
  if (found.status_code !== "approved")
    throw new Error(`Activity is not approved: activity_id=${activityId}`);

  return {
    activity_id: found.activity_id,
    activity_code: found.activity_code,
    title: found.title,
    abstract: found.abstract,
    kind_id: found.kind_id,
    kind_name: found.kind_name,
    type_id: found.type_id,
    type_name: found.type_name,
    academic_year_id: found.academic_year_id,
    academic_year_code: found.academic_year_code,
    approved_at: found.approved_at ?? new Date().toISOString(),
    authors: found.authors,
    evidence_items: found.evidence_items,
    final_approval: found.final_approval,
  };
}
