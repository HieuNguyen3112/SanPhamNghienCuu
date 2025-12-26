import type {
  AssignRolesPayload,
  LecturerAccountDTO,
  LecturerAccountFilterState,
  ToggleAccountStatusPayload,
  UnitOptionDTO,
  UpdateLecturerAccountPayload,
} from "../contracts/lecturerAccountManagement.contract";
import { normalizeKeyword } from "../contracts/lecturerAccountManagement.contract";
import {
  lecturerAccountsMockDTO,
  resolveUnitName,
  unitOptionsMockDTO,
} from "../mock-data/lecturerAccounts.mock";

let inMemoryAccounts: LecturerAccountDTO[] = [...lecturerAccountsMockDTO];

function sleep(ms: number) {
  return new Promise<void>((resolve) => window.setTimeout(resolve, ms));
}

function includesAnyRole(
  accountRoles: readonly string[],
  requiredRoles: readonly string[]
) {
  if (requiredRoles.length === 0) return true;
  return requiredRoles.some((r) => accountRoles.includes(r));
}

export async function getUnitOptionsDTO(): Promise<UnitOptionDTO[]> {
  await sleep(250);
  return [...unitOptionsMockDTO];
}

export async function searchLecturerAccountsDTO(
  filter: LecturerAccountFilterState
): Promise<LecturerAccountDTO[]> {
  await sleep(450);

  const keyword = normalizeKeyword(filter.keyword);

  const rows = inMemoryAccounts.filter((a) => {
    if (filter.status === "active" && a.status !== "ACTIVE") return false;
    if (filter.status === "inactive" && a.status !== "INACTIVE") return false;

    if (filter.unitId != null && a.unit_id !== filter.unitId) return false;

    if (!includesAnyRole(a.role_keys, filter.roleKeys)) return false;

    if (!keyword) return true;

    const haystack =
      `${a.full_name} ${a.email} ${a.lecturer_code} ${a.username}`.toLowerCase();
    return haystack.includes(keyword);
  });

  // default sort: name asc, then code
  rows.sort((x, y) => {
    const n = x.full_name.localeCompare(y.full_name, "vi");
    if (n !== 0) return n;
    return x.lecturer_code.localeCompare(y.lecturer_code);
  });

  return rows;
}

export async function updateLecturerAccountDTO(
  payload: UpdateLecturerAccountPayload
): Promise<LecturerAccountDTO> {
  await sleep(500);

  const index = inMemoryAccounts.findIndex((a) => a.id === payload.id);
  if (index < 0) throw new Error("Không tìm thấy tài khoản.");

  const current = inMemoryAccounts[index]!;
  const next: LecturerAccountDTO = {
    ...current,
    full_name: payload.full_name,
    email: payload.email,
    unit_id: payload.unit_id,
    unit_name: resolveUnitName(payload.unit_id),
    position_title: payload.position_title,
    updated_at: new Date().toISOString(),
    updated_by: "mock-admin",
  };

  inMemoryAccounts[index] = next;
  return next;
}

export async function assignRolesDTO(
  payload: AssignRolesPayload
): Promise<LecturerAccountDTO> {
  await sleep(450);

  const index = inMemoryAccounts.findIndex((a) => a.id === payload.id);
  if (index < 0) throw new Error("Không tìm thấy tài khoản.");

  const current = inMemoryAccounts[index]!;
  const next: LecturerAccountDTO = {
    ...current,
    role_keys: [...payload.role_keys],
    updated_at: new Date().toISOString(),
    updated_by: "mock-admin",
  };

  inMemoryAccounts[index] = next;
  return next;
}

export async function toggleAccountStatusDTO(
  payload: ToggleAccountStatusPayload
): Promise<LecturerAccountDTO> {
  await sleep(450);

  const index = inMemoryAccounts.findIndex((a) => a.id === payload.id);
  if (index < 0) throw new Error("Không tìm thấy tài khoản.");

  const current = inMemoryAccounts[index]!;
  const next: LecturerAccountDTO = {
    ...current,
    status: payload.next_status,
    updated_at: new Date().toISOString(),
    updated_by: "mock-admin",
  };

  inMemoryAccounts[index] = next;
  return next;
}
