import type { MenuItem } from "@/app/config/menu.types";
import type { Component } from "vue";

import {
  Microscope,
  User,
  LayoutDashboard,
  ClipboardCheck,
  ShieldCheck,
  AlertTriangle,
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
  {
    id: "works-my-declarations",
    label: "Công trình của tôi",
    routeName: "works-my-declarations",
    icon: User,
    roles: ["LECTURER"],
  },
  {
    id: "works-pverview",
    label: "Quản lý công trình NCKH",
    routeName: "work.overview",
    icon: LayoutDashboard,
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
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
];
