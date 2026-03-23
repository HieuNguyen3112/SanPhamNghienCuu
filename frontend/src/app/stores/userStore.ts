import { defineStore } from "pinia";
import {
  login as apiLogin,
  logout as apiLogout,
  fetchCurrentUser as apiFetchCurrentUser,
  getCsrfCookie as apiGetCsrfCookie,
  switchActiveRole as apiSwitchActiveRole,
} from "@/features/auth/api";

export type UserRole = "LECTURER" | "DEPARTMENT_BOARD" | "SCIENCE_OFFICE";

const AUTH_SESSION_HINT_KEY = "spnc.auth.session_hint";

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
    case "LECTURER":
      return "LECTURER";
    case "DEPARTMENT_BOARD":
      return "DEPARTMENT_BOARD";
    case "SCIENCE_OFFICE":
      return "SCIENCE_OFFICE";
    default:
      return null;
  }
};

const mapBackendRoles = (roles: string[] = []): UserRole[] => {
  const mapped = roles
    .map((r) => mapBackendRole(r))
    .filter((r): r is UserRole => Boolean(r));
  return Array.from(new Set(mapped));
};

const pickPreferredRole = (params: {
  mappedRoles: UserRole[];
  activeRoleRaw?: string | null;
  fallbackRole?: UserRole | null;
}): UserRole | null => {
  const { mappedRoles, activeRoleRaw, fallbackRole } = params;

  const activeRole = mapBackendRole(activeRoleRaw ?? "");
  if (activeRole && mappedRoles.includes(activeRole)) {
    return activeRole;
  }

  if (fallbackRole && mappedRoles.includes(fallbackRole)) {
    return fallbackRole;
  }

  return mappedRoles[0] ?? null;
};

const readAuthSessionHint = (): boolean => {
  if (typeof window === "undefined") return false;
  try {
    return window.localStorage.getItem(AUTH_SESSION_HINT_KEY) === "1";
  } catch {
    return false;
  }
};

const writeAuthSessionHint = (value: boolean) => {
  if (typeof window === "undefined") return;
  try {
    if (value) {
      window.localStorage.setItem(AUTH_SESSION_HINT_KEY, "1");
      return;
    }
    window.localStorage.removeItem(AUTH_SESSION_HINT_KEY);
  } catch {
    // Ignore storage write errors. Auth still works without the hint.
  }
};

export const useUserStore = defineStore("user", {
  state: () => ({
    currentUser: null as User | null,
    currentRole: null as UserRole | null, // role đang chọn
    _initPromise: null as Promise<User | null> | null,
    isInitialized: false,
    hasSessionHint: readAuthSessionHint(),
    authErrorCode: null as AuthErrorCode,
    authErrorMessage: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.currentUser,
    role: (state) => state.currentRole,
  },

  actions: {
    setSessionHint(value: boolean) {
      this.hasSessionHint = value;
      writeAuthSessionHint(value);
    },

    async bootstrapAuth() {
      if (this.isInitialized && !this._initPromise) return this.currentUser;

      if (!this.hasSessionHint) {
        this.currentUser = null;
        this.currentRole = null;
        this.authErrorCode = null;
        this.authErrorMessage = null;
        this.isInitialized = true;
        return null;
      }

      return this.initAuth();
    },

    async initAuth() {
      if (this.isInitialized && !this._initPromise) return this.currentUser;
      if (this._initPromise) return this._initPromise;

      // reset error state before fetch
      this.authErrorCode = null;
      this.authErrorMessage = null;

      this._initPromise = (async () => {
        try {
          const { data } = await apiFetchCurrentUser();

          const mappedRoles = mapBackendRoles(data?.roles ?? []);
          if (!mappedRoles.length) {
            this.currentUser = null;
            this.currentRole = null;
            this.setSessionHint(false);
            return null;
          }

          this.currentUser = {
            id: String(data.id ?? ""),
            name: data.name ?? "",
            email: data.email ?? "",
            roles: mappedRoles,
            code: data.code,
            department: data.department,
            avatar: data.avatar,
          };

          this.currentRole = pickPreferredRole({
            mappedRoles,
            activeRoleRaw: data?.active_role,
            fallbackRole: this.currentRole,
          });

          this.setSessionHint(true);

          return this.currentUser;
        } catch (err: any) {
          const status = err?.response?.status;
          const code: AuthErrorCode =
            err?.response?.data?.code ||
            (status === 401
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
                : "Không thể tải thông tin người dùng");

          this.currentUser = null;
          this.currentRole = null;
          if (status === 401) {
            this.setSessionHint(false);
          }
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
      return this.bootstrapAuth();
    },

    async ensureAuthInitialized() {
      return this.bootstrapAuth();
    },

    async login(payload: LoginPayload) {
      try {
        await apiGetCsrfCookie();
      } catch (err) {
        const error = new Error("CSRF_FAILED");
        (error as any).cause = err;
        throw error;
      }

      // Gửi role ở dạng canonical (LECTURER/DEPARTMENT_BOARD/SCIENCE_OFFICE)
      await apiLogin({
        email: payload.email,
        password: payload.password,
        role: payload.role,
      });

      const { data } = await apiFetchCurrentUser();
      const mappedRoles = mapBackendRoles(data?.roles ?? []);

      this.currentUser = {
        id: String(data.id ?? ""),
        name: data.name ?? "",
        email: data.email ?? "",
        roles: mappedRoles,
        code: data.code,
        department: data.department,
        avatar: data.avatar,
      };

      this.currentRole = pickPreferredRole({
        mappedRoles,
        activeRoleRaw: data?.active_role,
        fallbackRole: payload.role,
      });
      this.isInitialized = true;
      this._initPromise = null;
      this.authErrorCode = null;
      this.authErrorMessage = null;
      this.setSessionHint(true);

      return mappedRoles;
    },

    setRole(role: UserRole) {
      if (!this.currentUser) throw new Error("CHUA_DANG_NHAP");
      if (!this.currentUser.roles.includes(role)) {
        throw new Error("ROLE_KHONG_HOP_LE");
      }
      this.currentRole = role;
    },

    async switchRole(role: UserRole) {
      if (!this.currentUser) throw new Error("CHUA_DANG_NHAP");
      if (!this.currentUser.roles.includes(role)) {
        throw new Error("ROLE_KHONG_HOP_LE");
      }

      await apiSwitchActiveRole(role);
      this.currentRole = role;
      return role;
    },

    async logout() {
      let remoteLogoutOk = true;
      try {
        await apiLogout();
      } catch (error) {
        remoteLogoutOk = false;
        if (import.meta.env.DEV) {
          console.error("[logout] API error", error);
        }
      }
      this.currentUser = null;
      this.currentRole = null;
      this._initPromise = null;
      this.isInitialized = false;
      this.authErrorCode = null;
      this.authErrorMessage = null;
      this.setSessionHint(false);
      return remoteLogoutOk;
    },
  },
});
