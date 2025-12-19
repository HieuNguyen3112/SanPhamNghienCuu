import LecturerDeclaredResearchWorkPage from "@/features/scientific/research-works/lecturer/pages/LecturerDeclaredResearchWorkPage.vue";
import LecturerResearchWorkOverviewPage from "@/features/scientific/research-works/overview/pages/LecturerResearchWorkOverviewPage.vue";
import LecturerResearchHourWarningPage from "@/features/scientific/hourswarning/pages/LecturerResearchHourWarningPage.vue";

import FacultyResearchWorkApprovalPage from "@/features/scientific/researchWorkApproval/faculty/pages/FacultyResearchWorkApprovalPage.vue";
import UniversityResearchWorkApprovalPage from "@/features/scientific/researchWorkApproval/university/pages/UniversityResearchWorkApprovalPage.vue";
import type { RouteRecordRaw } from "vue-router";
export const researchWorksRoutes: RouteRecordRaw[] = [
  {
    path: "works/my",
    name: "works-my-declarations",
    component: LecturerDeclaredResearchWorkPage,
  },
  {
    path: "works/facapprovals",
    name: "works.facapprovals",
    component: FacultyResearchWorkApprovalPage,
  },
  {
    path: "works/uniapprovals",
    name: "works.uniapprovals",
    component: UniversityResearchWorkApprovalPage,
  },
  // {
  //   path: "works/approvals",
  //   name: "works.approvals",
  //   component: ResearchWorkApprovalPage,
  // },
  {
    path: "work/overview",
    name: "work.overview",
    component: LecturerResearchWorkOverviewPage,
  },
  {
    path: "hours/warning",
    name: "hours.warning",
    component: LecturerResearchHourWarningPage,
  },
];
