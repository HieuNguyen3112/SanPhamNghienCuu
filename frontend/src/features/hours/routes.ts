// src/features/hours/routes.ts
import type { RouteRecordRaw } from "vue-router";

import HoursMySummaryView from "@/features/hours/pages/HoursMySummaryView.vue";
import HoursFacultySummaryView from "@/features/hours/pages/HoursFacultySummaryView.vue";

export const hoursRoutes: RouteRecordRaw[] = [
  {
    path: "hours/my",
    name: "hours.my", // nhớ thêm vào AppRouteName
    component: HoursMySummaryView,
  },
  {
    path: "hours/faculty",
    name: "hours.faculty", // nhớ thêm vào AppRouteName
    component: HoursFacultySummaryView,
  },
];
