// src/features/declarations/menu.ts
import type { MenuItem } from "@/app/config/menu.types";
import { FilePlus, BellDot } from "lucide-vue-next";
export const declarationsMenuGroup: MenuItem = {
  id: "grp-declarations",
  label: "Kê khai khoa học",
  roles: ["LECTURER"],
};

export const declarationMenuItems: MenuItem[] = [
  // {
  //   id: "declaration-articles",
  //   label: "Bài báo khoa học",
  //   routeName: "declarations.articles",
  // },
  {
    id: "declarations-gateway",
    label: "Kê khai công trình",
    routeName: "declarations.gateway",
    roles: ["LECTURER"],
    icon: FilePlus,
  },
  // {
  //   id: "declaration-projects",
  //   label: "Đề tài NCKH",
  //   routeName: "declarations.projects",
  //   roles: ["LECTURER"],
  // },

  // {
  //   id: "declaration-books",
  //   label: "Sách / giáo trình",
  //   routeName: "declarations.books",
  //   roles: ["LECTURER"],
  // },

  // {
  //   id: "declaration-others",
  //   label: "Công trình khác",
  //   routeName: "declarations.others",
  //   roles: ["LECTURER"],
  // },
  {
    id: "declarations-participatier",
    label: "Thông báo xác nhận",
    routeName: "declarations.participatier",
    roles: ["LECTURER"],
    icon: BellDot,
  },
];
