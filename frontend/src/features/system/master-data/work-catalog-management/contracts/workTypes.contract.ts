export type WorkTypeDTO = {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
  updated_at: string;
};

export type WorkTypeUpsertDTO = {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
};

export type WorkType = {
  id: number;
  name: string;
  description: string | null;
  isActive: boolean;
  updatedAt: string;
};

export function workTypeFromDto(dto: WorkTypeDTO): WorkType {
  return {
    id: dto.id,
    name: dto.name,
    description: dto.description ?? null,
    isActive: !!dto.is_active,
    updatedAt: dto.updated_at,
  };
}
