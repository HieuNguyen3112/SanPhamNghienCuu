import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";
import { useUserStore } from "@/app/stores/userStore";

// Layouts
import MainLayout from "@/layouts/MainLayout.vue";
import LoginPage from "@/features/auth/pages/LoginPage.vue";

// Features
import { profileRoutes } from "@/features/profile/routes";
import { declarationRoutes } from "@/features/declaration/routes";
import { searchRoutes } from "@/features/research-work-search/routes";
import { researchWorksRoutes } from "@/features/scientific/routes";
import { UserManagerRoutes } from "@/features/system/routes";
import { reportRoutes } from "@/features/reports/routes";
// =====================
// CẤU HÌNH ROUTE CHUẨN
// =====================
const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginPage,
    meta: { guestOnly: true },
  },
  {
    path: "/",
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      { path: "", redirect: "/profile" },
      ...profileRoutes,
      ...declarationRoutes,

      ...searchRoutes,
      ...researchWorksRoutes,
      ...UserManagerRoutes,
      ...reportRoutes,
    ],
  },
  {
    path: "/403",
    name: "forbidden",
    component: () => import("@/features/errors/pages/ForbiddenPage.vue"),
    meta: { requiresAuth: true },
  },
  { path: "/:pathMatch(.*)*", redirect: "/login", meta: { guestOnly: true } },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

router.beforeEach(async (to) => {
  const userStore = useUserStore();

  const requiresAuth = to.matched.some(
    (record) => record.meta.requiresAuth === true
  );
  const isGuestOnly = to.matched.some(
    (record) => record.meta.guestOnly === true
  );
  const requiredRoles = to.matched
    .map((record) => record.meta.roles as string[] | undefined)
    .find((roles) => Array.isArray(roles) && roles.length);

  const needsSession = requiresAuth || isGuestOnly || Boolean(requiredRoles);

  if (needsSession && !userStore.isInitialized) {
    await userStore.initAuth();
  }

  const isAuthenticated = userStore.isAuthenticated;

  if (isGuestOnly && isAuthenticated) {
    return { path: "/", replace: true };
  }

  if (requiresAuth && !isAuthenticated) {
    const redirectQuery =
      to.fullPath && to.fullPath !== "/login"
        ? { query: { redirect: to.fullPath } }
        : {};
    return { name: "login", replace: true, ...redirectQuery };
  }

  if (requiredRoles) {
    const currentRole = userStore.role;
    if (!currentRole || !requiredRoles.includes(currentRole)) {
      return { path: "/403", replace: true };
    }
  }

  return true;
});

export default router;
