import http, { ensureCsrfCookie } from "@/lib/http";

export type FacultyApprovalFilters = {
  academic_year_id?: number | null;
  kind_code?: string | null;
  status?: string | null;
  q?: string | null;
  page?: number | null;
  per_page?: number | null;
};

export type FacultyApprovalLookupsResponse = {
  data: {
    academic_years: { id: number; code: string; is_active: boolean }[];
    work_kinds: { id: number; code: string; name: string }[];
    statuses: { code: string; name: string }[];
    faculty: { id: number; name: string };
  };
};

export type FacultyApprovalListItem = {
  activity_id: number;
  activity_code: string | null;
  title: string;
  kind_code: string;
  kind_name: string | null;
  type_code: string | null;
  type_name: string | null;
  academic_year_id: number | null;
  academic_year_code: string | null;
  status_code: string;
  approval_status: string;
  submitted_at: string | null;
  approved_at: string | null;
  declared_hours: number | null;
  official_hours: number | null;
  evidence_count: number;
  lecturer: {
    id: number;
    code: string;
    full_name: string;
    department_id: number | null;
    department_name: string | null;
    faculty_id: number | null;
    faculty_name: string | null;
  };
  authors: {
    lecturer_id: number;
    lecturer_code: string;
    lecturer_full_name: string;
    member_role_code: string | null;
    member_role_name: string | null;
    department_name: string | null;
    faculty_name: string | null;
  }[];
};

export type FacultyApprovalDetailResponse = {
  activity: FacultyApprovalListItem & {
    computed_total_hours?: number | null;
    member_count?: number | null;
    hours_value_label?: string | null;
    hours_request_state?: string | null;
    hours_request_status_raw?: string | null;
  };
  members: {
    lecturer_id: number;
    lecturer_code: string;
    lecturer_full_name: string;
    member_role_id: number | null;
    member_role_code: string | null;
    member_role_name: string | null;
    contribution_share: number | null;
    hours_assigned: number | null;
    declared_hours: number | null;
    computed_member_hours?: number | null;
    recommended_hours: number | null;
    official_hours: number | null;
    department_name: string | null;
    faculty_name: string | null;
  }[];
  evidence_files: {
    id: number;
    file_type_id: number;
    file_type_name: string | null;
    original_name: string | null;
    mime_type: string | null;
    size_bytes: number | null;
    path: string | null;
    disk: string | null;
    uploaded_at: string | null;
    url: string | null;
  }[];
  approvals: {
    id: number;
    stage_id: number;
    stage_code: string;
    stage_name: string;
    status: string;
    decided_by_user_id: number | null;
    decided_by_user_name: string | null;
    decided_at: string | null;
    note: string | null;
  }[];
};

export type FacultyApprovalListResponse = {
  data: FacultyApprovalListItem[];
  meta: {
    filters: Record<string, unknown>;
    counters: {
      pending: number;
      approved: number;
      rejected: number;
      total: number;
    };
    pagination?: {
      page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  };
};

function normalizeParams(filters: FacultyApprovalFilters) {
  const cleaned: Record<string, string | number> = {};
  Object.entries(filters).forEach(([key, value]) => {
    if (value === null || value === undefined) return;
    if (typeof value === "string" && value.trim() === "") return;
    cleaned[key] = typeof value === "string" ? value.trim() : value;
  });
  return cleaned;
}

export async function fetchFacultyApprovalLookups(): Promise<FacultyApprovalLookupsResponse> {
  const response = await http.get("/api/faculty/works/approvals/lookups");
  return response.data as FacultyApprovalLookupsResponse;
}

export async function fetchFacultyApprovals(
  filters: FacultyApprovalFilters
): Promise<FacultyApprovalListResponse> {
  const response = await http.get("/api/faculty/works/approvals", {
    params: normalizeParams(filters),
  });
  return response.data as FacultyApprovalListResponse;
}

export async function fetchFacultyApprovalDetail(
  activityId: number
): Promise<FacultyApprovalDetailResponse> {
  const response = await http.get(`/api/faculty/works/approvals/${activityId}`);
  return response.data?.data as FacultyApprovalDetailResponse;
}

export async function approveFacultyApproval(activityId: number): Promise<void> {
  await ensureCsrfCookie();
  await http.put(`/api/faculty/works/approvals/${activityId}/approve`);
}

export async function rejectFacultyApproval(
  activityId: number,
  payload: { reason_type: string; reason_detail?: string | null }
): Promise<void> {
  await ensureCsrfCookie();
  await http.put(`/api/faculty/works/approvals/${activityId}/reject`, payload);
}
