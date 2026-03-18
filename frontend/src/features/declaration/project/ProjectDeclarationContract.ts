import type { HoursDistributionItem } from "../shared/contracts/declarationSharedContract";
import type { ParticipantRowModel } from "../shared/components/ParticipantsTable.vue";
import {
  normalizeParticipantsForHours,
  round2,
} from "../shared/contracts/participantHours.util";

export type ProjectDeclarationFormModel = {
  activityId: number | null;
  academicYearId: number | null;
  kindId: number;
  typeId: number | null;
  title: string;
  abstract: string;
  notes: string;
  startDate: string | null;
  endDate: string | null;
  projectCode: string;
  projectCategory: string;
  researchField: string;
  objectives: string;
  contentSummary: string;
  applicationAddress: string;
  implementingUnit: string;
  projectStatus: string;
  mainResults: string;
  decisionNo: string;
  decisionDate: string | null;
  funding: number | null;
  members: ParticipantRowModel[];
};

export type ProjectFormulaRow = {
  role_label: string;
  total_hours: number;
  formula_text: string;
};

export type ProjectHoursComputationResult = {
  total_hours: number;
  current_lecturer_hours: number;
  distribution: HoursDistributionItem[];
  has_rule: boolean;
  rule_type_code: string | null;
  rule_label: string | null;
  leader_hours: number;
  member_pool_hours: number;
  member_pool_count: number;
  member_pool_each: number;
  formula_rows: ProjectFormulaRow[];
  progress_note: string | null;
};

type ProjectSplitRule = {
  type_codes: readonly string[];
  rule_label: string;
  leader_hours: number;
  member_pool_hours: number;
};

const PROJECT_SPLIT_RULES: readonly ProjectSplitRule[] = [
  {
    type_codes: ["bo", "ministry"],
    rule_label: "Đề tài cấp Bộ (2 năm)",
    leader_hours: 720,
    member_pool_hours: 480,
  },
  {
    type_codes: ["coso", "university"],
    rule_label: "Đề tài cấp cơ sở (1 năm)",
    leader_hours: 600,
    member_pool_hours: 240,
  },
];

export const projectAllowedMemberRoleCodes = [
  "principal",
  "secretary",
  "member",
] as const;

function getRuleByTypeCode(typeCode: string | null): ProjectSplitRule | null {
  if (!typeCode) return null;
  const normalized = typeCode.trim().toLowerCase();
  return (
    PROJECT_SPLIT_RULES.find((rule) => rule.type_codes.includes(normalized)) ??
    null
  );
}

function isLeaderRole(roleCode: string): boolean {
  return roleCode === "principal";
}

export function computeProjectHours(
  model: ProjectDeclarationFormModel,
  ctx: {
    typeCodeById: Record<number, string>;
    lecturerNameById: Record<number, string>;
    memberRoleNameById: Record<number, string>;
    memberRoleCodeById: Record<number, string>;
    currentLecturerId: number;
  },
): ProjectHoursComputationResult {
  const typeCode = model.typeId
    ? (ctx.typeCodeById[model.typeId] ?? null)
    : null;
  const rule = getRuleByTypeCode(typeCode);
  const participants = normalizeParticipantsForHours(model.members);

  const internal = participants.internal.map((participant) => {
    const roleCode =
      ctx.memberRoleCodeById[participant.member_role_id]?.toLowerCase() ?? "";
    return {
      lecturer_id: participant.lecturer_id,
      member_role_id: participant.member_role_id,
      member_role_code: roleCode,
      member_role_name:
        ctx.memberRoleNameById[participant.member_role_id] ??
        `member_role_id=${participant.member_role_id}`,
      lecturer_name:
        ctx.lecturerNameById[participant.lecturer_id] ??
        `lecturer_id=${participant.lecturer_id}`,
    };
  });

  const leaderRuleHours = rule?.leader_hours ?? 0;
  const memberPoolHours = rule?.member_pool_hours ?? 0;

  const explicitLeader = internal.find((member) =>
    isLeaderRole(member.member_role_code),
  );
  const fallbackLeader =
    explicitLeader ??
    internal.find((member) => member.lecturer_id === ctx.currentLecturerId) ??
    internal[0] ??
    null;

  const poolMembers = internal.filter(
    (member) => member.lecturer_id !== fallbackLeader?.lecturer_id,
  );

  const memberPoolCount = poolMembers.length;
  const memberPoolEach =
    memberPoolCount > 0 ? round2(memberPoolHours / memberPoolCount) : 0;
  const leaderHours =
    memberPoolCount > 0
      ? round2(Math.max(0, leaderRuleHours - memberPoolHours))
      : leaderRuleHours;
  const totalHours = round2(
    leaderHours + (memberPoolCount > 0 ? memberPoolHours : 0),
  );

  const distribution: HoursDistributionItem[] = internal.map((member) => {
    const hours =
      member.lecturer_id === fallbackLeader?.lecturer_id
        ? leaderHours
        : memberPoolCount > 0
          ? memberPoolEach
          : 0;
    return {
      lecturer_id: member.lecturer_id,
      lecturer_name: member.lecturer_name,
      member_role_id: member.member_role_id,
      member_role_name: member.member_role_name,
      hours: round2(hours),
    };
  });

  const currentLecturerHours =
    distribution.find((member) => member.lecturer_id === ctx.currentLecturerId)
      ?.hours ?? 0;

  const formulaRows: ProjectFormulaRow[] = rule
    ? [
        {
          role_label: "Chủ nhiệm",
          total_hours: leaderHours,
          formula_text:
            memberPoolCount > 0
              ? `${rule.leader_hours} - ${rule.member_pool_hours} = ${leaderHours.toFixed(
                  2,
                )} giờ`
              : `${rule.leader_hours} giờ (không có thành viên)`,
        },
        {
          role_label: "Nhóm thành viên",
          total_hours: rule.member_pool_hours,
          formula_text:
            memberPoolCount > 0
              ? `${rule.member_pool_hours} / ${memberPoolCount} = ${memberPoolEach.toFixed(
                  2,
                )} giờ/người`
              : `${rule.member_pool_hours} / 0 = 0 giờ/người (chưa có thành viên)`,
        },
      ]
    : [];

  return {
    total_hours: totalHours,
    current_lecturer_hours: round2(currentLecturerHours),
    distribution,
    has_rule: rule !== null,
    rule_type_code: typeCode,
    rule_label: rule?.rule_label ?? null,
    leader_hours: leaderHours,
    member_pool_hours: memberPoolHours,
    member_pool_count: memberPoolCount,
    member_pool_each: memberPoolEach,
    formula_rows: formulaRows,
    progress_note:
      "Chưa áp dụng % tiến độ do chưa có trường dữ liệu trong hệ thống.",
  };
}
