import type { RouteRecordRaw } from "vue-router";

import OrgStructureManagementPage from "@/features/system/users/pages/OrgStructureManagementPage.vue";
import LecturerAccountManagementPage from "@/features/system/users-management/pages/LecturerAccountManagementPage.vue";

import FacultyAuditLogPage from "@/features/system/audit-log/pages/FacultyAuditLogPage.vue";
import UniversityAuditLogPage from "@/features/system/audit-log/pages/UniversityAuditLogPage.vue";

import WorkCatalogManagementPage from "@/features/system/master-data/work-catalog-management/pages/WorkCatalogManagementPage.vue";
import ResearchHoursCatalogPage from "@/features/system/master-data/research-hours-catalog/pages/ResearchHoursCatalogPage.vue";

export const UserManagerRoutes: RouteRecordRaw[] = [
  // Users
  {
    path: "users/lecturer-accounts",
    name: "user.manager",
    component: LecturerAccountManagementPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "users/org-structure",
    name: "userorg.structure",
    component: OrgStructureManagementPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },

  // Audit logs
  {
    path: "audit-logs/faculty",
    name: "auditlog.facmanagement",
    component: FacultyAuditLogPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "audit-logs/university",
    name: "auditlog.unimanagement",
    component: UniversityAuditLogPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },

  // Master data
  {
    path: "master-data/work-catalog",
    name: "masterdata.work_catalog",
    component: WorkCatalogManagementPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
  {
    path: "master-data/research-hours-catalog",
    name: "masterdata.hours_catalog",
    component: ResearchHoursCatalogPage,
    meta: { roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"] },
  },
];
