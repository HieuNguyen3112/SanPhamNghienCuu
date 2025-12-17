import type { MenuItem } from "@/app/config/menu.types";

export const UserManagerMenuGroup: MenuItem = {
  id: "user-manager",
  label: "Quản lý người dùng",
  roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
};

export const UserManagerMenuItems: MenuItem[] = [
  {
    id: "user-list",
    label: "Người dùng",
    routeName: "user.manager",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
  {
    id: "user-structure",
    label: "Cơ cấu tổ chức",
    routeName: "userorg.structure",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
];
