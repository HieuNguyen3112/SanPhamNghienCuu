import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  EvidenceFileDto,
  EvidenceLinkDto,
  LecturerOptionDto,
  ResearchActivityMemberUpsertDto,
} from "../contracts/declarationSharedContract";

// Backend endpoints:
// - POST /api/research-activities
// - PUT /api/research-activities/{id}
// - PUT /api/research-activities/{id}/{detail-kind}
// - PUT /api/research-activities/{id}/members
// - POST /api/research-activities/{id}/submit
// - GET  /api/research-activities/{id}/evidence-files
// - POST /api/research-activities/{id}/evidence-files
// - DELETE /api/research-activities/{id}/evidence-files/{evidence}
// - GET  /api/research-activities/{id}/evidence-links
// - POST /api/research-activities/{id}/evidence-links
// - DELETE /api/research-activities/{id}/evidence-links/{link}

const MOCK = false;

type ResearchActivityDto = {
  id: number;
  activity_code: string;
  owner_lecturer_id: number | null;
  kind_id: number;
  type_id: number | null;
  academic_year_id: number | null;
  status_id: number | null;
  title: string;
  abstract: string | null;
  start_date: string | null;
  end_date: string | null;
  quantity: number;
  submitted_at: string | null;
  approved_at: string | null;
  total_hours_calc: number | null;
  notes: string | null;
  created_at?: string;
  updated_at?: string;
};

type CurrentLecturerPayload = {
  id?: number;
  code?: string | null;
  full_name?: string | null;
  department_id?: number | null;
  department_name?: string | null;
};

type PaperDetailsDto = {
  activity_id: number;
  journal_name: string | null;
  issn: string | null;
  doi: string | null;
  article_url: string | null;
  volume: string | null;
  issue: string | null;
  page_start: number | null;
  page_end: number | null;
  year: number | null;
  keywords?: string | null;
};

type BookDetailsDto = {
  activity_id: number;
  publisher: string;
  approval_decision_no: string | null;
  approval_decision_date: string | null;
  isbn: string | null;
  pages: number | null;
  year: number | null;
};

type ProjectDetailsDto = {
  activity_id: number;
  project_code: string | null;
  decision_no: string | null;
  decision_date: string | null;
  funding: number | null;
  start_month: string | null;
  end_month: string | null;
};

type ConferenceDetailsDto = {
  activity_id: number;
  conference_name: string;
  location: string | null;
  held_on: string | null;
};

type UpsertActivityPayload = {
  id?: number;
  owner_lecturer_id?: number | null;
  kind_id: number;
  type_id?: number | null;
  academic_year_id?: number | null;
  status_id?: number | null;
  title: string;
  abstract?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  quantity?: number | null;
  notes?: string | null;
  submitted_at?: string | null;
  approved_at?: string | null;
  total_hours_calc?: number | null;
};

export type DownloadEvidenceFileResult = {
  blob: Blob;
  filename: string;
};

let next_id = 1000;
const activities = new Map<number, ResearchActivityDto>();
const paper_details = new Map<number, PaperDetailsDto>();
const book_details = new Map<number, BookDetailsDto>();
const project_details = new Map<number, ProjectDetailsDto>();
const conference_details = new Map<number, ConferenceDetailsDto>();
const members = new Map<number, ResearchActivityMemberUpsertDto[]>();
const evidence = new Map<number, EvidenceFileDto[]>();
const evidence_links = new Map<number, EvidenceLinkDto[]>();
let next_evidence_link_id = 1;
let current_lecturer_cache: CurrentLecturerPayload | null = null;
let current_lecturer_promise: Promise<CurrentLecturerPayload | null> | null =
  null;
let current_lecturer_cached_at = 0;
const CURRENT_LECTURER_CACHE_TTL_MS = 30_000;

function now_iso() {
  return new Date().toISOString();
}

function decodeContentDispositionFilename(headerValue: string): string | null {
  const value = headerValue.trim();
  if (!value) return null;

  const utf8Match = value.match(/filename\*=UTF-8''([^;]+)/i);
  if (utf8Match?.[1]) {
    const encodedName = utf8Match[1].trim().replace(/^"(.*)"$/, "$1");
    try {
      const decoded = decodeURIComponent(encodedName);
      if (decoded.trim()) return decoded;
    } catch {
      // Fallback to plain filename parsing below.
    }
  }

  const plainMatch = value.match(/filename="?([^";]+)"?/i);
  if (plainMatch?.[1] && plainMatch[1].trim()) {
    return plainMatch[1].trim();
  }

  return null;
}

export async function upsert_activity_base(
  dto: UpsertActivityPayload,
): Promise<ResearchActivityDto> {
  if (!MOCK) {
    await ensureCsrfCookie();
    const payload = {
      kind_id: dto.kind_id,
      type_id: dto.type_id ?? null,
      academic_year_id: dto.academic_year_id ?? null,
      title: dto.title,
      abstract: dto.abstract ?? null,
      start_date: dto.start_date ?? null,
      end_date: dto.end_date ?? null,
      quantity: dto.quantity ?? 1,
      notes: dto.notes ?? null,
    };

    if (!dto.id) {
      const { data } = await http.post<{ data: ResearchActivityDto }>(
        "/api/research-activities",
        payload,
      );
      return data.data;
    }

    const { data } = await http.put<{ data: ResearchActivityDto }>(
      `/api/research-activities/${dto.id}`,
      payload,
    );
    return data.data;
  }

  if (!dto.id) {
    const id = next_id++;

    const created: ResearchActivityDto = {
      id,
      activity_code: `ACT-${id}`,
      kind_id: dto.kind_id,
      type_id: dto.type_id ?? null,
      academic_year_id: dto.academic_year_id ?? null,
      title: dto.title,
      abstract: dto.abstract ?? null,
      start_date: dto.start_date ?? null,
      end_date: dto.end_date ?? null,
      quantity: dto.quantity ?? 1,
      notes: dto.notes ?? null,
      owner_lecturer_id: dto.owner_lecturer_id ?? null,
      status_id: dto.status_id ?? null,
      submitted_at: dto.submitted_at ?? null,
      approved_at: dto.approved_at ?? null,
      total_hours_calc: dto.total_hours_calc ?? null,
      created_at: now_iso(),
      updated_at: now_iso(),
    };

    activities.set(id, created);
    return created;
  }

  const prev = activities.get(dto.id);
  if (!prev) throw new Error("Activity not found");

  const updated: ResearchActivityDto = {
    ...prev,
    ...dto,
    type_id: dto.type_id ?? prev.type_id ?? null,
    academic_year_id: dto.academic_year_id ?? prev.academic_year_id ?? null,
    quantity: dto.quantity ?? prev.quantity ?? 1,
    status_id: dto.status_id ?? prev.status_id ?? null,
    updated_at: now_iso(),
  };

  activities.set(dto.id, updated);
  return updated;
}

export async function upsert_paper_details(dto: PaperDetailsDto) {
  if (!MOCK) {
    await ensureCsrfCookie();
    const { data } = await http.put<{ data: PaperDetailsDto }>(
      `/api/research-activities/${dto.activity_id}/paper_details`,
      dto,
    );
    return data.data;
  }
  paper_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_book_details(dto: BookDetailsDto) {
  if (!MOCK) {
    await ensureCsrfCookie();
    const { data } = await http.put<{ data: BookDetailsDto }>(
      `/api/research-activities/${dto.activity_id}/book_details`,
      dto,
    );
    return data.data;
  }
  book_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_project_details(dto: ProjectDetailsDto) {
  if (!MOCK) {
    await ensureCsrfCookie();
    const { data } = await http.put<{ data: ProjectDetailsDto }>(
      `/api/research-activities/${dto.activity_id}/project_details`,
      dto,
    );
    return data.data;
  }
  project_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_conference_details(dto: ConferenceDetailsDto) {
  if (!MOCK) {
    await ensureCsrfCookie();
    const { data } = await http.put<{ data: ConferenceDetailsDto }>(
      `/api/research-activities/${dto.activity_id}/conference_details`,
      dto,
    );
    return data.data;
  }
  conference_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_members(
  activity_id: number,
  list: ResearchActivityMemberUpsertDto[],
) {
  if (!MOCK) {
    await ensureCsrfCookie();
    const { data } = await http.put<{
      data: ResearchActivityMemberUpsertDto[];
    }>(`/api/research-activities/${activity_id}/members`, { items: list });
    return data.data;
  }
  members.set(activity_id, list);
  return list;
}

export async function submit_activity(
  activity_id: number,
  _submitted_status_id?: number,
): Promise<{
  message: string;
  data: ResearchActivityDto & { status_code?: string };
  workflow?: {
    status_code?: string;
    pending_members?: Array<{
      invitation_id: number;
      lecturer_id: number;
      lecturer_code: string | null;
      lecturer_full_name: string | null;
      member_role_code: string | null;
      member_role_name: string | null;
    }>;
    can_faculty_review?: boolean;
  };
}> {
  if (!MOCK) {
    await ensureCsrfCookie();
    const { data } = await http.post<{
      message: string;
      data: ResearchActivityDto & { status_code?: string };
      workflow?: {
        status_code?: string;
        pending_members?: Array<{
          invitation_id: number;
          lecturer_id: number;
          lecturer_code: string | null;
          lecturer_full_name: string | null;
          member_role_code: string | null;
          member_role_name: string | null;
        }>;
        can_faculty_review?: boolean;
      };
    }>(`/api/research-activities/${activity_id}/submit`);
    return data;
  }
  const prev = activities.get(activity_id);
  if (!prev) throw new Error("Activity not found");
  const next = {
    ...prev,
    submitted_at: now_iso(),
  };
  activities.set(activity_id, next);
  return {
    message: "submitted",
    data: {
      ...next,
      status_code: "pending_faculty_review",
    },
    workflow: {
      status_code: "pending_faculty_review",
      pending_members: [],
      can_faculty_review: true,
    },
  };
}

export async function list_evidence_files(
  activity_id: number,
): Promise<EvidenceFileDto[]> {
  if (!MOCK) {
    const { data } = await http.get<{ data: EvidenceFileDto[] }>(
      `/api/research-activities/${activity_id}/evidence-files`,
    );
    return data.data;
  }
  return evidence.get(activity_id) ?? [];
}

export async function upload_evidence_file(
  activity_id: number,
  payload: {
    file: File;
    file_type_id: number;
  },
): Promise<EvidenceFileDto | null> {
  if (!MOCK) {
    await ensureCsrfCookie();
    const formData = new FormData();
    formData.append("file", payload.file);
    formData.append("file_type_id", String(payload.file_type_id));
    const { data } = await http.post<{ data: EvidenceFileDto | null }>(
      `/api/research-activities/${activity_id}/evidence-files`,
      formData,
    );
    return data.data ?? null;
  }

  const list = evidence.get(activity_id) ?? [];
  const created: EvidenceFileDto = {
    id: Date.now(),
    activity_id,
    file_type_id: payload.file_type_id,
    file_type_name: `Loại #${payload.file_type_id}`,
    disk: "local",
    path: `mock/evidence/${activity_id}/${payload.file.name}`,
    original_name: payload.file.name,
    mime_type: payload.file.type || "application/pdf",
    size_bytes: payload.file.size,
    sha256: `mock-${Math.random().toString(36).slice(2)}`,
    uploaded_by_user_id: 0,
    uploaded_at: now_iso(),
    created_at: now_iso(),
    updated_at: now_iso(),
    url: "#",
    download_url: "#",
  };
  evidence.set(activity_id, [created, ...list]);
  return created;
}

export async function delete_evidence_file(
  activity_id: number,
  evidence_id: number,
): Promise<void> {
  if (!MOCK) {
    await ensureCsrfCookie();
    await http.delete(
      `/api/research-activities/${activity_id}/evidence-files/${evidence_id}`,
    );
    return;
  }

  const list = evidence.get(activity_id) ?? [];
  evidence.set(
    activity_id,
    list.filter((item) => item.id !== evidence_id),
  );
}

export async function download_evidence_file(
  activity_id: number,
  evidence_id: number,
  fallbackName?: string,
): Promise<DownloadEvidenceFileResult> {
  const response = await http.get<Blob>(
    `/api/research-activities/${activity_id}/evidence-files/${evidence_id}/download`,
    {
      responseType: "blob",
    },
  );

  const headerValue = String(response.headers?.["content-disposition"] ?? "");
  const fromHeader = decodeContentDispositionFilename(headerValue);
  const fallback = String(fallbackName ?? "").trim();
  const filename =
    fromHeader?.trim() || fallback || `evidence-${evidence_id}.pdf`;

  return {
    blob: response.data,
    filename,
  };
}

export async function list_evidence_links(
  activity_id: number,
): Promise<EvidenceLinkDto[]> {
  if (!MOCK) {
    const { data } = await http.get<{ data: EvidenceLinkDto[] }>(
      `/api/research-activities/${activity_id}/evidence-links`,
    );
    return data.data ?? [];
  }

  return evidence_links.get(activity_id) ?? [];
}

export async function add_evidence_link(
  activity_id: number,
  payload: {
    url: string;
  },
): Promise<EvidenceLinkDto | null> {
  if (!MOCK) {
    await ensureCsrfCookie();
    const { data } = await http.post<{ data: EvidenceLinkDto | null }>(
      `/api/research-activities/${activity_id}/evidence-links`,
      { url: payload.url },
    );
    return data.data ?? null;
  }

  const trimmed = payload.url.trim();
  if (!trimmed) return null;
  const list = evidence_links.get(activity_id) ?? [];
  const existing = list.find((item) => item.url === trimmed);
  if (existing) return existing;

  const created: EvidenceLinkDto = {
    id: next_evidence_link_id++,
    activity_id,
    lecturer_id: 0,
    lecturer_name: null,
    url: trimmed,
    added_by_user_id: 0,
    created_at: now_iso(),
    updated_at: now_iso(),
  };
  evidence_links.set(activity_id, [created, ...list]);
  return created;
}

export async function delete_evidence_link(
  activity_id: number,
  link_id: number,
): Promise<void> {
  if (!MOCK) {
    await ensureCsrfCookie();
    await http.delete(
      `/api/research-activities/${activity_id}/evidence-links/${link_id}`,
    );
    return;
  }

  const list = evidence_links.get(activity_id) ?? [];
  evidence_links.set(
    activity_id,
    list.filter((item) => item.id !== link_id),
  );
}

export type ResearchActivityDetailResponse = {
  activity: ResearchActivityDto & {
    status_code?: string | null;
    kind_code?: string | null;
  };
  detail_kind?: string | null;
  detail?: Record<string, unknown> | null;
  members?: Array<{
    lecturer_id?: number | null;
    member_role_id: number;
    is_external?: boolean;
    external_full_name?: string | null;
    external_department_name?: string | null;
    contribution_share: number | null;
    hours_assigned: number | null;
    member_role_code?: string | null;
    member_role_name?: string | null;
    lecturer_code?: string | null;
    lecturer_full_name?: string | null;
    department_id?: number | null;
    department_name?: string | null;
    member_faculty_id?: number | null;
    faculty_name?: string | null;
  }>;
  evidence_files?: EvidenceFileDto[];
  evidence_links?: EvidenceLinkDto[];
};

export type ProjectHoursPreviewRequestDto = {
  academic_year_id?: number | null;
  type_id?: number | null;
  quantity?: number | null;
  members: Array<{
    lecturer_id: number;
    member_role_id: number;
  }>;
};

export type ProjectHoursPreviewResponseDto = {
  kind_code: "project";
  type_id: number | null;
  type_code: string | null;
  type_name: string | null;
  level_label?: string | null;
  rule_summary: string;
  distribution_strategy: string | null;
  quantity: number;
  formula: {
    leader_hours: number | null;
    member_pool_hours: number | null;
    member_pool_count: number;
    member_pool_each: number;
    rule_total_hours: number | null;
    total_hours_allocated: number | null;
    progress_multiplier_applied: boolean;
    progress_supported: boolean;
    progress_note: string | null;
  };
  formula_rows?: Array<{
    role_label: string;
    total_hours: number;
    formula_text: string;
  }>;
  current_lecturer_hours: number | null;
  members: Array<{
    lecturer_id: number;
    lecturer_full_name: string;
    member_role_code: string | null;
    member_role_name: string | null;
    hours_assigned: number | null;
    is_leader: boolean;
  }>;
};

export async function fetch_activity(
  activity_id: number,
): Promise<ResearchActivityDetailResponse> {
  const { data } = await http.get<{ data: ResearchActivityDetailResponse }>(
    `/api/research-activities/${activity_id}`,
  );
  return data.data;
}

async function fetch_current_lecturer_payload(): Promise<CurrentLecturerPayload | null> {
  if (
    current_lecturer_cache &&
    Date.now() - current_lecturer_cached_at <= CURRENT_LECTURER_CACHE_TTL_MS
  ) {
    return { ...current_lecturer_cache };
  }
  if (current_lecturer_promise) {
    const payload = await current_lecturer_promise;
    return payload ? { ...payload } : null;
  }

  current_lecturer_promise = http
    .get<{ data: { lecturer?: CurrentLecturerPayload } }>("/api/profile/me")
    .then(({ data }) => {
      const lecturer = data?.data?.lecturer ?? null;
      if (!lecturer?.id) {
        current_lecturer_cache = null;
        current_lecturer_cached_at = 0;
        return null;
      }

      current_lecturer_cache = {
        id: lecturer.id,
        code: lecturer.code ?? null,
        full_name: lecturer.full_name ?? null,
        department_id: lecturer.department_id ?? null,
        department_name: lecturer.department_name ?? null,
      };
      current_lecturer_cached_at = Date.now();

      return current_lecturer_cache;
    })
    .finally(() => {
      current_lecturer_promise = null;
    });

  const payload = await current_lecturer_promise;
  return payload ? { ...payload } : null;
}

export async function fetch_current_lecturer_id(): Promise<number | null> {
  const lecturer = await fetch_current_lecturer_payload();
  return lecturer?.id ?? null;
}

export async function fetch_current_lecturer_option(): Promise<LecturerOptionDto | null> {
  const lecturer = await fetch_current_lecturer_payload();
  if (!lecturer?.id) return null;

  const code = String(lecturer.code ?? "").trim();
  const fullName = String(lecturer.full_name ?? "").trim();
  if (!code || !fullName) return null;

  const departmentId = Number(lecturer.department_id ?? 0);
  return {
    id: lecturer.id,
    code,
    full_name: fullName,
    department_id:
      Number.isFinite(departmentId) && departmentId > 0 ? departmentId : 0,
    department_name: lecturer.department_name ?? undefined,
    faculty_id: null,
    faculty_name: null,
  };
}

export async function preview_project_hours(
  payload: ProjectHoursPreviewRequestDto,
): Promise<ProjectHoursPreviewResponseDto> {
  await ensureCsrfCookie();
  const { data } = await http.post<{ data: ProjectHoursPreviewResponseDto }>(
    "/api/lecturer/declarations/projects/preview-hours",
    payload,
  );
  return data.data;
}
