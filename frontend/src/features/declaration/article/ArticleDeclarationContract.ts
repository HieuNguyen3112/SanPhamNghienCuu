import type { HoursComputationResult } from "../shared/contracts/declarationSharedContract";
import type { ParticipantRowModel } from "../shared/components/ParticipantsTable.vue";
import {
  normalizeParticipantsForHours,
  round2,
} from "../shared/contracts/participantHours.util";
export type ArticleDeclarationFormModel = {
  activityId: number | null;

  academicYearId: number | null;
  kindId: number; // fixed (paper)
  typeId: number | null; // activity_types.id (hdgsnn_900/600/300)

  title: string;
  abstract: string;
  notes: string;

  journalId: number | null;
  journalName: string;
  journalResearchHours: number | null;
  issn: string;
  doi: string;
  articleUrl: string;
  volume: string;
  issue: string;
  year: number | null;
  pageStart: number | null;
  pageEnd: number | null;
  publicationStatus: string;

  journalScope: string;
  journalSourceName: string;
  journalPublisher: string;
  journalWebsite: string;
  workScore: number | null;

  members: ParticipantRowModel[];
};
export const articleAllowedMemberRoleCodes = [
  "corresponding_author",
  "coauthor",
] as const;

export const articleBaseHoursByTypeCode: Record<string, number> = {
  hdgsnn_900: 900,
  hdgsnn_600: 600,
  hdgsnn_300: 300,
};

export function computeArticleHours(
  model: ArticleDeclarationFormModel,
  ctx: {
    typeCodeById: Record<number, string>;
    typeHoursById: Record<number, number>;
    explicitBaseHours?: number | null;
    lecturerNameById: Record<number, string>;
    memberRoleNameById: Record<number, string>;
    currentLecturerId: number;
  },
): HoursComputationResult {
  const typeCode = model.typeId ? ctx.typeCodeById[model.typeId] : null;
  const byTypeConfig =
    model.typeId && Number.isFinite(ctx.typeHoursById[model.typeId])
      ? Number(ctx.typeHoursById[model.typeId])
      : 0;
  const byTypeFallback = typeCode
    ? (articleBaseHoursByTypeCode[typeCode] ?? 0)
    : 0;
  const overrideHours =
    typeof ctx.explicitBaseHours === "number" &&
    Number.isFinite(ctx.explicitBaseHours)
      ? Number(ctx.explicitBaseHours)
      : null;
  const baseHours =
    overrideHours !== null
      ? overrideHours
      : byTypeConfig > 0
        ? byTypeConfig
        : byTypeFallback;

  const participants = normalizeParticipantsForHours(model.members);

  // ✅ mẫu số = tổng tác giả (trong + ngoài)
  const hoursPerAuthor =
    participants.total_authors > 0 ? baseHours / participants.total_authors : 0;

  // ✅ chỉ phân bổ cho internal
  const distribution = participants.internal.map((p) => ({
    lecturer_id: p.lecturer_id,
    lecturer_name:
      ctx.lecturerNameById[p.lecturer_id] ?? `lecturer_id=${p.lecturer_id}`,
    member_role_id: p.member_role_id,
    member_role_name:
      ctx.memberRoleNameById[p.member_role_id] ??
      `member_role_id=${p.member_role_id}`,
    hours: round2(hoursPerAuthor),
  }));

  const currentLecturerHours =
    distribution.find((d) => d.lecturer_id === ctx.currentLecturerId)?.hours ??
    0;

  return {
    total_hours: round2(baseHours),
    current_lecturer_hours: round2(currentLecturerHours),
    distribution,
  };
}
