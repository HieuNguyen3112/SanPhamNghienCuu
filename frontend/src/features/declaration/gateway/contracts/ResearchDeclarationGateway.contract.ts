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

function normalizeDraftTypeLabel(dto: DeclarationDraftRowDTO): string {
  const label = String(dto.type_label ?? "").trim();
  const lower = label.toLowerCase();
  const route = String(dto.to ?? "").toLowerCase();

  // Legacy backend labels may append hour classes (e.g. ISSN/ISBN 300h).
  // In draft list we only need declaration kind for quick recognition.
  if (lower.startsWith("bài báo khoa học -")) return "Bài báo khoa học";
  if (lower.startsWith("báo cáo khoa học -")) return "Báo cáo khoa học";

  if (route.includes("/declaration/article")) return "Bài báo khoa học";
  if (route.includes("/declaration/conference")) return "Báo cáo hội thảo";

  return label || "—";
}

export function declarationTypeCardFromDto(
  dto: DeclarationTypeCardDTO,
): DeclarationTypeCard {
  return {
    typeKey: dto.type_key,
    title: dto.title,
    descriptionLines: dto.description_lines,
    to: dto.to,
  };
}

export function draftDeclarationItemFromDto(
  dto: DeclarationDraftRowDTO,
): DraftDeclarationItem {
  return {
    id: dto.id,
    title: dto.title,
    typeLabel: normalizeDraftTypeLabel(dto),
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
