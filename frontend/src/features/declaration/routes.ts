// src/features/declarations/routes.ts
import type { RouteRecordRaw } from "vue-router";

import ParticipationNotificationListPage from "@/features/declaration/participation-notifications/pages/ParticipationNotificationListPage.vue";
import CommonResearchDeclarationEntryPage from "@/features/declaration/gateway/pages/ResearchDeclarationGatewayPage.vue";

import ArticleDeclarationPage from "@/features/declaration/article/pages/ArticleDeclarationPage.vue";
import BookDeclarationPage from "@/features/declaration/book/pages/BookDeclarationPage.vue";
import ProjectDeclarationPage from "@/features/declaration/project/pages/ProjectDeclarationPage.vue";
import ConferenceDeclarationPage from "@/features/declaration/conference/pages/ConferenceDeclarationPage.vue";

export const declarationRoutes: RouteRecordRaw[] = [
  {
    path: "declarations/articles",
    name: "declarations.articles",
    component: ArticleDeclarationPage,
    meta: { roles: ["LECTURER"] },
  },

  {
    path: "declarations/projects",
    name: "declarations.projects",
    component: ProjectDeclarationPage,
    meta: { roles: ["LECTURER"] },
  },

  {
    path: "declarations/books",
    name: "declarations.books",
    component: BookDeclarationPage,
    meta: { roles: ["LECTURER"] },
  },

  {
    path: "declarations/others",
    name: "declarations.others",
    component: ConferenceDeclarationPage,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "declarations/participatier",
    name: "declarations.participatier",
    component: ParticipationNotificationListPage,
    meta: { roles: ["LECTURER"] },
  },
  {
    path: "declarations/gateway",
    name: "declarations.gateway",
    component: CommonResearchDeclarationEntryPage,
    meta: { roles: ["LECTURER"] },
  },
];
