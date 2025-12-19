// src/features/profile/menu.ts
import type { MenuItem } from "@/app/config/menu.types";
import type { UserRole } from "@/app/stores/userStore";
import {
  FileText,
  Phone,
  Briefcase,
  GraduationCap,
  Award,
  Microscope,
  Languages,
} from "lucide-vue-next";

const PROFILE_ROLES: UserRole[] = ["LECTURER"];

export const profileMenuGroup: MenuItem = {
  id: "grp-profile",
  label: "Hồ sơ cá nhân",
  roles: PROFILE_ROLES,
};

export const profileMenuItems: MenuItem[] = [
  {
    id: "profile-scientific",
    label: "Hồ sơ khoa học",
    routeName: "profile.scientific",
    roles: PROFILE_ROLES,
    icon: FileText,
  },
  {
    id: "profile-contact",
    label: "Thông tin liên hệ",
    routeName: "profile.contact",
    roles: PROFILE_ROLES,
    icon: Phone,
  },
  {
    id: "profile-work-history",
    label: "Quá trình công tác",
    routeName: "profile.workHistory",
    roles: PROFILE_ROLES,
    icon: Briefcase,
  },
  {
    id: "profile-education",
    label: "Quá trình đào tạo",
    routeName: "profile.education",
    roles: PROFILE_ROLES,
    icon: GraduationCap,
  },
  {
    id: "profile-academic-rank",
    label: "Học vị – chức danh khoa học",
    routeName: "profile.academicRank",
    roles: PROFILE_ROLES,
    icon: Award,
  },
  {
    id: "profile-research",
    label: "Lĩnh vực nghiên cứu",
    routeName: "profile.research",
    roles: PROFILE_ROLES,
    icon: Microscope,
  },
  {
    id: "profile-languages",
    label: "Trình độ ngoại ngữ",
    routeName: "profile.languages",
    roles: PROFILE_ROLES,
    icon: Languages,
  },
];
