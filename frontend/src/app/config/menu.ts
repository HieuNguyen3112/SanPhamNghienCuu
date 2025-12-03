// src/app/config/menu.ts
import type { UserRole } from "@/app/stores/userStore";
import type { MenuItem } from "@/app/config/menu.types";

import { profileMenuGroup, profileMenuItems } from "@/features/profile/menu";

import {
  declarationsMenuGroup,
  declarationMenuItems,
} from "@/features/declarations/menu";
import { hoursMenuGroup, hoursMenuItems } from "@/features/hours/menu";
import { researchWorksMenuItems } from "@/features/research-works/menu";

import { searchMenuGroup, searchMenuItems } from "@/features/search/menu";
// Ghép toàn bộ menu (chung cho mọi role)
const allMenuItems: MenuItem[] = [
  // quan ly thong tin ca nhan
  profileMenuGroup,
  ...profileMenuItems,
  // quan ly cong trinh khoa hoc
  declarationsMenuGroup,
  ...researchWorksMenuItems,
  ...declarationMenuItems,
  // quan ly gio khoa hoc

  hoursMenuGroup,
  ...hoursMenuItems,

  // tra cuu cong trinh
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
