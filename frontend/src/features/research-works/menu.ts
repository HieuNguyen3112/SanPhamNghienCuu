import type { MenuItem } from "@/app/config/menu.types";
export const researchWorksMenuGroup: MenuItem = {
  id: "pu",
  label: "Công bố khoa học ",
};
export const researchWorksMenuItems: MenuItem[] = [
  {
    id: "works-my-declarations",
    label: "Công trình của tôi",
    routeName: "works-my-declarations",
    roles: ["LECTURER"],
  },
  {
    id: "works-approvals",
    label: "Duyệt công trình ",
    routeName: "works.approvals",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
  },
];
