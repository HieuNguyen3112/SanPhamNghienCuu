// src/features/hours/menu.ts
import type { MenuItem } from "@/app/config/menu.types";
import type { Component } from "vue";

import {
  Clock,
  Calculator,
  Bell,
  BarChart3,
  ClipboardCheck,
} from "lucide-vue-next";

// Nếu MenuItem chưa có icon thì bạn có thể tạm dùng intersection như dưới
type MenuItemWithIcon = MenuItem & { icon?: Component };

export const hoursMenuGroup: MenuItemWithIcon = {
  id: "grp-hours",
  label: "Kê khai giờ khoa học",
  icon: Clock,
  roles: ["LECTURER"],
};

export const getHoursFacultyLabel = (role: string) => {
  if (role === "DEPARTMENT_BOARD") return "Giờ NCKH của khoa";
  if (role === "SCIENCE_OFFICE") return "Giờ NCKH toàn trường";
  return "Giờ NCKH";
};

export const hoursMenuItems: MenuItemWithIcon[] = [
  {
    id: "hours-my",
    label: "Giờ NCKH cá nhân",
    routeName: "hours.my",
    icon: Clock,
    roles: ["LECTURER"],
  },
  {
    id: "hours-calculate",
    label: "Tính giờ NCKH",
    routeName: "hours.calculate",
    icon: Calculator,
    roles: ["LECTURER"],
  },
  {
    id: "hours-warnings",
    label: "Thông báo NCKH",
    routeName: "hours.warnings",
    icon: Bell,
    roles: ["LECTURER"],
  },
  {
    id: "hours-summary-department",
    label: "Tổng hợp giờ NCKH",
    routeName: "hours.summary",
    icon: BarChart3,
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
  {
    id: "hours-approvals",
    label: "Xét duyệt giờ NCKH",
    routeName: "hours.approvals",
    icon: ClipboardCheck,
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
];
