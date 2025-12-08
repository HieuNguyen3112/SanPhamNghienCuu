import UserManagementPage from "@/features/users/pages/UserManagementView.vue";
import OrgStructureManagementPage from "@/features/users/pages/OrgStructureManagementPage.vue";
import type { RouteRecordRaw } from "vue-router";
export const UserManagerRoutes: RouteRecordRaw[] = [
  {
    path: "user/manager",
    name: "user.manager",
    component: UserManagementPage,
  },
  {
    path: "userorg/structure",
    name: "userorg.structure",
    component: OrgStructureManagementPage,
  },
];
