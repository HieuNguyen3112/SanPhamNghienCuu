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
  },
  {
    path: "profile/contact",
    name: "profile.contact",
    component: ProfileContactView,
  },
  {
    path: "profile/work-history",
    name: "profile.workHistory",
    component: ProfileWorkHistoryView,
  },
  {
    path: "profile/education",
    name: "profile.education",
    component: ProfileEducationView,
  },

  {
    path: "profile/academic-rank",
    name: "profile.academicRank",
    component: ProfileAcademicRankView,
  },
  {
    path: "profile/languages",
    name: "profile.languages",
    component: ProfileLanguageSkillsView,
  },
  {
    path: "profile/research",
    name: "profile.research",
    component: ProfileResearchAreaView,
  },
];
