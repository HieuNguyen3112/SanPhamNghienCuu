import PersonalResearchWorksPage from "@/features/scientific/lecturer/personal-research-works/pages/PersonalResearchWorksPage.vue";
import LecturerSelectApprovedWorksForHoursPage from "@/features/scientific/lecturer/personal-hours-declare/pages/LecturerSelectApprovedWorksForHoursPage.vue";
import LecturerEvidenceMissingHoursPage from "@/features/scientific/lecturer/personal-hours-declare/pages/LecturerEvidenceMissingHoursPage.vue";
import LecturerHoursWarningPage from "@/features/scientific/lecturer/personal-hours-warning/pages/LecturerHoursWarningPage.vue";
import PersonalHoursOverviewPage from "@/features/scientific/lecturer/personal-hours-overview/pages/PersonalHoursOverviewPage.vue";

import FacultyLecturerResearchWorkManagementPage from "@/features/scientific/management/research-work-management/pages/FacultyLecturerResearchWorkManagementPage.vue";
import UniversityLecturerResearchWorkManagementPage from "@/features/scientific/management/research-work-management/pages/UniversityLecturerResearchWorkManagementPage.vue";
import FacultyResearchWorkApprovalPage from "@/features/scientific/management/research-work-approval/faculty/pages/FacultyResearchWorkApprovalPage.vue";

import FacultyLecturerHoursManagementPage from "@/features/scientific/management/lecturer-hours-management/pages/FacultyLecturerHoursManagementPage.vue";
import UniversityLecturerHoursManagementPage from "@/features/scientific/management/lecturer-hours-management/pages/UniversityLecturerHoursManagementPage.vue";
import FacultyHourApprovalPage from "@/features/scientific/management/lecturer-hour-approval/pages/FacultyHourApprovalPage.vue";
import UniversityHourApprovalPage from "@/features/scientific/management/lecturer-hour-approval/pages/UniversityHourApprovalPage.vue";

import FacultyResearchHourWarningPage from "@/features/scientific/management/lecturer-hours-warnings/pages/FacultyResearchHourWarningPage.vue";
import UniversityResearchHourWarningPage from "@/features/scientific/management/lecturer-hours-warnings/pages/UniversityResearchHourWarningPage.vue";
import type { RouteRecordRaw } from "vue-router";

export const researchWorksRoutes: RouteRecordRaw[] = [
  // 1) GIẢNG VIÊN (Personal)
  {
    path: "works/personal",
    name: "works.personal",
    component: PersonalResearchWorksPage,
    meta: { roles: ["LECTURER"] },
  },

  {
    path: "hours/personal",
    name: "hours.personal",
    component: PersonalHoursOverviewPage,
    meta: { roles: ["LECTURER"] },
  },

  {
    path: "hours/personal_warnings",
    name: "hours.personal_warnings",
    component: LecturerHoursWarningPage,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "hours/calculate",
    name: "hours.calculate",
    component: LecturerSelectApprovedWorksForHoursPage,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "hours/calculate/evidence-missing",
    name: "hours.calculate.evidence_missing",
    component: LecturerEvidenceMissingHoursPage,
    meta: { roles: ["LECTURER"] },
  },

  // 2) BCN KHOA (Faculty)
  {
    path: "works/facapprovals",
    name: "works.facapprovals",
    component: FacultyResearchWorkApprovalPage,
    meta: { roles: ["DEPARTMENT_BOARD"] },
  },
  {
    path: "works/facmanagement",
    name: "works.facmanagement",
    component: FacultyLecturerResearchWorkManagementPage,
    meta: { roles: ["DEPARTMENT_BOARD"] },
  },

  {
    path: "hours/facmanagement",
    name: "hours.facmanagement",
    component: FacultyLecturerHoursManagementPage,
    meta: { roles: ["DEPARTMENT_BOARD"] },
  },
  {
    path: "hours/facapprovals",
    name: "hours.facapprovals",
    component: FacultyHourApprovalPage,
    meta: { roles: ["DEPARTMENT_BOARD"] },
  },
  {
    path: "hours/facwarning",
    name: "hours.facwarning",
    component: FacultyResearchHourWarningPage,
    meta: { roles: ["DEPARTMENT_BOARD"] },
  },

  // 3) QLKH TOÀN TRƯỜNG (University)
  {
    path: "works/unimanagement",
    name: "works.unimanagement",
    component: UniversityLecturerResearchWorkManagementPage,
    meta: { roles: ["SCIENCE_OFFICE"] },
  },

  {
    path: "hours/unimanagement",
    name: "hours.unimanagement",
    component: UniversityLecturerHoursManagementPage,
    meta: { roles: ["SCIENCE_OFFICE"] },
  },
  {
    path: "hours/uniapprovals",
    name: "hours.uniapprovals",
    component: UniversityHourApprovalPage,
    meta: { roles: ["SCIENCE_OFFICE"] },
  },
  {
    path: "hours/uniwarning",
    name: "hours.uniwarning",
    component: UniversityResearchHourWarningPage,
    meta: { roles: ["SCIENCE_OFFICE"] },
  },
];
