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
  | "declarations.articles"
  | "declarations.projects"
  | "declarations.books"
  | "declarations.others"
  | "hours.my"
  | "hours.faculty"
  | "search.global";
// sau này thêm route thì bổ sung vào đây

export interface MenuItem {
  id: string;
  label: string;
  routeName?: AppRouteName; // header thì không có
  roles?: UserRole[]; // menu áp dụng cho role nào
}
