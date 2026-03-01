import http, { ensureCsrfCookie } from "@/lib/http";
import type {
  EvidenceFileDto,
  ResearchActivityMemberUpsertDto,
} from "../contracts/declarationSharedContract";

// Backend endpoints:
// - POST /api/research-activities
// - PUT /api/research-activities/{id}
// - PUT /api/research-activities/{id}/{detail-kind}
// - PUT /api/research-activities/{id}/members
// - POST /api/research-activities/{id}/submit
// - GET  /api/research-activities/{id}/evidence-files

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

let next_id = 1000;
const activities = new Map<number, ResearchActivityDto>();
const paper_details = new Map<number, PaperDetailsDto>();
const book_details = new Map<number, BookDetailsDto>();
const project_details = new Map<number, ProjectDetailsDto>();
const conference_details = new Map<number, ConferenceDetailsDto>();
const members = new Map<number, ResearchActivityMemberUpsertDto[]>();
const evidence = new Map<number, EvidenceFileDto[]>();

function now_iso() {
  return new Date().toISOString();
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

export type ResearchActivityDetailResponse = {
  activity: ResearchActivityDto & {
    status_code?: string | null;
    kind_code?: string | null;
  };
  detail_kind?: string | null;
  detail?: Record<string, unknown> | null;
  members?: Array<{
    lecturer_id: number;
    member_role_id: number;
    contribution_share: number | null;
    hours_assigned: number | null;
    member_role_code?: string | null;
    member_role_name?: string | null;
  }>;
  evidence_files?: EvidenceFileDto[];
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

export async function fetch_current_lecturer_id(): Promise<number | null> {
  const { data } = await http.get<{ data: { lecturer?: { id?: number } } }>(
    "/api/profile/me",
  );
  return data?.data?.lecturer?.id ?? null;
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
