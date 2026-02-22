export type WorkLevelDTO = {
  id: number;
  name: string;
  priority: number;
  notes: string | null;
  is_active: boolean;
  updated_at: string;
};

export type WorkLevelUpsertDTO = {
  id: number;
  name: string;
  priority: number;
  notes: string | null;
  is_active: boolean;
};

export type WorkLevel = {
  id: number;
  name: string;
  priority: number;
  notes: string | null;
  isActive: boolean;
  updatedAt: string;
};

export function workLevelFromDto(dto: WorkLevelDTO): WorkLevel {
  return {
    id: dto.id,
    name: dto.name,
    priority: dto.priority,
    notes: dto.notes ?? null,
    isActive: !!dto.is_active,
    updatedAt: dto.updated_at,
  };
}
