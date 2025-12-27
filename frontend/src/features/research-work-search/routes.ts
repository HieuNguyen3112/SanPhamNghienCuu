// src/features/search/routes.ts
import type { RouteRecordRaw } from "vue-router";

import GlobalResearchWorkSearchPage from "@/features/research-work-search/pages/GlobalResearchWorkSearchPage.vue";
export const searchRoutes: RouteRecordRaw[] = [
  {
    path: "search",
    name: "search.global", // nhắc thêm vào AppRouteName
    component: GlobalResearchWorkSearchPage,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
];
