// src/features/hours/menu.ts
import type { MenuItem } from "@/app/config/menu.types";

export const hoursMenuGroup: MenuItem = {
  id: "grp-hours",
  label: "Kê khai giờ khoa học ",
  roles: ["LECTURER"],
};
export const getHoursFacultyLabel = (role: string) => {
  if (role === "DEPARTMENT_BOARD") return "Giờ NCKH của khoa";
  if (role === "SCIENCE_OFFICE") return "Giờ NCKH toàn trường";
  return "Giờ NCKH";
};
export const hoursMenuItems: MenuItem[] = [
  {
    id: "hours-my",
    label: "Giờ NCKH cá nhân", // bớt chữ "Theo dõi"
    routeName: "hours.my",
    roles: ["LECTURER"],
  },
  {
    id: "hours-calculate",
    label: "Tính giờ NCKH", // giữ nguyên, chỉ bỏ space thừa
    routeName: "hours.calculate",
    roles: ["LECTURER"],
  },
  {
    id: "hours-warnings",
    label: "Thông báo NCKH", // rõ ràng đây là thông báo liên quan NCKH
    routeName: "hours.warnings",
    roles: ["LECTURER"],
  },
  {
    id: "hours-summary-department",
    roles: ["DEPARTMENT_BOARD", "SCIENCE_OFFICE"],
    // dùng label trung tính để dùng chung cho khoa + phòng KH
    label: "Tổng hợp giờ NCKH", // hoặc "Thống kê giờ NCKH"
    routeName: "hours.summary",
  },
  {
    id: "hours-approvals",
    label: "Xét duyệt giờ NCKH", // chuẩn, rõ vai trò phê duyệt
    routeName: "hours.approvals",
    roles: ["SCIENCE_OFFICE"],
  },
];
