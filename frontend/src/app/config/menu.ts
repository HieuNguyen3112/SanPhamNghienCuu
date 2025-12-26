// src/app/config/menu.ts
import type { UserRole } from "@/app/stores/userStore";
import type { MenuItem } from "@/app/config/menu.types";

import { profileMenuGroup, profileMenuItems } from "@/features/profile/menu";

import {
  declarationsMenuGroup,
  declarationMenuItems,
} from "@/features/declaration/menu";

import {
  UserManagerMenuGroup,
  UserManagerMenuItems,
} from "@/features/users/menu";
import {
  researchWorksMenuGroup,
  researchWorksMenuItems,
} from "@/features/scientific/menu";

import {
  searchMenuGroup,
  searchMenuItems,
} from "@/features/research-work-search/menu";
import { reportMenuGroup, reportMenuItems } from "@/features/reports/menu";
// Ghép toàn bộ menu (chung cho mọi role)
const allMenuItems: MenuItem[] = [
  // quan ly thong tin ca nhan
  profileMenuGroup,
  ...profileMenuItems,
  // quan ly cong trinh khoa hoc
  declarationsMenuGroup,
  researchWorksMenuGroup,
  ...declarationMenuItems,
  ...researchWorksMenuItems,

  // quan ly gio khoa hoc

  UserManagerMenuGroup,
  ...UserManagerMenuItems,
  // tra cuu cong trinh
  reportMenuGroup,
  ...reportMenuItems,
  searchMenuGroup,
  ...searchMenuItems,
];

export function buildMenuForRole(role?: UserRole | null): MenuItem[] {
  if (!role) return allMenuItems;
  return allMenuItems.filter((item) => {
    if (!item.roles) return true; // không set roles => ai cũng thấy
    return item.roles.includes(role);
  });
}

// optional: nếu chỗ khác cần full menu
export { allMenuItems };
