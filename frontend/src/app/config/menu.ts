// src/app/config/menu.ts

// Route name dùng trong app
export type AppRouteName =
  | "works-my-declarations"
  | "profile.scientific"
  | "profile.contact"
  | "profile.workHistory"
  | "profile.education"
  | "profile.researchAreas"
  | "profile.academicRank"
  | "profile.languages";
// … sau này thêm route thì mở rộng union này

export interface MenuItem {
  id: string;
  label: string;
  routeName?: AppRouteName; // header thì không có
}

// Cấu hình toàn bộ menu (chung cho mọi role)
const allMenuItems: MenuItem[] = [
  { id: "grp-profile", label: "Hồ sơ cá nhân" },
  {
    id: "profile-scientific",
    label: "Hồ sơ khoa học",
    routeName: "profile.scientific",
  },
  {
    id: "profile-contact",
    label: "Thông tin liên hệ",
    routeName: "profile.contact",
  },
  {
    id: "profile-work-history",
    label: "Quá trình công tác",
    routeName: "profile.workHistory",
  },
  {
    id: "profile-education",
    label: "Quá trình đào tạo",
    routeName: "profile.education",
  },
  {
    id: "profile-academic-rank",
    label: "Học vị – chức danh khoa học",
    routeName: "profile.academicRank",
  },
  {
    id: "profile-languages",
    label: "Trình độ ngoại ngữ",
    routeName: "profile.languages",
  },
];

// Hiện tại chưa dùng role → chỉ trả hết
export function buildMenuForRole(): MenuItem[] {
  return allMenuItems;
}
