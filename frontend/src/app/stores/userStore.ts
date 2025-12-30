import { defineStore } from "pinia";
import {
  login as apiLogin,
  logout as apiLogout,
  fetchCurrentUser as apiFetchCurrentUser,
  getCsrfCookie as apiGetCsrfCookie,
} from "@/features/auth/api";

export type UserRole = "LECTURER" | "DEPARTMENT_BOARD" | "SCIENCE_OFFICE";

interface User {
  id: string;
  name: string;
  email: string;
  code?: string;
  department?: string;
  roles: UserRole[];
  avatar?: string;
}

interface LoginPayload {
  email: string;
  password: string;
  role: UserRole;
}

type AuthErrorCode =
  | null
  | "UNAUTHENTICATED"
  | "UNVERIFIED_EMAIL"
  | "FORBIDDEN_MISSING_ROLE"
  | "FORBIDDEN"
  | "UNKNOWN";

const mapBackendRole = (role: string): UserRole | null => {
  switch ((role || "").toUpperCase()) {
    case "GV":
    case "LECTURER":
      return "LECTURER";
    case "DL":
    case "DEPARTMENT_BOARD":
      return "DEPARTMENT_BOARD";
    case "QL":
    case "ADMIN":
    case "SCIENCE_OFFICE":
      return "SCIENCE_OFFICE";
    default:
      return null;
  }
};

const mapBackendRoles = (roles: string[] = [], fallback: string[] = []): UserRole[] => {
  const source = roles.length ? roles : fallback;
  const mapped = source
    .map((r) => mapBackendRole(r))
    .filter((r): r is UserRole => Boolean(r));
  return Array.from(new Set(mapped));
};


export const useUserStore = defineStore("user", {
  state: () => ({
    currentUser: null as User | null,
    currentRole: null as UserRole | null, // role dang chon
    _initPromise: null as Promise<User | null> | null,
    isInitialized: false,
    authErrorCode: null as AuthErrorCode,
    authErrorMessage: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.currentUser,
    role: (state) => state.currentRole,
  },

  actions: {
    async initAuth() {
      if (this.isInitialized && !this._initPromise) return this.currentUser;
      if (this._initPromise) return this._initPromise;
      // reset error state before fetch
      this.authErrorCode = null;
      this.authErrorMessage = null;

      this._initPromise = (async () => {
        try {
          const { data } = await apiFetchCurrentUser();
          const mappedRoles = mapBackendRoles(
            data?.roles ?? [],
            data?.backend_roles ?? []
          );
          if (!mappedRoles.length) {
            this.currentUser = null;
            this.currentRole = null;
            return null;
          }
          this.authErrorCode = null;
          this.authErrorMessage = null;
          this.currentUser = {
            id: String(data.id ?? ""),
            name: data.name ?? "",
            email: data.email ?? "",
            roles: mappedRoles,
            code: data.code,
            department: data.department,
            avatar: data.avatar,
          };
          if (
            !this.currentRole ||
            !this.currentUser.roles.includes(this.currentRole)
          ) {
            this.currentRole = mappedRoles[0] ?? null;
          }
          return this.currentUser;
        } catch (err: any) {
          const status = err?.response?.status;
          const code: AuthErrorCode =
            err?.response?.data?.code || (status === 401
              ? "UNAUTHENTICATED"
              : status === 403
              ? "FORBIDDEN"
              : "UNKNOWN");
          this.authErrorCode = code;
          this.authErrorMessage =
            err?.response?.data?.message ||
            (status === 401
              ? "Unauthenticated"
              : status === 403
              ? "Forbidden"
              : "Kh�ng th? t?i th�ng tin ngu?i d�ng");
          this.currentUser = null;
          this.currentRole = null;
          return null;
        } finally {
          this.isInitialized = true;
          this._initPromise = null;
        }
      })();

      return this._initPromise;
    },

    // Backward-compatible alias
    async ensureCurrentUser() {
      return this.initAuth();
    },

    async ensureAuthInitialized() {
      return this.initAuth();
    },

    async login(payload: LoginPayload) {
      try {
        await apiGetCsrfCookie();
      } catch (err) {
        const error = new Error("CSRF_FAILED");
        (error as any).cause = err;
        throw error;
      }

      // G?i role ? d?ng canonical (LECTURER/DEPARTMENT_BOARD/SCIENCE_OFFICE) d? kh?p v?i backend response
      await apiLogin({
        email: payload.email,
        password: payload.password,
        role: payload.role,
      });

      const { data } = await apiFetchCurrentUser();
      const mappedRoles = mapBackendRoles(
        data?.roles ?? [],
        data?.backend_roles ?? []
      );

      this.currentUser = {
        id: String(data.id ?? ""),
        name: data.name ?? "",
        email: data.email ?? "",
        roles: mappedRoles,
        code: data.code,
        department: data.department,
        avatar: data.avatar,
      };
      this.currentRole = mappedRoles[0] ?? null;
      this.isInitialized = true;
      this._initPromise = null;

      return mappedRoles;
    },

    setRole(role: UserRole) {
      if (!this.currentUser) throw new Error("CHUA_DANG_NHAP");

      if (!this.currentUser.roles.includes(role)) {
        throw new Error("ROLE_KHONG_HOP_LE");
      }

      this.currentRole = role;
    },

    async logout() {
      try {
        await apiLogout();
      } catch (e) {
        // best effort
      }
      this.currentUser = null;
      this.currentRole = null;
      this._initPromise = null;
      this.isInitialized = false;
    },
  },
});


