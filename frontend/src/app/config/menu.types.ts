// src/app/config/menu.types.ts
import type { UserRole } from "@/app/stores/userStore";

// Route name dùng trong app
export type AppRouteName =
  | "works-my-declarations"
  | "profile.scientific"
  | "profile.contact"
  | "profile.workHistory"
  | "profile.education"
  | "profile.researchAreas"
  | "profile.academicRank"
  | "profile.languages"
  | "profile.research"
  | "declarations.articles"
  | "declarations.projects"
  | "declarations.books"
  | "declarations.others"
  | "declarations.participatier"
  | "works.approvals"
  | "hours.my"
  | "hours.summary"
  | "search.global"
  | "hours.calculate"
  | "hours.warnings"
  | "hours.approvals"
  | "hours.batchDetail";
// sau này thêm route thì bổ sung vào đây

export interface MenuItem {
  id: string;
  label: string;
  routeName?: AppRouteName; // header thì không có
  roles?: UserRole[]; // menu áp dụng cho role nào
}
