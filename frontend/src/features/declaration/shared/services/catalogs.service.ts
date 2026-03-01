import type {
  AcademicYearDto,
  ActivityKindDto,
  ActivityStatusDto,
  ActivityTypeDto,
  EvidenceFileTypeDto,
  LecturerOptionDto,
  MemberRoleDto,
} from "../contracts/declarationSharedContract";
import http from "@/lib/http";

const ROLE_LABELS_VI: Record<string, string> = {
  principal: "Chủ nhiệm",
  corresponding_author: "Tác giả chính",
  coauthor: "Đồng tác giả",
  member: "Thành viên",
  secretary: "Thư ký",
  chief_editor: "Chủ biên",
};

const ACTIVITY_TYPE_LABELS_VI: Record<string, string> = {
  hdgsnn_900: "HDGSNN 1-2 điểm (900 giờ)",
  hdgsnn_600: "HDGSNN >= 1 điểm (600 giờ)",
  hdgsnn_300: "Có ISSN/ISBN (300 giờ)",
  textbook: "Giáo trình",
  reference: "Tài liệu tham khảo",
  report: "Báo cáo hội thảo",
  attend: "Tham dự hội thảo",
  bo: "Đề tài cấp Bộ (2 năm)",
  ministry: "Đề tài cấp Bộ (2 năm)",
  coso: "Đề tài cấp Trường (1 năm)",
  university: "Đề tài cấp Trường (1 năm)",
};

const EXPECTED_ACADEMIC_YEAR_CODES = ["2024-2025", "2025-2026"];

export type JournalOptionDto = {
  id: number;
  name: string;
  issn: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;
  source_name: string | null;
  point_min: string | number | null; // Laravel decimal thường trả string
  point_max: string | number | null;
  classification: string; // hoặc union nếu bạn muốn strict
  research_hours: number;
  is_active: boolean;
};

export async function fetch_academic_years(): Promise<AcademicYearDto[]> {
  const { data } = await http.get<{ data: AcademicYearDto[] }>(
    "/api/lookups/academic-years",
  );

  if (import.meta.env.DEV) {
    const codes = new Set(data.data.map((y) => y.code));
    const missing = EXPECTED_ACADEMIC_YEAR_CODES.filter((c) => !codes.has(c));
    if (missing.length > 0) {
      console.warn(
        "[catalogs] Missing academic years in API:",
        missing.join(", "),
      );
    }
  }

  return data.data;
}

export async function fetch_activity_kinds(): Promise<ActivityKindDto[]> {
  const { data } = await http.get<{ data: ActivityKindDto[] }>(
    "/api/lookups/activity-kinds",
  );
  return data.data;
}

export async function fetch_activity_types_by_kind(
  kind_id: number,
): Promise<ActivityTypeDto[]> {
  const { data } = await http.get<{ data: ActivityTypeDto[] }>(
    "/api/lookups/activity-types",
    { params: { kind_id } },
  );

  return data.data.map((t) => ({
    ...t,
    name: ACTIVITY_TYPE_LABELS_VI[t.code] ?? t.name,
  }));
}

export async function fetch_member_roles(): Promise<MemberRoleDto[]> {
  const { data } = await http.get<{ data: MemberRoleDto[] }>(
    "/api/lookups/member-roles",
  );

  return data.data.map((r) => ({
    ...r,
    name: ROLE_LABELS_VI[r.code] ?? r.name,
  }));
}

export async function fetch_evidence_file_types(): Promise<
  EvidenceFileTypeDto[]
> {
  const { data } = await http.get<{ data: EvidenceFileTypeDto[] }>(
    "/api/lookups/evidence-file-types",
  );
  return data.data;
}

export async function fetch_activity_statuses(): Promise<ActivityStatusDto[]> {
  const { data } = await http.get<{ data: ActivityStatusDto[] }>(
    "/api/lookups/activity-statuses",
  );
  return data.data;
}

export async function search_lecturer_options(
  search: string,
): Promise<LecturerOptionDto[]> {
  const { data } = await http.get<{ data: LecturerOptionDto[] }>(
    "/api/lookups/lecturers",
    { params: { search } },
  );
  return data.data;
}

export async function search_journals(
  search: string,
): Promise<JournalOptionDto[]> {
  const { data } = await http.get<{ data: JournalOptionDto[] }>(
    "/api/lookups/journals",
    { params: { search, active: 1 } },
  );
  return data.data;
}
