// src/features/profile/routes.ts
import type { RouteRecordRaw } from "vue-router";

import ProfileScientificView from "@/features/profile/pages/ProfileScientificView.vue";
import ProfileContactView from "../../features/profile/pages/ProfileContactView.vue";
import ProfileWorkHistoryView from "../../features/profile/pages/ProfileWorkHistoryView.vue";
import ProfileEducationView from "../../features/profile/pages/ProfileEducationView.vue";
import ProfileAcademicRankView from "@/features/profile/pages/ProfileAcademicRankView.vue";
import ProfileLanguageSkillsView from "@/features/profile/pages/ProfileLanguageSkillsView.vue";
import ProfileResearchAreaView from "@/features/profile/pages/ProfileResearchAreaView.vue";
export const profileRoutes: RouteRecordRaw[] = [
  {
    path: "profile",
    name: "profile.scientific",
    component: ProfileScientificView,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "profile/contact",
    name: "profile.contact",
    component: ProfileContactView,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "profile/work-history",
    name: "profile.workHistory",
    component: ProfileWorkHistoryView,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "profile/education",
    name: "profile.education",
    component: ProfileEducationView,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },

  {
    path: "profile/academic-rank",
    name: "profile.academicRank",
    component: ProfileAcademicRankView,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "profile/languages",
    name: "profile.languages",
    component: ProfileLanguageSkillsView,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "profile/research",
    name: "profile.research",
    component: ProfileResearchAreaView,
    meta: { roles: ["LECTURER", "DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
];
