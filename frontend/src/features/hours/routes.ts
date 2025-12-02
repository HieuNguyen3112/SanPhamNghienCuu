// src/features/hours/routes.ts
import type { RouteRecordRaw } from "vue-router";

import HoursApprovalsView from "@/features/hours/pages/management/HoursApprovalsView.vue";
import HoursSummaryView from "@/features/hours/pages/management/HoursSummaryView.vue";

import HoursMySummaryView from "@/features/hours/pages/lecturer/HoursMySummaryView.vue";
import HoursMyWarningsView from "@/features/hours/pages/lecturer/HoursMyWarningsView.vue";
import HoursCalculateView from "@/features/hours/pages/lecturer/HoursCalculateView.vue";

import HoursBatchDetailView from "@/features/hours/components/HoursBatchDetailView.vue";
export const hoursRoutes: RouteRecordRaw[] = [
  {
    path: "hours/my",
    name: "hours.my", // nhớ thêm vào AppRouteName
    component: HoursMySummaryView,
  },
  {
    path: "hours/summary",
    name: "hours.summary", // nhớ thêm vào AppRouteName
    component: HoursSummaryView,
  },
  {
    path: "hours/warnings",
    name: "hours.warnings",
    component: HoursMyWarningsView,
  },
  {
    path: "hours/calculate",
    name: "hours.calculate",
    component: HoursCalculateView,
  },
  {
    path: "hours/approvals",
    name: "hours.approvals",
    component: HoursApprovalsView,
  },
  {
    path: "hours/batches/:batchId",
    name: "hours.batchDetail",
    component: HoursBatchDetailView,
  },
];
