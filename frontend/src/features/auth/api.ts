import http, { ensureCsrfCookie } from "@/lib/http";

interface LoginRequest {
  email: string;
  password: string;
  role: string;
}

// Session-based SPA: use /me (web guard + cookie/CSRF), not the token-based /api/auth/me
export const fetchCurrentUser = () => http.get("/me");

export const login = async (payload: LoginRequest) => {
  await ensureCsrfCookie();
  return http.post("/login", payload);
};

export const logout = async () => {
  await ensureCsrfCookie();
  return http.post("/logout");
};
