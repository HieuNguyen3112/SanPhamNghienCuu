import { defineStore } from "pinia";

export type UserRole = "LECTURER" | "DEPARTMENT_BOARD" | "SCIENCE_OFFICE";

interface User {
  id: string;
  name: string;
  code: string;
  department: string;
  roles: UserRole[]; // 👈 1 user có thể có nhiều role
  avatar?: string;
}

interface LoginPayload {
  username: string;
  password: string;
}

export const useUserStore = defineStore("user", {
  state: () => ({
    currentUser: null as User | null,
    currentRole: null as UserRole | null, // 👈 role đang được chọn
  }),

  getters: {
    isAuthenticated: (state) => !!state.currentUser,
    role: (state) => state.currentRole, // 👈 dùng getter này ở menu, guard, sidebar
  },

  actions: {
    login(payload: LoginPayload) {
      const mockAccounts: { username: string; password: string; user: User }[] =
        [
          {
            username: "gv",
            password: "123",
            user: {
              id: "GV001",
              name: "Nguyễn Văn A",
              code: "48.01.104.001",
              department: "Khoa CNTT",
              roles: ["LECTURER"], // chỉ là giảng viên
              avatar: "https://i.pravatar.cc/100?img=1",
            },
          },
          {
            username: "bcn",
            password: "123",
            user: {
              id: "BCN001",
              name: "Trần Thị B",
              code: "48.01.104.002",
              department: "Khoa CNTT",
              roles: ["DEPARTMENT_BOARD"], // chỉ BCN
              avatar: "https://i.pravatar.cc/100?img=5",
            },
          },
          {
            username: "qlkh",
            password: "123",
            user: {
              id: "QLKH001",
              name: "Phạm Quốc C",
              code: "48.01.104.003",
              department: "Phòng QLKH",
              roles: ["SCIENCE_OFFICE"], // chỉ QLKH
              avatar: "https://i.pravatar.cc/100?img=12",
            },
          },
          {
            // 👉 TÀI KHOẢN ĐA VAI TRÒ: vừa là GV vừa là QLKH
            username: "multi",
            password: "123",
            user: {
              id: "M001",
              name: "Nguyễn Đa Vai Trò",
              code: "48.01.104.999",
              department: "Khoa CNTT",
              roles: ["LECTURER", "SCIENCE_OFFICE"],
              avatar: "https://i.pravatar.cc/100?img=20",
            },
          },
        ];

      const found = mockAccounts.find(
        (acc) =>
          acc.username === payload.username && acc.password === payload.password
      );

      if (!found) {
        throw new Error("SAI_TAI_KHOAN");
      }

      this.currentUser = found.user;
      this.currentRole = found.user.roles[0] ?? null; // default role đầu tiên
    },

    // đổi role đang dùng
    setRole(role: UserRole) {
      if (!this.currentUser) throw new Error("CHUA_DANG_NHAP");

      if (!this.currentUser.roles.includes(role)) {
        throw new Error("ROLE_KHONG_HOP_LE");
      }

      this.currentRole = role;
    },

    logout() {
      this.currentUser = null;
      this.currentRole = null;
    },
  },
});
