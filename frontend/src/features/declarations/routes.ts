// src/features/declarations/routes.ts
import type { RouteRecordRaw } from "vue-router";

import DeclarationArticlesView from "@/features/declarations/pages/DeclarationArticlesView.vue";
import DeclarationProjectsView from "@/features/declarations/pages/DeclarationProjectsView.vue";
import DeclarationBooksView from "@/features/declarations/pages/DeclarationBooksView.vue";
import DeclarationOthersView from "@/features/declarations/pages/DeclarationOthersView.vue";
import MyWorksView from "@/features/works/pages/MyWorksView.vue";
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
    path: "works/my",
    name: "works-my-declarations", // trùng với AppRouteName
    component: MyWorksView,
  },
];
