export type JournalClassificationDTO =
  | "POINT_GE_2"
  | "POINT_GE_1"
  | "ISSN_ISBN"
  | "OTHER";

export type JournalDTO = {
  id: number;
  name: string;
  issn: string | null;
  journal_type: string | null;
  research_field: string | null;
  website: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;

  source_name: string | null;
  publisher: string | null;
  point: number | null;

  classification: JournalClassificationDTO;
  research_hours: number;

  is_active: boolean;
  updated_at: string;
};

export type JournalUpsertDTO = {
  id: number;
  name: string;

  issn: string | null;
  journal_type: string | null;
  research_field: string | null;
  website: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;

  source_name: string | null;
  publisher: string | null;
  point: number | null;

  is_active: boolean;
};

export type JournalSuggestionDTO = {
  id: number;
  activity_id: number;
  suggestion_type: "journal";
  source_name: string;
  status: "pending" | "approved" | "rejected";
  submitted_by_lecturer_id: number | null;
  submitted_by_user_id: number | null;
  reviewed_by_user_id: number | null;
  reviewed_at: string | null;
  review_note: string | null;
  resolved_catalog_id: number | null;
  payload: {
    name?: string | null;
    issn?: string | null;
    journal_type?: string | null;
    research_field?: string | null;
    website?: string | null;
    address?: string | null;
    country?: string | null;
    notes?: string | null;
    source_name?: string | null;
    publisher?: string | null;
    point?: number | null;
  } | null;
  created_at: string;
  updated_at: string;
};

export type JournalSuggestion = {
  id: number;
  activityId: number;
  sourceName: string;
  status: "pending" | "approved" | "rejected";
  submittedByLecturerId: number | null;
  submittedByUserId: number | null;
  reviewedByUserId: number | null;
  reviewedAt: string | null;
  reviewNote: string | null;
  resolvedCatalogId: number | null;
  payload: JournalSuggestionDTO["payload"];
  createdAt: string;
  updatedAt: string;
};

export type Journal = {
  id: number;
  name: string;
  issn: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;
  journalType?: string | null;
  researchField?: string | null;
  website?: string | null;

  sourceName: string | null;
  publisher: string | null;
  point: number | null;

  classification: JournalClassificationDTO;
  researchHours: number;

  isActive: boolean;
  updatedAt: string;
};

export function journalFromDto(dto: JournalDTO): Journal {
  return {
    id: dto.id,
    name: dto.name,
    issn: dto.issn ?? null,
    journalType: dto.journal_type ?? null,
    researchField: dto.research_field ?? null,
    website: dto.website ?? null,
    address: dto.address ?? null,
    country: dto.country ?? null,
    notes: dto.notes ?? null,

    sourceName: dto.source_name ?? null,
    publisher: dto.publisher ?? null,
    point: dto.point ?? null,

    classification: dto.classification,
    researchHours: dto.research_hours ?? 0,

    isActive: !!dto.is_active,
    updatedAt: dto.updated_at,
  };
}

export function journalSuggestionFromDto(
  dto: JournalSuggestionDTO,
): JournalSuggestion {
  return {
    id: dto.id,
    activityId: dto.activity_id,
    sourceName: dto.source_name,
    status: dto.status,
    submittedByLecturerId: dto.submitted_by_lecturer_id,
    submittedByUserId: dto.submitted_by_user_id,
    reviewedByUserId: dto.reviewed_by_user_id,
    reviewedAt: dto.reviewed_at,
    reviewNote: dto.review_note,
    resolvedCatalogId: dto.resolved_catalog_id,
    payload: dto.payload,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  };
}
