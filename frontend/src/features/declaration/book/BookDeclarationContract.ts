import type { HoursComputationResult } from "../shared/contracts/declarationSharedContract";
import type { ParticipantRowModel } from "../shared/components/ParticipantsTable.vue";
import {
  normalizeParticipantsForHours,
  round2,
} from "../shared/contracts/participantHours.util";
export type BookDeclarationFormModel = {
  activityId: number | null;

  academicYearId: number | null;
  kindId: number; // fixed (book)
  typeId: number | null; // textbook/reference

  title: string;
  notes: string;

  publisher: string;
  year: number | null;
  isbn: string;
  pages: number | null;
  approvalDecisionNo: string;
  approvalDecisionDate: string | null;

  members: ParticipantRowModel[];
};
export const bookAllowedMemberRoleCodes = ["chief_editor", "coauthor"] as const;

export const bookBaseHoursByTypeCode: Record<string, number> = {
  textbook: 900,
  reference: 600,
};

export function computeBookHours(
  model: BookDeclarationFormModel,
  ctx: {
    typeCodeById: Record<number, string>;
    typeHoursById: Record<number, number>;
    lecturerNameById: Record<number, string>;
    memberRoleNameById: Record<number, string>;
    memberRoleCodeById: Record<number, string>;
    currentLecturerId: number;
  },
): HoursComputationResult {
  const typeCode = model.typeId ? ctx.typeCodeById[model.typeId] : null;
  const byTypeConfig =
    model.typeId && Number.isFinite(ctx.typeHoursById[model.typeId])
      ? Number(ctx.typeHoursById[model.typeId])
      : 0;
  const byTypeFallback = typeCode
    ? (bookBaseHoursByTypeCode[typeCode] ?? 0)
    : 0;
  const baseHours = byTypeConfig > 0 ? byTypeConfig : byTypeFallback;

  const participants = normalizeParticipantsForHours(model.members);

  // map role_code cho cả internal + external (để external chief_editor vẫn “ăn” 1/5 nhưng không phân bổ lại)
  const allWithRole = participants.all.map((p) => ({
    ...p,
    role_code: ctx.memberRoleCodeById[p.member_role_id] ?? "",
  }));
  const internalWithRole = participants.internal.map((p) => ({
    ...p,
    role_code: ctx.memberRoleCodeById[p.member_role_id] ?? "",
  }));

  const totalMembers = allWithRole.length;

  // Rule giữ nguyên, chỉ đổi mẫu số
  const sharedPerMember =
    totalMembers > 0 ? (baseHours * 4) / 5 / totalMembers : 0;

  const chiefEditorsCount = allWithRole.filter(
    (p) => p.role_code === "chief_editor",
  ).length;

  const chiefExtra =
    chiefEditorsCount > 0 ? baseHours / 5 / chiefEditorsCount : 0;

  const distribution = internalWithRole.map((p) => {
    const isChief = p.role_code === "chief_editor";
    const hours = sharedPerMember + (isChief ? chiefExtra : 0);

    return {
      lecturer_id: p.lecturer_id,
      lecturer_name:
        ctx.lecturerNameById[p.lecturer_id] ?? `lecturer_id=${p.lecturer_id}`,
      member_role_id: p.member_role_id,
      member_role_name:
        ctx.memberRoleNameById[p.member_role_id] ??
        `member_role_id=${p.member_role_id}`,
      hours: round2(hours),
    };
  });

  const currentLecturerHours =
    distribution.find((d) => d.lecturer_id === ctx.currentLecturerId)?.hours ??
    0;

  return {
    total_hours: round2(baseHours),
    current_lecturer_hours: round2(currentLecturerHours),
    distribution,
  };
}
