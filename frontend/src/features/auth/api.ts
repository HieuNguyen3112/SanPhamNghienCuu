import http, { getCsrfCookie as fetchCsrfCookie } from "@/lib/http";

interface LoginRequest {
  email: string;
  password: string;
  role: string;
}

// Session-based SPA: use /me (web guard + cookie/CSRF), not the token-based /api/auth/me
export const fetchCurrentUser = () => http.get("/me");

export const getCsrfCookie = () => fetchCsrfCookie();

export const login = (payload: LoginRequest) => http.post("/login", payload);

export const logout = async () => {
  await fetchCsrfCookie();
  return http.post("/logout");
};

export const changePassword = async (payload: {
  current_password: string;
  password: string;
  password_confirmation: string;
}) => {
  await fetchCsrfCookie();
  return http.put("/profile/password", payload);
};
