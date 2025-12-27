import type { MenuItem } from "@/app/config/menu.types";

export const MasterDataMenuGroup: MenuItem = {
  id: "user-manager",
  label: "Quản lý hệ thống ",
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
  {
    id: "auditlog-facmanagement",
    label: "Nhật ký hệ thống ",
    routeName: "auditlog.facmanagement",
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "auditlog-unimanagement",
    label: "Nhật ký hệ thống",
    routeName: "auditlog.unimanagement",
    roles: ["SCIENCE_OFFICE"],
  },
];
