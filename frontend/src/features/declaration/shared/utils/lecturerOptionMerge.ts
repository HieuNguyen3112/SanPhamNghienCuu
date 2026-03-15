import type { LecturerOptionDto } from "../contracts/declarationSharedContract";

export type DraftMemberLecturerMeta = {
  lecturer_id?: number | null;
  lecturer_code?: string | null;
  lecturer_full_name?: string | null;
  department_id?: number | null;
  department_name?: string | null;
  member_faculty_id?: number | null;
  faculty_name?: string | null;
};

export function mergeLecturerOptionsFromMembers(
  current: LecturerOptionDto[],
  members: DraftMemberLecturerMeta[] | undefined,
): LecturerOptionDto[] {
  const byId = new Map<number, LecturerOptionDto>(
    current.map((lecturer) => [lecturer.id, lecturer]),
  );

  for (const member of members ?? []) {
    if (typeof member.lecturer_id !== "number") continue;

    const code = String(member.lecturer_code ?? "").trim();
    const fullName = String(member.lecturer_full_name ?? "").trim();
    if (!code || !fullName) continue;

    const departmentId = Number(member.department_id ?? 0);
    byId.set(member.lecturer_id, {
      id: member.lecturer_id,
      code,
      full_name: fullName,
      department_id:
        Number.isFinite(departmentId) && departmentId > 0 ? departmentId : 0,
      department_name: member.department_name ?? undefined,
      faculty_id:
        typeof member.member_faculty_id === "number"
          ? member.member_faculty_id
          : null,
      faculty_name: member.faculty_name ?? null,
    });
  }

  return Array.from(byId.values());
}
