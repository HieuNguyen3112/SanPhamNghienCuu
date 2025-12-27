import type {
  ActorOptionDTO,
  AuditLogEntryDTO,
  FacultyOptionDTO,
} from "../contracts/audit-log.contract";

export const auditLogMockFaculties: FacultyOptionDTO[] = [
  { id: 1, name: "Khoa Công nghệ Thông tin" },
  { id: 2, name: "Khoa Toán" },
  { id: 3, name: "Khoa Kinh tế" },
];

export const auditLogMockActors: ActorOptionDTO[] = [
  { user_id: 1, name: "Admin System", email: "admin@uni.edu" },
  { user_id: 2, name: "Phòng QLKH", email: "qlkh@uni.edu" },
  { user_id: 3, name: "BCN Khoa CNTT", email: "bcn.cntt@uni.edu" },
  { user_id: 4, name: "Nguyễn Văn A", email: "nva@uni.edu" },
  { user_id: 5, name: "Trần Thị B", email: "ttb@uni.edu" },
];

function isoAt(daysAgo: number, hh: number, mm: number) {
  const d = new Date();
  d.setDate(d.getDate() - daysAgo);
  d.setHours(hh, mm, 0, 0);
  return d.toISOString();
}

/** Mock deterministic, cover đủ A→F */
export const auditLogMockEntries: AuditLogEntryDTO[] = [
  // A) AUTH
  {
    id: 1001,
    occurred_at: isoAt(0, 9, 12),
    severity: "normal",
    action_group: "auth",
    action_code: "LOGIN_SUCCESS",
    action_label: "Đăng nhập hệ thống",
    actor: {
      user_id: 3,
      name: "BCN Khoa CNTT",
      email: "bcn.cntt@uni.edu",
      backend_roles_snapshot: ["DL"],
    },
    target: { type: "system", id: null, display: "Hệ thống SPNC" },
    result: { status: "success", error_message: null },
    faculty_id: 1,
    faculty_name: "Khoa Công nghệ Thông tin",
    ip: "113.161.12.34",
    user_agent:
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/121.0",
    request: { method: "POST", path: "/login", http_status: 200 },
    changes: null,
    note: null,
  },
  {
    id: 1002,
    occurred_at: isoAt(0, 9, 15),
    severity: "important",
    action_group: "auth",
    action_code: "LOGIN_FAILED",
    action_label: "Đăng nhập thất bại",
    actor: {
      user_id: null,
      name: null,
      email: "unknown@uni.edu",
      backend_roles_snapshot: null,
    },
    target: { type: "system", id: null, display: "Hệ thống SPNC" },
    result: { status: "failure", error_message: "Sai mật khẩu" },
    faculty_id: null,
    faculty_name: null,
    ip: "42.112.90.1",
    user_agent: "Mozilla/5.0 (Macintosh) AppleWebKit/605.1",
    request: { method: "POST", path: "/login", http_status: 401 },
    changes: null,
    note: null,
  },

  // B) LECTURER
  {
    id: 2001,
    occurred_at: isoAt(2, 10, 5),
    severity: "normal",
    action_group: "lecturer",
    action_code: "LECTURER_PROFILE_UPDATED",
    action_label: "Cập nhật thông tin giảng viên",
    actor: {
      user_id: 3,
      name: "BCN Khoa CNTT",
      email: "bcn.cntt@uni.edu",
      backend_roles_snapshot: ["DL"],
    },
    target: {
      type: "lecturer",
      id: "l-4",
      display: "Giảng viên: Nguyễn Văn A",
    },
    result: { status: "success", error_message: null },
    faculty_id: 1,
    faculty_name: "Khoa Công nghệ Thông tin",
    ip: "113.161.12.34",
    user_agent: "Mozilla/5.0 Chrome/121.0",
    request: { method: "PUT", path: "/api/lecturers/4", http_status: 200 },
    changes: [
      { field: "phone", before: "0909xxxx", after: "0912xxxx" },
      { field: "academic_rank_id", before: "null", after: "2" },
    ],
    note: null,
  },

  // C) RESEARCH
  {
    id: 3001,
    occurred_at: isoAt(3, 9, 30),
    severity: "normal",
    action_group: "research",
    action_code: "RESEARCH_CREATED",
    action_label: "Kê khai công trình",
    actor: {
      user_id: 4,
      name: "Nguyễn Văn A",
      email: "nva@uni.edu",
      backend_roles_snapshot: ["GV"],
    },
    target: {
      type: "research_activity",
      id: "ra-9101",
      display: "Bài báo: Ứng dụng AI trong giáo dục",
      route_name: "works.personal",
      route_params: { id: 9101 },
    },
    result: { status: "success", error_message: null },
    faculty_id: 1,
    faculty_name: "Khoa Công nghệ Thông tin",
    ip: "113.161.55.9",
    user_agent: "Mozilla/5.0 Chrome/121.0",
    request: {
      method: "POST",
      path: "/api/research-activities",
      http_status: 201,
    },
    changes: null,
    note: null,
  },

  // D) APPROVAL
  {
    id: 4001,
    occurred_at: isoAt(2, 15, 10),
    severity: "important",
    action_group: "approval",
    action_code: "RESEARCH_APPROVED_STAGE1",
    action_label: "BCN duyệt công trình",
    actor: {
      user_id: 3,
      name: "BCN Khoa CNTT",
      email: "bcn.cntt@uni.edu",
      backend_roles_snapshot: ["DL"],
    },
    target: {
      type: "research_activity",
      id: "ra-9101",
      display: "Bài báo: Ứng dụng AI trong giáo dục",
      route_name: "works.facapprovals",
      route_params: { id: 9101 },
    },
    result: { status: "success", error_message: null },
    faculty_id: 1,
    faculty_name: "Khoa Công nghệ Thông tin",
    ip: "113.161.12.34",
    user_agent: "Mozilla/5.0 Chrome/121.0",
    request: {
      method: "POST",
      path: "/api/approvals/assistant/9101",
      http_status: 200,
    },
    changes: [
      { field: "status", before: "submitted", after: "approved_stage1" },
    ],
    note: "Đủ minh chứng",
  },

  // E) CONFIG (global only)
  {
    id: 5001,
    occurred_at: isoAt(5, 11, 25),
    severity: "dangerous",
    action_group: "config",
    action_code: "ACADEMIC_YEAR_UPDATED",
    action_label: "Thay đổi cấu hình năm học",
    actor: {
      user_id: 1,
      name: "Admin System",
      email: "admin@uni.edu",
      backend_roles_snapshot: ["ADMIN"],
    },
    target: {
      type: "academic_year",
      id: "ay-2025-2026",
      display: "Cấu hình: Năm học 2025–2026",
    },
    result: { status: "success", error_message: null },
    faculty_id: null,
    faculty_name: null,
    ip: "113.161.88.2",
    user_agent: "Mozilla/5.0 Chrome/120.0",
    request: { method: "PUT", path: "/api/academic-years/3", http_status: 200 },
    changes: [{ field: "is_active", before: "false", after: "true" }],
    note: null,
  },

  // F) SECURITY
  {
    id: 6001,
    occurred_at: isoAt(1, 8, 50),
    severity: "dangerous",
    action_group: "security",
    action_code: "EXPORT_DATA",
    action_label: "Xuất dữ liệu",
    actor: {
      user_id: 2,
      name: "Phòng QLKH",
      email: "qlkh@uni.edu",
      backend_roles_snapshot: ["QL"],
    },
    target: {
      type: "system",
      id: null,
      display: "Export: Danh sách công trình",
    },
    result: { status: "success", error_message: null },
    faculty_id: null,
    faculty_name: null,
    ip: "113.161.77.10",
    user_agent: "Mozilla/5.0 Chrome/120.0",
    request: {
      method: "GET",
      path: "/api/exports/research-activities",
      http_status: 200,
    },
    changes: null,
    note: "Xuất toàn trường",
  },
  {
    id: 6002,
    occurred_at: isoAt(1, 9, 2),
    severity: "dangerous",
    action_group: "security",
    action_code: "FORBIDDEN_ACCESS",
    action_label: "Truy cập trái quyền",
    actor: {
      user_id: 3,
      name: "BCN Khoa CNTT",
      email: "bcn.cntt@uni.edu",
      backend_roles_snapshot: ["DL"],
    },
    target: { type: "system", id: null, display: "API: /api/hour-rules" },
    result: { status: "failure", error_message: "403 Forbidden" },
    faculty_id: 1,
    faculty_name: "Khoa Công nghệ Thông tin",
    ip: "113.161.12.34",
    user_agent: "Mozilla/5.0 Chrome/121.0",
    request: { method: "GET", path: "/api/hour-rules", http_status: 403 },
    changes: null,
    note: null,
  },
];
