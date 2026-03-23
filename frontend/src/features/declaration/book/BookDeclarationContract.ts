import type { HoursComputationResult } from "../shared/contracts/declarationSharedContract";
import type { ParticipantRowModel } from "../shared/components/ParticipantsTable.vue";
import { round2 } from "../shared/contracts/participantHours.util";
export type BookDeclarationFormModel = {
  activityId: number | null;

  academicYearId: number | null;
  kindId: number; // fixed (book)
  typeId: number | null; // textbook/reference

  title: string;
  abstract: string;
  notes: string;

  publisher: string;
  publisherAddress: string;
  publisherPhone: string;
  publisherEmail: string;
  publisherWebsite: string;
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

  const allParticipants = model.members.flatMap((row) => {
    const memberRoleId = row.member_role_id;
    if (typeof memberRoleId !== "number" || !Number.isFinite(memberRoleId)) {
      return [] as Array<{
        kind: "internal" | "external";
        lecturer_id?: number;
        member_role_id: number;
        role_code: string;
        chief_editor_is_coauthor: boolean;
      }>;
    }

    const roleCode = (ctx.memberRoleCodeById[memberRoleId] ?? "").toLowerCase();
    const chiefEditorIsCoauthor = row.chief_editor_is_coauthor !== false;

    if (row.is_external === true) {
      const externalName = String(row.external_full_name ?? "").trim();
      if (!externalName) return [];
      return [
        {
          kind: "external" as const,
          member_role_id: memberRoleId,
          role_code: roleCode,
          chief_editor_is_coauthor: chiefEditorIsCoauthor,
        },
      ];
    }

    const lecturerId = row.lecturer_id;
    if (typeof lecturerId !== "number" || !Number.isFinite(lecturerId)) {
      return [];
    }

    return [
      {
        kind: "internal" as const,
        lecturer_id: lecturerId,
        member_role_id: memberRoleId,
        role_code: roleCode,
        chief_editor_is_coauthor: chiefEditorIsCoauthor,
      },
    ];
  });

  const internalParticipants = allParticipants.filter(
    (
      p,
    ): p is (typeof allParticipants)[number] & {
      kind: "internal";
      lecturer_id: number;
    } => p.kind === "internal" && typeof p.lecturer_id === "number",
  );

  const chiefEditorsCount = allParticipants.filter(
    (p) => p.role_code === "chief_editor",
  ).length;
  const chiefExtra =
    chiefEditorsCount > 0 ? baseHours / 5 / chiefEditorsCount : 0;

  const writingParticipantsCount = allParticipants.filter((p) => {
    if (p.role_code !== "chief_editor") return true;
    return p.chief_editor_is_coauthor;
  }).length;
  const sharedPerMember =
    writingParticipantsCount > 0
      ? (baseHours * 4) / 5 / writingParticipantsCount
      : 0;

  const distribution = internalParticipants.map((p) => {
    const isChief = p.role_code === "chief_editor";
    const joinWritingPool = !isChief || p.chief_editor_is_coauthor;
    const hours =
      (joinWritingPool ? sharedPerMember : 0) + (isChief ? chiefExtra : 0);

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
