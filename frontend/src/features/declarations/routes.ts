// src/features/declarations/routes.ts
import type { RouteRecordRaw } from "vue-router";

import DeclarationArticlesView from "@/features/declarations/pages/lecturer/DeclarationArticlesView.vue";
import DeclarationProjectsView from "@/features/declarations/pages/lecturer/DeclarationProjectsView.vue";
import DeclarationBooksView from "@/features/declarations/pages/lecturer/DeclarationBooksView.vue";
import DeclarationOthersView from "@/features/declarations/pages/lecturer/DeclarationOthersView.vue";
import DeclarationParticipationConfirmView from "@/features/declarations/pages/lecturer/DeclarationParticipationConfirmView.vue";
import WorksApprovalsView from "@/features/scientific/research-works/approval/pages/ResearchWorkApprovalPage.vue";

export const declarationRoutes: RouteRecordRaw[] = [
  {
    path: "declarations/articles",
    name: "declarations.articles",
    component: DeclarationArticlesView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "declarations/projects",
    name: "declarations.projects",
    component: DeclarationProjectsView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "declarations/books",
    name: "declarations.books",
    component: DeclarationBooksView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "declarations/others",
    name: "declarations.others",
    component: DeclarationOthersView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "declarations/participatier",
    name: "declarations.participatier",
    component: DeclarationParticipationConfirmView,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "works/approvals",
    name: "works.approvals",
    component: WorksApprovalsView,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
];
