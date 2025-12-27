import type {
  EvidenceFileDto,
  ResearchActivityMemberUpsertDto,
} from "../contracts/declarationSharedContract";

/**
 * TODO (backend):
 * - POST /api/research-activities
 * - PUT /api/research-activities/{id}
 * - PUT /api/research-activities/{id}/{detail-kind} (paper_details/book_details/...)
 * - PUT /api/research-activities/{id}/members
 * - POST /api/research-activities/{id}/submit
 * - POST /api/research-activities/{id}/evidence-files (multipart)
 *
 * NOTE: mock dưới đây chỉ để FE dev UI.
 */

const MOCK = true;

type ResearchActivityDto = {
  id: number;
  activity_code: string;
  owner_lecturer_id: number;
  kind_id: number;
  type_id: number | null;
  academic_year_id: number;
  status_id: number;
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
  dto: Omit<ResearchActivityDto, "id" | "activity_code"> & { id?: number }
): Promise<ResearchActivityDto> {
  if (!MOCK) throw new Error("TODO: upsert_activity_base API");

  if (!dto.id) {
    const id = next_id++;

    const created: ResearchActivityDto = {
      id,
      activity_code: `ACT-${id}`,

      // spread trước để không overwrite defaults
      ...dto,

      // defaults cuối (dto không thể ghi đè)
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
    updated_at: now_iso(),
  };

  activities.set(dto.id, updated);
  return updated;
}

export async function upsert_paper_details(dto: PaperDetailsDto) {
  if (!MOCK) throw new Error("TODO: upsert_paper_details API");
  paper_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_book_details(dto: BookDetailsDto) {
  if (!MOCK) throw new Error("TODO: upsert_book_details API");
  book_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_project_details(dto: ProjectDetailsDto) {
  if (!MOCK) throw new Error("TODO: upsert_project_details API");
  project_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_conference_details(dto: ConferenceDetailsDto) {
  if (!MOCK) throw new Error("TODO: upsert_conference_details API");
  conference_details.set(dto.activity_id, dto);
  return dto;
}

export async function upsert_members(
  activity_id: number,
  list: ResearchActivityMemberUpsertDto[]
) {
  if (!MOCK) throw new Error("TODO: upsert_members API");
  members.set(activity_id, list);
  return list;
}

export async function submit_activity(
  activity_id: number,
  submitted_status_id: number
) {
  if (!MOCK) throw new Error("TODO: submit_activity API");
  const prev = activities.get(activity_id);
  if (!prev) throw new Error("Activity not found");
  activities.set(activity_id, {
    ...prev,
    status_id: submitted_status_id,
    submitted_at: now_iso(),
  });
}

export async function list_evidence_files(
  activity_id: number
): Promise<EvidenceFileDto[]> {
  if (!MOCK) throw new Error("TODO: list_evidence_files API");
  return evidence.get(activity_id) ?? [];
}
