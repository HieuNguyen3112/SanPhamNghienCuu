import PersonalResearchWorksPage from "@/features/scientific/personal-research-works/pages/PersonalResearchWorksPage.vue";
import LecturerSelectApprovedWorksForHoursPage from "@/features/scientific/personal-hours-declare/pages/LecturerSelectApprovedWorksForHoursPage.vue";
import LecturerHoursWarningPage from "@/features/scientific/personal-hours-warning/pages/LecturerHoursWarningPage.vue";
import PersonalHoursOverviewPage from "@/features/scientific/personal-hours-overview/pages/PersonalHoursOverviewPage.vue";

import FacultyLecturerResearchWorkManagementPage from "@/features/scientific/research-work-management/pages/FacultyLecturerResearchWorkManagementPage.vue";
import UniversityLecturerResearchWorkManagementPage from "@/features/scientific/research-work-management/pages/UniversityLecturerResearchWorkManagementPage.vue";
import FacultyResearchWorkApprovalPage from "@/features/scientific/research-work-approval/faculty/pages/FacultyResearchWorkApprovalPage.vue";
import UniversityResearchWorkApprovalPage from "@/features/scientific/research-work-approval/university/pages/UniversityResearchWorkApprovalPage.vue";

import FacultyLecturerHoursManagementPage from "@/features/scientific/lecturer-hours-management/pages/FacultyLecturerHoursManagementPage.vue";
import UniversityLecturerHoursManagementPage from "@/features/scientific/lecturer-hours-management/pages/UniversityLecturerHoursManagementPage.vue";
import FacultyHourApprovalPage from "@/features/scientific/lecturer-hour-approval/pages/FacultyHourApprovalPage.vue";
import UniversityHourApprovalPage from "@/features/scientific/lecturer-hour-approval/pages/UniversityHourApprovalPage.vue";

import FacultyResearchHourWarningPage from "@/features/scientific/hours-warnings/pages/FacultyResearchHourWarningPage.vue";
import UniversityResearchHourWarningPage from "@/features/scientific/hours-warnings/pages/UniversityResearchHourWarningPage.vue";
import type { RouteRecordRaw } from "vue-router";

export const researchWorksRoutes: RouteRecordRaw[] = [
  // 1) GIẢNG VIÊN (Personal)
  {
    path: "works/personal",
    name: "works.personal",
    component: PersonalResearchWorksPage,
  },

  {
    path: "hours/personal",
    name: "hours.personal",
    component: PersonalHoursOverviewPage,
  },

  {
    path: "hours/personal_warnings",
    name: "hours.personal_warnings",
    component: LecturerHoursWarningPage,
  },
  {
    path: "hours/calculate",
    name: "hours.calculate",
    component: LecturerSelectApprovedWorksForHoursPage,
  },

  // 2) BCN KHOA (Faculty)
  {
    path: "works/facapprovals",
    name: "works.facapprovals",
    component: FacultyResearchWorkApprovalPage,
  },
  {
    path: "works/facmanagement",
    name: "works.facmanagement",
    component: FacultyLecturerResearchWorkManagementPage,
  },

  {
    path: "hours/facmanagement",
    name: "hours.facmanagement",
    component: FacultyLecturerHoursManagementPage,
  },
  {
    path: "hours/facapprovals",
    name: "hours.facapprovals",
    component: FacultyHourApprovalPage,
  },
  {
    path: "hours/facwarning",
    name: "hours.facwarning",
    component: FacultyResearchHourWarningPage,
  },

  // 3) QLKH TOÀN TRƯỜNG (University)
  {
    path: "works/uniapprovals",
    name: "works.uniapprovals",
    component: UniversityResearchWorkApprovalPage,
  },
  {
    path: "works/unimanagement",
    name: "works.unimanagement",
    component: UniversityLecturerResearchWorkManagementPage,
  },

  {
    path: "hours/unimanagement",
    name: "hours.unimanagement",
    component: UniversityLecturerHoursManagementPage,
  },
  {
    path: "hours/uniapprovals",
    name: "hours.uniapprovals",
    component: UniversityHourApprovalPage,
  },
  {
    path: "hours/uniwarning",
    name: "hours.uniwarning",
    component: UniversityResearchHourWarningPage,
  },
];
