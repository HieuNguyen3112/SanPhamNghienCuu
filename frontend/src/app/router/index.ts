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
import { declarationRoutes } from "@/features/declarations/routes";
import { hoursRoutes } from "@/features/hours/routes";
import { searchRoutes } from "@/features/search/routes";
import { researchWorksRoutes } from "@/features/research-works/routes";
import { UserManagerRoutes } from "@/features/users/routes";
// =====================
// CẤU HÌNH ROUTE CHUẨN
// =====================
const routes: RouteRecordRaw[] = [
  // ===== LOGIN (public) =====
  {
    path: "/login",
    name: "login",
    component: LoginPage,
    meta: { public: true },
  },

  // ===== LAYOUT CHÍNH (có bảo vệ) =====
  {
    path: "/",
    component: MainLayout,
    children: [
      { path: "", redirect: "/profile" }, // Trang mặc định
      ...profileRoutes,
      ...declarationRoutes,
      ...hoursRoutes,
      ...searchRoutes,
      ...researchWorksRoutes,
      ...UserManagerRoutes,
    ],
  },

  // ===== NOT FOUND =====
  {
    path: "/",
    redirect: "/login",
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// =====================
// ROUTER GUARD
// =====================
router.beforeEach((to) => {
  const userStore = useUserStore();

  // Route công khai (login)
  if (to.meta.public) return true;

  // Chưa đăng nhập → về login
  if (!userStore.isAuthenticated) {
    return { name: "login" };
  }

  // Kiểm tra quyền truy cập theo role nếu có meta.roles
  const allowedRoles = to.meta.roles as string[] | undefined;
  if (allowedRoles && !allowedRoles.includes(userStore.role!)) {
    return "/"; // hoặc chuyển tới trang 403
  }

  return true;
});

export default router;
