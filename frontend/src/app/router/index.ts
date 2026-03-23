import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import type { UserRole } from "@/app/stores/userStore";

// Layouts
import MainLayout from "@/layouts/MainLayout.vue";
import LoginPage from "@/features/auth/pages/LoginPage.vue";
import ResetPasswordPage from "@/features/auth/pages/ResetPasswordPage.vue";

// ✅ Public HomePage
import HomePage from "@/features/search/pages/HomePage.vue";

// Features (✅ lấy đúng import paths từ index.ts 2)
import { profileRoutes } from "@/features/profile/routes";
import { declarationRoutes } from "@/features/declaration/routes";
import { searchRoutes } from "@/features/research-work-search/routes";
import { researchWorksRoutes } from "@/features/scientific/routes";
import { UserManagerRoutes } from "@/features/system/routes";
import { reportRoutes } from "@/features/reports/routes";

// =====================
// ROUTES
// =====================
const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginPage,
    meta: { guestOnly: true },
  },
  {
    path: "/reset-password",
    name: "reset-password",
    component: ResetPasswordPage,
  },

  /**
   * ✅ PUBLIC HOME (ALWAYS PUBLIC)
   * - "/" luôn hiển thị trang tra cứu công khai
   * - initAuth: để guard init session -> header biết login hay chưa
   */
  {
    path: "/",
    name: "public-home",
    component: HomePage,
    meta: { initAuth: true },
  },

  // ✅ PUBLIC SEARCH ROUTES (ALWAYS PUBLIC)
  {
    path: "/lecturers",
    name: "public-lecturer",
    component: () => import("@/features/search/pages/PublicLecturerPage.vue"),
    meta: { initAuth: true },
  },
  {
    path: "/lecturers/:lecturerCode",
    name: "public-lecturer-detail",
    component: () =>
      import("@/features/search/pages/PublicLecturerDetailPage.vue"),
    meta: { initAuth: true },
  },
  {
    path: "/research-articles",
    name: "public-article",
    component: () => import("@/features/search/pages/PublicSearchPage.vue"),
    props: { preset: "article" },
    meta: { initAuth: true },
  },
  {
    path: "/research-projects",
    name: "public-project",
    component: () => import("@/features/search/pages/PublicSearchPage.vue"),
    props: { preset: "project" },
    meta: { initAuth: true },
  },
  {
    path: "/textbooks",
    name: "public-book",
    component: () => import("@/features/search/pages/PublicSearchPage.vue"),
    props: { preset: "book" },
    meta: { initAuth: true },
  },
  {
    path: "/research-conferences",
    name: "public-conference",
    component: () => import("@/features/search/pages/PublicSearchPage.vue"),
    props: { preset: "conference" },
    meta: { initAuth: true },
  },
  {
    path: "/user-guide",
    name: "public-guide",
    component: () => import("@/features/search/pages/PublicGuidePage.vue"),
    meta: { initAuth: true },
  },
  /**
   * ✅ PRIVATE APP SHELL (AUTH REQUIRED)
   * - Giữ các route private như hiện tại
   */
  {
    path: "/",
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      // Optional: /me -> /profile
      { path: "me", redirect: "/profile" },

      // Routes modules
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

  // Unknown -> về public home
  { path: "/:pathMatch(.*)*", redirect: "/" },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

router.beforeEach(async (to) => {
  const userStore = useUserStore();

  const requiresAuth = to.matched.some(
    (record) => record.meta.requiresAuth === true,
  );
  const isGuestOnly = to.matched.some(
    (record) => record.meta.guestOnly === true,
  );
  const needsInitAuth = to.matched.some(
    (record) => (record.meta as any).initAuth === true,
  );

  const requiredRoles = to.matched
    .map((record) => record.meta.roles as string[] | undefined)
    .find((roles) => Array.isArray(roles) && roles.length);

  const needsSession =
    requiresAuth || isGuestOnly || Boolean(requiredRoles) || needsInitAuth;

  // ✅ dùng đúng init method từ index.ts 2
  if (needsSession && !userStore.isInitialized) {
    await userStore.bootstrapAuth();
  }

  const isAuthenticated = userStore.isAuthenticated;

  // ✅ Luồng bạn muốn: bấm /login nếu đã login -> chuyển thẳng /profile
  if (isGuestOnly && isAuthenticated) {
    return { path: "/profile", replace: true };
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
      const availableRoles = userStore.currentUser?.roles ?? [];
      const switchableRole = (requiredRoles as UserRole[]).find((role) =>
        availableRoles.includes(role),
      );

      if (switchableRole) {
        try {
          await userStore.switchRole(switchableRole);
          return true;
        } catch {
          return { path: "/403", replace: true };
        }
      }

      return { path: "/403", replace: true };
    }
  }

  return true;
});

export default router;
