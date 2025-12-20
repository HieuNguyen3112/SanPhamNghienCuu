import PersonalResearchWorksPage from "@/features/scientific/personal-research-works/pages/PersonalResearchWorksPage.vue";

import LecturerResearchHourWarningPage from "@/features/scientific/hourswarning/pages/LecturerResearchHourWarningPage.vue";
import FacultyLecturerResearchWorkManagementPage from "@/features/scientific/research-work-management/pages/FacultyLecturerResearchWorkManagementPage.vue";
import UniversityLecturerResearchWorkManagementPage from "@/features/scientific/research-work-management/pages/UniversityLecturerResearchWorkManagementPage.vue";
import FacultyResearchWorkApprovalPage from "@/features/scientific/research-work-approval/faculty/pages/FacultyResearchWorkApprovalPage.vue";
import UniversityResearchWorkApprovalPage from "@/features/scientific/research-work-approval/university/pages/UniversityResearchWorkApprovalPage.vue";

import FacultyLecturerHoursManagementPage from "@/features/scientific/lecturer-hours-management/pages/FacultyLecturerHoursManagementPage.vue";
import UniversityLecturerHoursManagementPage from "@/features/scientific/lecturer-hours-management/pages/UniversityLecturerHoursManagementPage.vue";
import FacultyHourApprovalPage from "@/features/scientific/lecturer-hour-approval/pages/FacultyHourApprovalPage.vue";
import UniversityHourApprovalPage from "@/features/scientific/lecturer-hour-approval/pages/UniversityHourApprovalPage.vue";

import type { RouteRecordRaw } from "vue-router";
export const researchWorksRoutes: RouteRecordRaw[] = [
  // {
  //   path: "works/my",
  //   name: "works-my-declarations",
  //   component: LecturerDeclaredResearchWorkPage,
  // },
  {
    path: "works/personal",
    name: "works.personal",
    component: PersonalResearchWorksPage,
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
  {
    path: "works/facmanagement",
    name: "works.facmanagement",
    component: FacultyLecturerResearchWorkManagementPage,
  },
  {
    path: "works/unimanagement",
    name: "works.unimanagement",
    component: UniversityLecturerResearchWorkManagementPage,
  },

  {
    path: "hours/warning",
    name: "hours.warning",
    component: LecturerResearchHourWarningPage,
  },
  {
    path: "hours/facmanagement",
    name: "hours.facmanagement",
    component: FacultyLecturerHoursManagementPage,
  },
  {
    path: "hours/unimanagement",
    name: "hours.unimanagement",
    component: UniversityLecturerHoursManagementPage,
  },
  {
    path: "hours/facapprovals",
    name: "hours.facapprovals",
    component: FacultyHourApprovalPage,
  },
  {
    path: "hours/uniapprovals",
    name: "hours.uniapprovals",
    component: UniversityHourApprovalPage,
  },
];
