import type { MenuItem } from "@/app/config/menu.types";
import type { Component } from "vue";

import {
  Microscope,
  User,
  LayoutDashboard,
  ClipboardCheck,
  AlertTriangle,
  BarChart3,
  Calculator,
  Clock,
  Bell,
} from "lucide-vue-next";

// Nếu MenuItem chưa có field icon thì bạn thêm:
// icon?: Component

export const researchWorksMenuGroup: MenuItem & { icon?: Component } = {
  id: "research-work-publication",
  label: "Quản lý khoa học",
  icon: Microscope,
  roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
};

export const researchWorksMenuItems: (MenuItem & { icon?: Component })[] = [
  // =========================
  // LECTURER
  // =========================
  {
    id: "works-personal",
    label: "Công trình của tôi",
    routeName: "works.personal",
    icon: User,
    roles: ["LECTURER"],
  },
  {
    id: "hours-calculate",
    label: "Tính giờ NCKH cá nhân",
    routeName: "hours.calculate",
    icon: Calculator,
    roles: ["LECTURER"],
  },
  {
    id: "hours-personal",
    label: "Giờ NCKH cá nhân",
    routeName: "hours.personal",
    icon: Clock,
    roles: ["LECTURER"],
  },
  {
    id: "hours-personal-warnings",
    label: "Thông báo cảnh báo",
    routeName: "hours.personal_warnings",
    icon: Bell,
    roles: ["LECTURER"],
  },

  // =========================
  // DEPARTMENT_BOARD (BCN KHOA)
  // =========================
  {
    id: "works-facmanagement",
    label: "Quản lý công trình NCKH",
    routeName: "works.facmanagement",
    icon: LayoutDashboard,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "works-facapproval",
    label: "Xét duyệt công trình",
    routeName: "works.facapprovals",
    icon: ClipboardCheck,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "hours-facwarning",
    label: "Cảnh báo giảng viên",
    routeName: "hours.facwarning",
    icon: AlertTriangle,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "hours-facmanagement",
    label: "Quản lý giờ NCKH",
    routeName: "hours.facmanagement",
    icon: BarChart3,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "hours.facapprovals",
    label: "Xét duyệt giờ NCKH",
    routeName: "hours.facapprovals",
    icon: ClipboardCheck,
    roles: ["DEPARTMENT_BOARD"],
  },

  // =========================
  // SCIENCE_OFFICE (QLKH)
  // =========================
  {
    id: "works-unimanagement",
    label: "Quản lý công trình NCKH",
    routeName: "works.unimanagement",
    icon: LayoutDashboard,
    roles: ["SCIENCE_OFFICE"],
  },
  {
    id: "hours-unimanagement",
    label: "Quản lý giờ NCKH",
    routeName: "hours.unimanagement",
    icon: BarChart3,
    roles: ["SCIENCE_OFFICE"],
  },
];
