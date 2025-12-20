import type { MenuItem } from "@/app/config/menu.types";
import type { Component } from "vue";

import {
  Microscope,
  User,
  LayoutDashboard,
  ClipboardCheck,
  ShieldCheck,
  AlertTriangle,
  BarChart3,
} from "lucide-vue-next";

// Nếu MenuItem CHƯA có field icon thì bạn thêm:
// icon?: Component

export const researchWorksMenuGroup: MenuItem & { icon?: Component } = {
  id: "research-work-publication",
  label: "Quản lý khoa học",
  icon: Microscope,
  roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
};

export const researchWorksMenuItems: (MenuItem & { icon?: Component })[] = [
  // {
  //   id: "works-my-declarations",
  //   label: "Công trình của tôi",
  //   routeName: "works-my-declarations",
  //   icon: User,
  //   roles: ["LECTURER"],
  // },
  {
    id: "works-personal",
    label: "Công trình của tôi",
    routeName: "works.personal",
    icon: User,
    roles: ["LECTURER"],
  },
  {
    id: "works-facmanagement",
    label: "Quản lý công trình NCKH",
    routeName: "works.facmanagement",
    icon: LayoutDashboard,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "works-unimanagement",
    label: "Quản lý công trình NCKH",
    routeName: "works.unimanagement",
    icon: LayoutDashboard,
    roles: ["SCIENCE_OFFICE"],
  },
  {
    id: "works-facapproval",
    label: "Xét duyệt công trình",
    routeName: "works.facapprovals",
    icon: ClipboardCheck,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "works-uniapproval",
    label: "Xét duyệt công trình",
    routeName: "works.uniapprovals",
    icon: ShieldCheck,
    roles: ["SCIENCE_OFFICE"],
  },
  {
    id: "hours-warning",
    label: "Cảnh báo giảng viên",
    routeName: "hours.warning",
    icon: AlertTriangle,
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
  {
    id: "hours-facmanagement",
    label: "Quản lý giờ NCKH ",
    routeName: "hours.facmanagement",
    icon: BarChart3,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "hours-unimanagement",
    label: "Quản lý giờ NCKH",
    routeName: "hours.unimanagement",
    icon: BarChart3,
    roles: ["SCIENCE_OFFICE"],
  },
  {
    id: "hours.facapprovals",
    label: "Xét duyệt giờ NCKH",
    routeName: "hours.facapprovals",
    icon: ClipboardCheck,
    roles: ["DEPARTMENT_BOARD"],
  },
  {
    id: "hours.uniapprovals",
    label: "Xét duyệt giờ NCKH",
    routeName: "hours.uniapprovals",
    icon: ClipboardCheck,
    roles: ["SCIENCE_OFFICE"],
  },
];
