export type ConferenceLevel =
  | "FACULTY"
  | "UNIVERSITY"
  | "NATIONAL"
  | "INTERNATIONAL";

// DTO (snake_case)
export interface ConferenceDTO {
  id: number;
  name: string;
  level: ConferenceLevel;
  notes: string | null;
  is_active: boolean;
  updated_at: string;
}

export interface ConferenceUpsertDTO {
  name: string;
  level: ConferenceLevel;
  notes: string | null;
  is_active: boolean;
}

// UI Model (camelCase)
export interface Conference {
  id: number;
  name: string;
  level: ConferenceLevel;
  notes: string | null;
  isActive: boolean;
  updatedAt: string;
}

export function conferenceFromDto(dto: ConferenceDTO): Conference {
  return {
    id: dto.id,
    name: dto.name,
    level: dto.level,
    notes: dto.notes,
    isActive: dto.is_active,
    updatedAt: dto.updated_at,
  };
}
