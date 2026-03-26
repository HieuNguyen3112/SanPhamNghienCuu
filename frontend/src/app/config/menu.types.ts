// src/app/config/menu.types.ts
import type { UserRole } from "@/app/stores/userStore";
import type { Component } from "vue";

// Route name dùng trong app
export type AppRouteName =
  // =========================
  // LECTURER — Hồ sơ cá nhân
  // =========================
  | "profile.scientific"
  | "profile.contact"
  | "profile.workHistory"
  | "profile.education"
  | "profile.researchAreas"
  | "profile.academicRank"
  | "profile.languages"
  | "profile.research"

  // =========================
  // LECTURER — Kê khai công trình
  // =========================
  | "declarations.articles"
  | "declarations.projects"
  | "declarations.books"
  | "declarations.others"
  | "declarations.participatier"
  | "declarations.gateway"
  | "works.personal"

  // =========================
  // LECTURER — Quản lý giờ NCKH cá nhân
  // =========================
  | "hours.personal"
  | "hours.calculate"
  | "hours.personal_warnings"

  // =========================
  // DEPARTMENT_BOARD — Công trình (khoa)
  // =========================
  | "works.facmanagement"
  | "works.facapprovals"

  // =========================
  // DEPARTMENT_BOARD — Giờ NCKH (khoa)
  // =========================
  | "hours.facmanagement"
  | "hours.facapprovals"

  // =========================
  // SCIENCE_OFFICE — Công trình (toàn trường)
  // =========================
  | "works.unimanagement"

  // =========================
  // SCIENCE_OFFICE — Giờ NCKH (toàn trường)
  // =========================
  | "hours.unimanagement"
  | "hours.uniapprovals"

  // =========================
  // MANAGERS (DEPARTMENT_BOARD + SCIENCE_OFFICE) — Cảnh báo / tổng quan
  // =========================
  | "hours.warning"
  | "hours.facwarning"
  | "hours.uniwarning"
  | "auditlog.facmanagement"
  | "auditlog.unimanagement"

  // =========================
  // MASTERDATA (DEPARTMENT_BOARD + SCIENCE_OFFICE) — QUẢN LÝ DANH MỤC
  // =========================
  | "masterdata.work_catalog"
  | "masterdata.hours_catalog"
  | "system.backups"

  // =========================
  // SYSTEM / GLOBAL / REPORTS
  // =========================
  | "search.global"
  | "user.manager"
  | "userorg.structure"
  | "report.lecturer"
  | "report.research"
  | "report.hour-research";

// sau này thêm route thì bổ sung vào đây

export interface MenuItem {
  id: string;
  label: string;
  routeName?: AppRouteName; // header thì không có
  roles?: UserRole[];
  icon?: Component; // NEW: icon component
}
