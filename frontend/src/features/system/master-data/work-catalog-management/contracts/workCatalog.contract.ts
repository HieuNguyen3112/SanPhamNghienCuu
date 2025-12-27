export type CatalogStatusDTO = "active" | "inactive";

// ===== TAB KEYS =====
export type WorkCatalogTabKey =
  | "work_type"
  | "work_level"
  | "journal"
  | "conference"
  | "research_field";

// ===== DTOs (snake_case) =====
// TODO(BE): Các bảng/field master-data dưới đây chưa có trong schema paste.
// Đây là DTO giả lập để UI chạy đúng theo spec.

export interface WorkTypeDTO {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
  updated_at: string;
}

export interface WorkLevelDTO {
  id: number;
  name: string;
  priority: number;
  notes: string | null;
  is_active: boolean;
  updated_at: string;
}

export type JournalClassificationDTO = "ISI" | "SCOPUS" | "OTHER";
export type JournalRankDTO = "Q1" | "Q2" | "Q3" | "Q4" | "Q5" | "OTHER";

export interface JournalDTO {
  id: number;
  name: string;
  address: string | null;
  issn: string | null;
  classification: JournalClassificationDTO;
  country: string | null;
  notes: string | null;
  is_active: boolean;
  updated_at: string;

  // computed from ranking history
  current_rank: JournalRankDTO | null;
  current_rank_effective_from: string | null; // YYYY-MM-DD
}

export interface JournalRankingDTO {
  id: number;
  journal_id: number;
  rank: JournalRankDTO;
  effective_from: string; // YYYY-MM-DD
  note: string | null;
  created_at: string; // ISO
}

export type ConferenceLevelDTO =
  | "FACULTY"
  | "UNIVERSITY"
  | "NATIONAL"
  | "INTERNATIONAL";

export interface ConferenceDTO {
  id: number;
  name: string;
  level: ConferenceLevelDTO;
  notes: string | null;
  is_active: boolean;
  updated_at: string;
}

export interface ResearchFieldDTO {
  id: number;
  code: string | null;
  name: string;
  description: string | null;
  is_active: boolean;
  updated_at: string;
}

// ===== UI Models (camelCase) =====
export interface WorkType {
  id: number;
  name: string;
  description: string | null;
  isActive: boolean;
  updatedAt: string;
}

export interface WorkLevel {
  id: number;
  name: string;
  priority: number;
  notes: string | null;
  isActive: boolean;
  updatedAt: string;
}

export type JournalClassification = JournalClassificationDTO;
export type JournalRank = JournalRankDTO;

export interface Journal {
  id: number;
  name: string;
  address: string | null;
  issn: string | null;
  classification: JournalClassification;
  country: string | null;
  notes: string | null;
  isActive: boolean;
  updatedAt: string;

  currentRank: JournalRank | null;
  currentRankEffectiveFrom: string | null;
}

export interface JournalRanking {
  id: number;
  journalId: number;
  rank: JournalRank;
  effectiveFrom: string;
  note: string | null;
  createdAt: string;
}

export type ConferenceLevel = ConferenceLevelDTO;
export interface Conference {
  id: number;
  name: string;
  level: ConferenceLevel;
  notes: string | null;
  isActive: boolean;
  updatedAt: string;
}

export interface ResearchField {
  id: number;
  code: string | null;
  name: string;
  description: string | null;
  isActive: boolean;
  updatedAt: string;
}

// ===== Mappers (in-contract) =====
export function workTypeFromDto(dto: WorkTypeDTO): WorkType {
  return {
    id: dto.id,
    name: dto.name,
    description: dto.description,
    isActive: dto.is_active,
    updatedAt: dto.updated_at,
  };
}

export function workLevelFromDto(dto: WorkLevelDTO): WorkLevel {
  return {
    id: dto.id,
    name: dto.name,
    priority: dto.priority,
    notes: dto.notes,
    isActive: dto.is_active,
    updatedAt: dto.updated_at,
  };
}

export function journalFromDto(dto: JournalDTO): Journal {
  return {
    id: dto.id,
    name: dto.name,
    address: dto.address,
    issn: dto.issn,
    classification: dto.classification,
    country: dto.country,
    notes: dto.notes,
    isActive: dto.is_active,
    updatedAt: dto.updated_at,

    currentRank: dto.current_rank,
    currentRankEffectiveFrom: dto.current_rank_effective_from,
  };
}

export function journalRankingFromDto(dto: JournalRankingDTO): JournalRanking {
  return {
    id: dto.id,
    journalId: dto.journal_id,
    rank: dto.rank,
    effectiveFrom: dto.effective_from,
    note: dto.note,
    createdAt: dto.created_at,
  };
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

export function researchFieldFromDto(dto: ResearchFieldDTO): ResearchField {
  return {
    id: dto.id,
    code: dto.code,
    name: dto.name,
    description: dto.description,
    isActive: dto.is_active,
    updatedAt: dto.updated_at,
  };
}

// ===== Helpers =====
export function formatDateTime(value: string): string {
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(
    d.getHours()
  )}:${pad(d.getMinutes())}`;
}

export function toLowerSafe(v: string | null | undefined): string {
  return (v ?? "").toLowerCase();
}
