// File: src/features/declaration/conference/ConferenceDeclarationContract.ts
import type {
  EvidenceFileDto,
  EvidenceLinkDto,
  HoursDistributionItem,
} from "../shared/contracts/declarationSharedContract";

export type ConferenceOccurrenceTypeCode = "report" | "attend";

export interface ConferenceOccurrenceFormItem {
  rowId: string;
  typeId: number | null;

  conferenceName: string;
  heldOn: string | null; // yyyy-mm-dd
  location: string;
  notes: string;

  // Evidence per-row (UI-only)
  existingEvidenceFiles: EvidenceFileDto[];
  existingEvidenceLinks: EvidenceLinkDto[];
  pendingEvidenceFiles: any[]; // giữ any để khớp EvidenceUpload hiện tại của bạn
  pendingEvidenceLinks: any[];
}

export interface ConferenceDeclarationFormModel {
  activityIds: number[]; // nếu backend persist theo từng activity cho từng row
  academicYearId: number | null;
  kindId: number;
  items: ConferenceOccurrenceFormItem[];
}

export interface ComputeConferenceHoursContext {
  currentLecturerId: number;
  currentLecturerName: string;
  typeCodeById: Record<number, string>;
  typeHoursById: Record<number, number>;
  typeMaxOccurrencesById: Record<number, number | null>;
}

export interface ComputeConferenceHoursResult {
  result: {
    total_hours: number;
    current_lecturer_hours: number;
    report_count: number;
    attend_count: number;
    valid_attend_count: number;
    report_hours_total: number;
    attend_hours_total: number;
    distribution: HoursDistributionItem[];
  };
  warnings: string[];
}

export function createConferenceRowId(): string {
  // deterministic enough for UI keys
  return `row_${Math.random().toString(16).slice(2)}_${Date.now()}`;
}

export function computeConferenceHours(
  model: ConferenceDeclarationFormModel,
  ctx: ComputeConferenceHoursContext,
): ComputeConferenceHoursResult {
  let reportCount = 0;
  let attendCount = 0;
  let reportHoursTotal = 0;

  const attendTypeStats = new Map<
    number,
    { count: number; hoursPerOccurrence: number; maxOccurrences: number | null }
  >();

  for (const row of model.items) {
    if (!row.typeId) continue;
    const code = ctx.typeCodeById[row.typeId] as string | undefined;
    const configuredHours = Number(ctx.typeHoursById[row.typeId] ?? 0);

    if (code === "report") {
      reportCount += 1;
      reportHoursTotal += configuredHours;
    }

    if (code === "attend") {
      attendCount += 1;

      const prev = attendTypeStats.get(row.typeId) ?? {
        count: 0,
        hoursPerOccurrence: configuredHours,
        maxOccurrences: ctx.typeMaxOccurrencesById[row.typeId] ?? null,
      };

      attendTypeStats.set(row.typeId, {
        ...prev,
        count: prev.count + 1,
      });
    }
  }

  let validAttend = 0;
  let attendHoursTotal = 0;

  const warnings: string[] = [];
  for (const [, stat] of attendTypeStats) {
    const cap =
      typeof stat.maxOccurrences === "number" && stat.maxOccurrences > 0
        ? stat.maxOccurrences
        : stat.count;

    const validCount = Math.min(stat.count, cap);
    validAttend += validCount;
    attendHoursTotal += validCount * stat.hoursPerOccurrence;

    if (stat.count > cap) {
      warnings.push(
        `Bạn đã kê khai ${stat.count} lần tham dự. Hệ thống chỉ tính tối đa ${cap} lần theo cấu hình quy đổi giờ NCKH.`,
      );
    }
  }

  const totalHours = reportHoursTotal + attendHoursTotal;

  const distribution: HoursDistributionItem[] = totalHours
    ? [
        {
          lecturer_id: ctx.currentLecturerId,
          lecturer_name: ctx.currentLecturerName,
          member_role_id: 0, // conference không có vai trò => dùng 0 cho hợp type
          member_role_name: "Tham gia",
          hours: totalHours,
        },
      ]
    : [];

  return {
    result: {
      total_hours: totalHours,
      current_lecturer_hours: totalHours,
      report_count: reportCount,
      attend_count: attendCount,
      valid_attend_count: validAttend,
      report_hours_total: reportHoursTotal,
      attend_hours_total: attendHoursTotal,
      distribution,
    },
    warnings,
  };
}
