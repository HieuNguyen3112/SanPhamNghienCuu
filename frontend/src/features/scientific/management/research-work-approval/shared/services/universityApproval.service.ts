import http, { ensureCsrfCookie } from "@/lib/http";

export type UniversityApprovalFilters = {
  academic_year_id?: number | null;
  academic_year_code?: string | null;
  faculty_id?: number | null;
  department_id?: number | null;
  kind_code?: string | null;
  status?: string | null;
  q?: string | null;
};

export type UniversityApprovalListItem = {
  activity_id: number;
  activity_code: string;
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
  declared_hours: number;
  official_hours: number;
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

export type UniversityApprovalDetailResponse = {
  activity: UniversityApprovalListItem;
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

export type UniversityApprovalListResponse = {
  data: UniversityApprovalListItem[];
  meta: {
    filters: Record<string, unknown>;
    counters: {
      pending: number;
      approved: number;
      rejected: number;
      total: number;
    };
  };
};

function normalizeParams(filters: UniversityApprovalFilters) {
  const cleaned: Record<string, string | number> = {};
  Object.entries(filters).forEach(([key, value]) => {
    if (value === null || value === undefined) return;
    if (typeof value === "string" && value.trim() === "") return;
    cleaned[key] = typeof value === "string" ? value.trim() : value;
  });
  return cleaned;
}

export async function fetchUniversityApprovals(
  filters: UniversityApprovalFilters
): Promise<UniversityApprovalListResponse> {
  const response = await http.get("/api/admin/uni-approvals", {
    params: normalizeParams(filters),
  });
  return response.data as UniversityApprovalListResponse;
}

export async function fetchUniversityApprovalDetail(
  activityId: number
): Promise<UniversityApprovalDetailResponse> {
  const response = await http.get(`/api/admin/uni-approvals/${activityId}`);
  return response.data?.data as UniversityApprovalDetailResponse;
}

export async function finalizeUniversityApproval(
  activityId: number,
  payload: {
    members: { lecturer_id: number; official_hours: number }[];
    note?: string | null;
  }
): Promise<void> {
  await ensureCsrfCookie();
  await http.put(`/api/admin/uni-approvals/${activityId}/finalize`, payload);
}

export async function rejectUniversityApproval(
  activityId: number,
  payload: { reason_type: string; reason_detail?: string | null }
): Promise<void> {
  await ensureCsrfCookie();
  await http.put(`/api/admin/uni-approvals/${activityId}/reject`, payload);
}
