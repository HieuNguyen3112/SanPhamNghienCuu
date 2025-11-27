// src/features/hours/menu.ts
import type { MenuItem } from "@/app/config/menu.types";

export const hoursMenuGroup: MenuItem = {
  id: "grp-hours",
  label: "Giờ NCKH",
};

export const hoursMenuItems: MenuItem[] = [
  {
    id: "hours-my",
    label: "Giờ NCKH của tôi",
    routeName: "hours.my",
    roles: ["LECTURER"],
  },
  {
    id: "hours-faculty",
    label: "Giờ NCKH giảng viên trong khoa",
    routeName: "hours.faculty",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
];
