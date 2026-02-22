export type JournalClassificationDTO = string;

export type JournalDTO = {
  id: number;
  name: string;
  issn: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;

  source_name: string | null;
  point_min: number | null;
  point_max: number | null;

  classification: JournalClassificationDTO;
  research_hours: number;

  is_active: boolean;
  updated_at: string;
};

export type JournalUpsertDTO = {
  id: number;
  name: string;

  issn: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;

  source_name: string | null;
  point_min: number | null;
  point_max: number | null;

  is_active: boolean;
};

export type Journal = {
  id: number;
  name: string;
  issn: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;

  sourceName: string | null;
  pointMin: number | null;
  pointMax: number | null;

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
    address: dto.address ?? null,
    country: dto.country ?? null,
    notes: dto.notes ?? null,

    sourceName: dto.source_name ?? null,
    pointMin: dto.point_min ?? null,
    pointMax: dto.point_max ?? null,

    classification: dto.classification,
    researchHours: dto.research_hours ?? 0,

    isActive: !!dto.is_active,
    updatedAt: dto.updated_at,
  };
}
