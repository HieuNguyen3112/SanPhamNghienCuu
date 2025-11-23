// src/app/router/index.ts
import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";

// Layout chính
import MainLayout from "@/layouts/MainLayout.vue";

// Auth feature
import LoginPage from "@/features/auth/pages/LoginPage.vue";

// Feature pages

// Routes của module profile
import { profileRoutes } from "@/features/profile/routes";

// =======================
// Children bên trong MainLayout
// =======================
const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginPage,
  },
  {
    path: "/",
    component: MainLayout,
    children: [...profileRoutes],
  },
  { path: "/:pathMatch(.*)*", redirect: "/login" },
];
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

export default router;
