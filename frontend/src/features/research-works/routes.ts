import MyWorksView from "@/features/research-works/pages/lecturer/MyWorksView.vue";
import WorksApprovalsView from "@/features/research-works/pages/management/WorkApprovalView.vue";
import WorkOverviewView from "@/features/research-works/pages/management/WorkOverviewView.vue";

import type { RouteRecordRaw } from "vue-router";
export const researchWorksRoutes: RouteRecordRaw[] = [
  {
    path: "works/my",
    name: "works-my-declarations",
    component: MyWorksView,
  },
  {
    path: "works/approvals",
    name: "works.approvals",
    component: WorksApprovalsView,
  },
  {
    path: "work/overview",
    name: "work.overview",
    component: WorkOverviewView,
  },
];
