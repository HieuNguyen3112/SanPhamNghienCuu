// src/features/declarations/menu.ts
import type { MenuItem } from "@/app/config/menu.types";

export const declarationsMenuGroup: MenuItem = {
  id: "grp-declarations",
  label: "Kê khai khoa học",
  roles: ["LECTURER"],
};

export const declarationMenuItems: MenuItem[] = [
  {
    id: "declarations-articles",
    label: "Bài báo khoa học",
    routeName: "declarations.articles",
    roles: ["LECTURER"],
  },
  {
    id: "declarations-projects",
    label: "Đề tài NCKH",
    routeName: "declarations.projects",
    roles: ["LECTURER"],
  },
  {
    id: "declarations-books",
    label: "Sách / giáo trình",
    routeName: "declarations.books",
    roles: ["LECTURER"],
  },
  {
    id: "declarations-others",
    label: "Công trình khác",
    routeName: "declarations.others",
    roles: ["LECTURER"],
  },
  {
    id: "declarations-participatier",
    label: "Thông báo xác nhận",
    routeName: "declarations.participatier",
    roles: ["LECTURER"],
  },
];
