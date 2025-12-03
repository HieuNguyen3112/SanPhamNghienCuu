// src/features/declarations/routes.ts
import type { RouteRecordRaw } from "vue-router";

import DeclarationArticlesView from "@/features/declarations/pages/lecturer/DeclarationArticlesView.vue";
import DeclarationProjectsView from "@/features/declarations/pages/lecturer/DeclarationProjectsView.vue";
import DeclarationBooksView from "@/features/declarations/pages/lecturer/DeclarationBooksView.vue";
import DeclarationOthersView from "@/features/declarations/pages/lecturer/DeclarationOthersView.vue";
import DeclarationParticipationConfirmView from "@/features/declarations/pages/lecturer/DeclarationParticipationConfirmView.vue";
import MyWorksView from "@/features/research-works/pages/lecturer/MyWorksView.vue";
import WorksApprovalsView from "@/features/research-works/pages/management/WorkApprovalView.vue";

export const declarationRoutes: RouteRecordRaw[] = [
  {
    path: "declarations/articles",
    name: "declarations.articles",
    component: DeclarationArticlesView,
  },
  {
    path: "declarations/projects",
    name: "declarations.projects",
    component: DeclarationProjectsView,
  },
  {
    path: "declarations/books",
    name: "declarations.books",
    component: DeclarationBooksView,
  },
  {
    path: "declarations/others",
    name: "declarations.others",
    component: DeclarationOthersView,
  },
  {
    path: "declarations/participatier",
    name: "declarations.participatier",
    component: DeclarationParticipationConfirmView,
  },
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
];
