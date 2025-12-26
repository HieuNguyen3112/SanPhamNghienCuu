import type { MenuItem } from "@/app/config/menu.types";
import { Users, Network, ScrollText, BookCopy, Timer } from "lucide-vue-next";

export const UserManagerMenuGroup: MenuItem = {
  id: "user-manager",
  label: "Quản lý hệ thống ",
  roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
};

export const UserManagerMenuItems: MenuItem[] = [
  {
    id: "users-lecturer-accounts",
    label: "Tài khoản giảng viên",
    routeName: "user.manager",
    icon: Users,
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
  {
    id: "users-org-structure",
    label: "Cơ cấu tổ chức",
    routeName: "userorg.structure",
    icon: Network,
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },

  // Audit log
  {
    id: "audit-logs-faculty",
    label: "Nhật ký hệ thống (Khoa)",
    routeName: "auditlog.facmanagement",
    icon: ScrollText,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "audit-logs-university",
    label: "Nhật ký hệ thống (Trường)",
    routeName: "auditlog.unimanagement",
    icon: ScrollText,
    roles: ["SCIENCE_OFFICE"],
  },

  // Master data
  {
    id: "masterdata-work-catalog",
    label: "Danh mục công trình",
    routeName: "masterdata.work_catalog",
    icon: BookCopy,
    roles: ["SCIENCE_OFFICE"],
  },
  {
    id: "masterdata-hours-catalog",
    label: "Danh mục giờ NCKH",
    routeName: "masterdata.hours_catalog",
    icon: Timer,
    roles: ["SCIENCE_OFFICE"],
  },
];
