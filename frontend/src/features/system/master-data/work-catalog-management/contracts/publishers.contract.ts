export interface PublisherDTO {
  id: number;
  name: string;
  code: string;
  address: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  is_active: boolean;
  updated_at: string;
}

export interface PublisherSuggestionDTO {
  id: number;
  activity_id: number;
  suggestion_type: "publisher";
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
    code?: string | null;
    address?: string | null;
    phone?: string | null;
    email?: string | null;
    website?: string | null;
    isbn?: string | null;
    year?: number | null;
    pages?: number | null;
  } | null;
  created_at: string;
  updated_at: string;
}

export interface PublisherUpsertDTO {
  name: string;
  code: string;
  address: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  is_active: boolean;
}

export interface Publisher {
  id: number;
  name: string;
  code: string;
  address: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  isActive: boolean;
  updatedAt: string;
}

export interface PublisherSuggestion {
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
  payload: PublisherSuggestionDTO["payload"];
  createdAt: string;
  updatedAt: string;
}

export function publisherFromDto(dto: PublisherDTO): Publisher {
  return {
    id: dto.id,
    name: dto.name,
    code: dto.code,
    address: dto.address ?? null,
    phone: dto.phone ?? null,
    email: dto.email ?? null,
    website: dto.website ?? null,
    isActive: !!dto.is_active,
    updatedAt: dto.updated_at,
  };
}

export function publisherSuggestionFromDto(
  dto: PublisherSuggestionDTO,
): PublisherSuggestion {
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
