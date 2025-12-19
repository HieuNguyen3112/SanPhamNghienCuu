// src/app/config/menu.types.ts
import type { UserRole } from "@/app/stores/userStore";
import type { Component } from "vue";

// Route name dùng trong app
export type AppRouteName =
  // route page ho so ca nhan
  | "profile.scientific"
  | "profile.contact"
  | "profile.workHistory"
  | "profile.education"
  | "profile.researchAreas"
  | "profile.academicRank"
  | "profile.languages"
  | "profile.research"
  // quan ly khoa hoc
  // ke khai cho giang vien
  | "declarations.articles"
  | "declarations.projects"
  | "declarations.books"
  | "declarations.others"
  | "declarations.participatier"
  | "works-my-declarations"
  // duyet cong trinh cho manager
  | "works.facapprovals"
  | "works.uniapprovals"
  | "work.overview"
  // quan ly gio nckh cho giang vien
  | "hours.my"
  | "hours.summary"
  | "hours.calculate"
  | "hours.warnings"
  // duyet gio nckh
  | "hours.approvals"
  | "hours.batchDetail"
  | "search.global"
  | "user.manager"
  | "userorg.structure"
  | "report.lecturer"
  | "report.research"
  | "hours.warning"
  | "report.hour-research";

// sau này thêm route thì bổ sung vào đây

export interface MenuItem {
  id: string;
  label: string;
  routeName?: AppRouteName; // header thì không có
  roles?: UserRole[];
  icon?: Component; // NEW: icon component
}
