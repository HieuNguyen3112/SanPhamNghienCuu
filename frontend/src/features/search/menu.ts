// src/features/search/menu.ts
import type { MenuItem } from "@/app/config/menu.types";
import { Search } from "lucide-vue-next";
export const searchMenuGroup: MenuItem = {
  id: "grp-search",
  label: "Tra cứu",
};

export const searchMenuItems: MenuItem[] = [
  {
    id: "search-global",
    label: "Tra cứu công trình",
    routeName: "search.global", // trùng với name trong routes.ts
    roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
    icon: Search,
  },
];
