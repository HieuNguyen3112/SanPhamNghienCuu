// src/features/hours/routes.ts
import type { RouteRecordRaw } from "vue-router";

import HoursApprovalsView from "@/features/scientific/hours/pages/management/HoursApprovalsView.vue";
import HoursSummaryView from "@/features/scientific/hours/pages/management/HoursSummaryView.vue";

import HoursMySummaryView from "@/features/scientific/hours/pages/lecturer/HoursMySummaryView.vue";
import HoursMyWarningsView from "@/features/scientific/hours/pages/lecturer/HoursMyWarningsView.vue";
import HoursCalculateView from "@/features/scientific/hours/pages/lecturer/HoursCalculateView.vue";

import HoursBatchDetailView from "@/features/scientific/hours/components/HoursBatchDetailView.vue";
export const hoursRoutes: RouteRecordRaw[] = [
  {
    path: "hours/my",
    name: "hours.my", // nhắc thêm vào AppRouteName
    component: HoursMySummaryView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "hours/summary",
    name: "hours.summary", // nhắc thêm vào AppRouteName
    component: HoursSummaryView,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "hours/warnings",
    name: "hours.warnings",
    component: HoursMyWarningsView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "hours/calculate",
    name: "hours.calculate",
    component: HoursCalculateView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "hours/approvals",
    name: "hours.approvals",
    component: HoursApprovalsView,
    meta: { roles: ["SCIENCE_OFFICE"] },
  },
  {
    path: "hours/batches/:batchId",
    name: "hours.batchDetail",
    component: HoursBatchDetailView,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
];
