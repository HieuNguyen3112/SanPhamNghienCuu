export type ResearchFieldDTO = {
  id: number;
  code: string | null;
  name: string;
  description: string | null;
  is_active: boolean;
  updated_at: string;
};

export type ResearchFieldUpsertDTO = {
  id: number;
  code: string | null;
  name: string;
  description: string | null;
  is_active: boolean;
};

export type ResearchField = {
  id: number;
  code: string | null;
  name: string;
  description: string | null;
  isActive: boolean;
  updatedAt: string;
};

export function researchFieldFromDto(dto: ResearchFieldDTO): ResearchField {
  return {
    id: dto.id,
    code: dto.code ?? null,
    name: dto.name,
    description: dto.description ?? null,
    isActive: !!dto.is_active,
    updatedAt: dto.updated_at,
  };
}
