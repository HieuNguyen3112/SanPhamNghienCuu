// File: src/features/declaration/conference/ConferenceDeclarationContract.ts
import type {
  EvidenceFileDto,
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
}

export interface ComputeConferenceHoursResult {
  result: {
    total_hours: number;
    current_lecturer_hours: number;
    report_count: number;
    attend_count: number;
    valid_attend_count: number;
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
  ctx: ComputeConferenceHoursContext
): ComputeConferenceHoursResult {
  let reportCount = 0;
  let attendCount = 0;

  for (const row of model.items) {
    if (!row.typeId) continue;
    const code = ctx.typeCodeById[row.typeId] as string | undefined;
    if (code === "report") reportCount += 1;
    if (code === "attend") attendCount += 1;
  }

  const validAttend = Math.min(attendCount, 40);

  const totalHours = reportCount * 40 + validAttend * 4;

  const warnings: string[] = [];
  if (attendCount > 40) {
    warnings.push(
      `Bạn đã kê khai ${attendCount} lần tham dự. Hệ thống chỉ tính tối đa 40 lần (160 giờ). Phần vượt sẽ không được tính.`
    );
  }

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
      distribution,
    },
    warnings,
  };
}
