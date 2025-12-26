import type { LucideIcon } from "lucide-vue-next";

/**
 * DTO (snake_case) - giả lập backend trả về
 */
export interface DeclarationTypeCardDTO {
  type_key: string;
  title: string;
  description_lines: string[];
  to: string; // router path
}

export interface DeclarationDraftRowDTO {
  id: number;
  title: string;
  type_label: string;
  updated_at: string; // ISO
  to: string; // router path
}

/**
 * UI Model (camelCase)
 */
export interface DeclarationTypeCard {
  typeKey: string;
  title: string;
  descriptionLines: string[];
  icon?: LucideIcon; // optional: gán ở UI layer
  to: string;
}

export interface DraftDeclarationItem {
  id: number;
  title: string;
  typeLabel: string;
  updatedAt: string; // ISO
  to: string;
}

/**
 * ✅ Backward-compat (fix TS2305)
 * Một số file cũ đang import DeclarationDraftRow
 */
export type DeclarationDraftRow = DraftDeclarationItem;

export function declarationTypeCardFromDto(
  dto: DeclarationTypeCardDTO
): DeclarationTypeCard {
  return {
    typeKey: dto.type_key,
    title: dto.title,
    descriptionLines: dto.description_lines,
    to: dto.to,
  };
}

export function draftDeclarationItemFromDto(
  dto: DeclarationDraftRowDTO
): DraftDeclarationItem {
  return {
    id: dto.id,
    title: dto.title,
    typeLabel: dto.type_label,
    updatedAt: dto.updated_at,
    to: dto.to,
  };
}

export function formatDateVietnamese(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return "—";
  return date.toLocaleDateString("vi-VN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
}
