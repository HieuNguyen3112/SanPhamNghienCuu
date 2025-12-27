import type { HoursComputationResult } from "../shared/contracts/declarationSharedContract";
import type { ParticipantRowModel } from "../shared/components/ParticipantsTable.vue";
import {
  normalizeParticipantsForHours,
  round2,
} from "../shared/contracts/participantHours.util";
export type ProjectDeclarationFormModel = {
  activityId: number | null;

  academicYearId: number | null;
  kindId: number; // fixed (project)
  typeId: number | null; // project level (activity_types.id)

  title: string;
  notes: string;

  // UI-only year fields (mapped to research_activities.start_date/end_date)
  startYear: number | null;
  endYear: number | null;

  projectCode: string;
  decisionNo: string;
  decisionDate: string | null; // DATE
  funding: number | null;

  members: ParticipantRowModel[];
};

export type ProjectHoursRule = {
  // base hours by activity_types.code (P0: backend should source from hour_rules or seeded activity_types)
  baseHoursByTypeCode: Record<string, number>;
  // fractions (Assumption for mock): principal 0.5, secretary 0.2, members share rest equally
  principalFraction: number;
  secretaryFraction: number;
};
export const projectAllowedMemberRoleCodes = [
  "principal", // chủ nhiệm
  "secretary", // thư ký
  "member", // thành viên
] as const;

export const projectHoursRule: ProjectHoursRule = {
  baseHoursByTypeCode: {
    ministry: 900,
    province: 600,
    university: 300,
    faculty: 200,
    other: 100,
  },
  principalFraction: 0.5,
  secretaryFraction: 0.2,
};

export function computeProjectHours(
  model: ProjectDeclarationFormModel,
  ctx: {
    typeCodeById: Record<number, string>;
    lecturerNameById: Record<number, string>;
    memberRoleNameById: Record<number, string>;
    memberRoleCodeById: Record<number, string>;
    currentLecturerId: number;
  }
): HoursComputationResult {
  const typeCode = model.typeId ? ctx.typeCodeById[model.typeId] : null;
  const baseHours = typeCode
    ? projectHoursRule.baseHoursByTypeCode[typeCode] ?? 0
    : 0;

  const participants = normalizeParticipantsForHours(model.members);

  const allWithRole = participants.all.map((p) => ({
    ...p,
    role_code: ctx.memberRoleCodeById[p.member_role_id] ?? "",
  }));
  const internalWithRole = participants.internal.map((p) => ({
    ...p,
    role_code: ctx.memberRoleCodeById[p.member_role_id] ?? "",
  }));

  // ✅ giữ logic cũ: pick 1 principal + 1 secretary, nhưng xét trên ALL (có thể external)
  const principalExists = allWithRole.some((p) => p.role_code === "principal");
  const secretaryExists = allWithRole.some((p) => p.role_code === "secretary");

  const principalHours = principalExists
    ? baseHours * projectHoursRule.principalFraction
    : 0;
  const secretaryHours = secretaryExists
    ? baseHours * projectHoursRule.secretaryFraction
    : 0;

  const remaining = Math.max(0, baseHours - principalHours - secretaryHours);

  // ✅ “others” mẫu số = số người còn lại (bao gồm external)
  const othersCount = allWithRole.filter(
    (p) => p.role_code !== "principal" && p.role_code !== "secretary"
  ).length;

  const hoursPerOther = othersCount > 0 ? remaining / othersCount : 0;

  const distribution = internalWithRole.map((p) => {
    let hours = 0;

    if (p.role_code === "principal") hours = principalHours;
    else if (p.role_code === "secretary") hours = secretaryHours;
    else hours = hoursPerOther;

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
