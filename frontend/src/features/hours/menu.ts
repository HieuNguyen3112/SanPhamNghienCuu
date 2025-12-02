// src/features/hours/menu.ts
import type { MenuItem } from "@/app/config/menu.types";

export const hoursMenuGroup: MenuItem = {
  id: "grp-hours",
  label: "Quản lý giờ khoa học ",
};
export const getHoursFacultyLabel = (role: string) => {
  if (role === "DEPARTMENT_BOARD") return "Giờ NCKH của khoa";
  if (role === "SCIENCE_OFFICE") return "Giờ NCKH toàn trường";
  return "Giờ NCKH";
};
export const hoursMenuItems: MenuItem[] = [
  {
    id: "hours-my",
    label: "Theo dõi giờ NCKH cá nhân ",
    routeName: "hours.my",
    roles: ["LECTURER"],
  },
  {
    id: "hours-summary",
    label: "Theo dõi giờ NCKH khoa ",
    routeName: "hours.summary",
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "hours-summary",
    label: "Theo dõi giờ NCKH trường ",
    routeName: "hours.summary",
    roles: ["SCIENCE_OFFICE"],
  },
  {
    id: "hours-calculate",
    label: "Tính giờ NCKH ",
    routeName: "hours.calculate",
    roles: ["LECTURER"],
  },
  {
    id: "hours-warnings",
    label: "Thông báo  ",
    routeName: "hours.warnings",
    roles: ["LECTURER"],
  },
  {
    id: "hours-approvals ",
    label: "Duyệt giờ   ",
    routeName: "hours.approvals",
    roles: ["SCIENCE_OFFICE"],
  },
  // {
  //   id: "hours-approvals ",
  //   label: "Duyệt giờ   ",
  //   routeName: "hours.approvals",
  //   roles: ["SCIENCE_OFFICE"],
  // },
];
