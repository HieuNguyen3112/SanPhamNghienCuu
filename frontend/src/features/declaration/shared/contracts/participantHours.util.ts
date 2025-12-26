import type { ParticipantRowModel } from "../components/ParticipantsTable.vue";

export type NormalizedParticipant = {
  kind: "internal" | "external";
  lecturer_id?: number; // chỉ internal
  member_role_id: number;
};

export type ParticipantStats = {
  total_authors: number; // internal + external
  internal_authors: number;
  external_authors: number;
  all: NormalizedParticipant[];
  internal: Array<
    NormalizedParticipant & { kind: "internal"; lecturer_id: number }
  >;
};

function isFiniteNumber(v: unknown): v is number {
  return typeof v === "number" && Number.isFinite(v);
}

function hasText(v: unknown): v is string {
  return typeof v === "string" && v.trim().length > 0;
}

export function normalizeParticipantsForHours(
  members: ParticipantRowModel[]
): ParticipantStats {
  const all: NormalizedParticipant[] = [];
  const internal: Array<
    NormalizedParticipant & { kind: "internal"; lecturer_id: number }
  > = [];

  for (const row of members) {
    // ✅ narrow đúng kiểu TS
    const memberRoleId = row.member_role_id;
    if (!isFiniteNumber(memberRoleId)) continue;

    const isExternal = row.is_external === true;

    if (!isExternal) {
      const lecturerId = row.lecturer_id;
      if (!isFiniteNumber(lecturerId)) continue;

      const p: NormalizedParticipant & {
        kind: "internal";
        lecturer_id: number;
      } = {
        kind: "internal",
        lecturer_id: lecturerId,
        member_role_id: memberRoleId,
      };

      all.push(p);
      internal.push(p);
      continue;
    }

    // external hợp lệ khi có tên
    if (!hasText(row.external_full_name)) continue;

    all.push({
      kind: "external",
      member_role_id: memberRoleId,
    });
  }

  const total = all.length;
  const internalCount = internal.length;
  const externalCount = total - internalCount;

  return {
    total_authors: total,
    internal_authors: internalCount,
    external_authors: externalCount,
    all,
    internal,
  };
}

export function round2(v: number) {
  return Math.round(v * 100) / 100;
}
