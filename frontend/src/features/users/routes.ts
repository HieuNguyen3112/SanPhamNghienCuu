import UserManagementPage from "@/features/users/pages/UserManagementPage.vue";
import OrgStructureManagementPage from "@/features/users/pages/OrgStructureManagementPage.vue";
import type { RouteRecordRaw } from "vue-router";
export const UserManagerRoutes: RouteRecordRaw[] = [
  {
    path: "user/manager",
    name: "user.manager",
    component: UserManagementPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "userorg/structure",
    name: "userorg.structure",
    component: OrgStructureManagementPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
];
