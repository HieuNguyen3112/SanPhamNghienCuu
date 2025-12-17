import type { MenuItem } from "@/app/config/menu.types";
export const researchWorksMenuGroup: MenuItem = {
  id: "research-work-publication",
  label: "Quản lý khoa học ",
  roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
};
export const researchWorksMenuItems: MenuItem[] = [
  {
    id: "works-my-declarations",
    label: "Công trình của tôi",
    routeName: "works-my-declarations",
    roles: ["LECTURER"],
  },
  {
    id: "works-pverview",
    label: "Quản lý công trình NCKH  ",
    routeName: "work.overview",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
  {
    id: "works-approvals",
    label: "Xẻt duyệt công trình ",
    routeName: "works.approvals",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
];
