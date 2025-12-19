// src/features/profile/routes.ts
import type { RouteRecordRaw } from "vue-router";

import LecturerStatisticsPage from "@/features/reports/lecturer/pages/LecturerStatisticsPage.vue";
import ResearchReportPage from "@/features/reports/research/page/ResearchReportPage.vue";
import LecturerResearchHourStatisticsPage from "@/features/reports/hour_research/pages/LecturerResearchHourStatisticsPage.vue";
export const reportRoutes: RouteRecordRaw[] = [
  {
    path: "report/lecturer",
    name: "report.lecturer",
    component: LecturerStatisticsPage,
  },
  {
    path: "report/hour-research",
    name: "report.hour-research",
    component: LecturerResearchHourStatisticsPage,
  },
  {
    path: "report/research",
    name: "report.research",
    component: ResearchReportPage,
  },
];
