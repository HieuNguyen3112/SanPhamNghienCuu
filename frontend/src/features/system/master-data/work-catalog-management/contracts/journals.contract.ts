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
