export type ConferenceLevel = "NATIONAL" | "INTERNATIONAL";

// DTO (snake_case)
export interface ConferenceDTO {
  id: number;
  name: string;
  level: ConferenceLevel;
  research_field: string | null;
  organization: string | null;
  year: number | null;
  has_proceedings: boolean;
  has_isbn: boolean;
  isbn: string | null;
  point: number | null;
  research_hours: number | null;
  notes: string | null;
  is_active: boolean;
  updated_at: string;
}

export interface ConferenceUpsertDTO {
  name: string;
  level: ConferenceLevel;
  research_field: string | null;
  organization: string | null;
  year: number | null;
  has_proceedings: boolean;
  has_isbn: boolean;
  isbn: string | null;
  point: number | null;
  notes: string | null;
  is_active: boolean;
}

export interface ConferenceSuggestionDTO {
  id: number;
  activity_id: number;
  suggestion_type: "conference";
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
    level?: ConferenceLevel | null;
    research_field?: string | null;
    organization?: string | null;
    year?: number | null;
    has_proceedings?: boolean;
    has_isbn?: boolean;
    isbn?: string | null;
    point?: number | null;
    notes?: string | null;
  } | null;
  created_at: string;
  updated_at: string;
}

export interface ConferenceSuggestion {
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
  payload: ConferenceSuggestionDTO["payload"];
  createdAt: string;
  updatedAt: string;
}

// UI Model (camelCase)
export interface Conference {
  id: number;
  name: string;
  level: ConferenceLevel;
  researchField: string | null;
  organization: string | null;
  year: number | null;
  hasProceedings: boolean;
  hasIsbn: boolean;
  isbn: string | null;
  point: number | null;
  researchHours: number | null;
  notes: string | null;
  isActive: boolean;
  updatedAt: string;
}

export function conferenceFromDto(dto: ConferenceDTO): Conference {
  return {
    id: dto.id,
    name: dto.name,
    level: dto.level,
    researchField: dto.research_field ?? null,
    organization: dto.organization ?? null,
    year: dto.year ?? null,
    hasProceedings: Boolean(dto.has_proceedings),
    hasIsbn: Boolean(dto.has_isbn),
    isbn: dto.isbn ?? null,
    point:
      dto.point !== null && dto.point !== undefined ? Number(dto.point) : null,
    researchHours:
      dto.research_hours !== null && dto.research_hours !== undefined
        ? Number(dto.research_hours)
        : null,
    notes: dto.notes,
    isActive: dto.is_active,
    updatedAt: dto.updated_at,
  };
}

export function conferenceSuggestionFromDto(
  dto: ConferenceSuggestionDTO,
): ConferenceSuggestion {
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
