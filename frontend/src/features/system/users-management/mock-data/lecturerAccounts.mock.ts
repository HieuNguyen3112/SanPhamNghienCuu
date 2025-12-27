import type {
  LecturerAccountDTO,
  RoleKey,
  UnitOptionDTO,
} from "../contracts/lecturerAccountManagement.contract";

export const unitOptionsMockDTO: UnitOptionDTO[] = [
  { id: 1, name: "Khoa Công nghệ Thông tin" },
  { id: 2, name: "Khoa Toán" },
  { id: 3, name: "Khoa Kinh tế" },
];

export function resolveUnitName(unitId: number): string {
  return (
    unitOptionsMockDTO.find((u) => u.id === unitId)?.name ?? `Unit#${unitId}`
  );
}

function isoAt(daysAgo: number, hh: number, mm: number) {
  const d = new Date();
  d.setDate(d.getDate() - daysAgo);
  d.setHours(hh, mm, 0, 0);
  return d.toISOString();
}

const role = (...keys: RoleKey[]) => keys;

export const lecturerAccountsMockDTO: LecturerAccountDTO[] = [
  {
    id: 101,
    lecturer_code: "gv001",
    full_name: "Nguyễn Văn A",
    email: "nva@uni.edu",
    username: "nva",
    unit_id: 1,
    unit_name: resolveUnitName(1),
    role_keys: role("LECTURER"),
    status: "ACTIVE",
    position_title: "Giảng viên",
    updated_at: isoAt(1, 9, 12),
    updated_by: "system",
  },
  {
    id: 102,
    lecturer_code: "gv002",
    full_name: "Trần Thị B",
    email: "ttb@uni.edu",
    username: "ttb",
    unit_id: 1,
    unit_name: resolveUnitName(1),
    role_keys: role("LECTURER", "DEPARTMENT_BOARD"),
    status: "ACTIVE",
    position_title: "Phó trưởng khoa",
    updated_at: isoAt(2, 14, 30),
    updated_by: "admin",
  },
  {
    id: 103,
    lecturer_code: "gv003",
    full_name: "Lê Văn C",
    email: "lvc@uni.edu",
    username: "lvc",
    unit_id: 2,
    unit_name: resolveUnitName(2),
    role_keys: role("LECTURER"),
    status: "INACTIVE",
    position_title: "Giảng viên",
    updated_at: isoAt(4, 11, 10),
    updated_by: "admin",
  },
  {
    id: 104,
    lecturer_code: "gv004",
    full_name: "Phạm Thị D",
    email: "ptd@uni.edu",
    username: "ptd",
    unit_id: 3,
    unit_name: resolveUnitName(3),
    role_keys: role("LECTURER"),
    status: "ACTIVE",
    position_title: "Giảng viên",
    updated_at: isoAt(3, 16, 5),
    updated_by: "system",
  },
  {
    id: 105,
    lecturer_code: "gv005",
    full_name: "Admin QLKH",
    email: "qlkh@uni.edu",
    username: "qlkh",
    unit_id: 1,
    unit_name: resolveUnitName(1),
    role_keys: role("SCIENCE_OFFICE"),
    status: "ACTIVE",
    position_title: "Phòng QLKH",
    updated_at: isoAt(0, 8, 45),
    updated_by: "system",
  },
];
