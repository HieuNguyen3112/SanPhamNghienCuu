// src/features/profile/menu.ts
import type { MenuItem } from "@/app/config/menu.types";
import { UserCheck, FileText, FileClock } from "lucide-vue-next";

export const reportMenuGroup: MenuItem = {
  id: "grp-report",
  label: "Thống kê và báo cáo ",
  roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
};

export const reportMenuItems: MenuItem[] = [
  {
    id: "report-lecturer",
    label: "Thống kê nhân sự ",
    routeName: "report.lecturer",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
    icon: UserCheck,
  },
  {
    id: "report-hr",
    label: "Thống kê công trình NCKH ",
    routeName: "report.research",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
    icon: FileText,
  },
  {
    id: "report-hour-research",
    label: "Thống kê giờ NCKH ",
    routeName: "report.hour-research",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
    icon: FileClock,
  },
];
