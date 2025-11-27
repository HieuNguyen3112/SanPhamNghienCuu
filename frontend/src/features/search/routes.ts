// src/features/search/routes.ts
import type { RouteRecordRaw } from "vue-router";

import GlobalSearchView from "@/features/search/pages/GlobalSearchView.vue";

export const searchRoutes: RouteRecordRaw[] = [
  {
    path: "search",
    name: "search.global", // nhớ thêm vào AppRouteName
    component: GlobalSearchView,
  },
];
