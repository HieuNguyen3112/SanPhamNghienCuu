import http, { getCsrfCookie as fetchCsrfCookie } from "@/lib/http";

interface LoginRequest {
  email: string;
  password: string;
  role: string;
}

// ✅ Session-based SPA + sanctum-protected API route
export const fetchCurrentUser = () => http.get("/api/auth/me");

export const getCsrfCookie = () => fetchCsrfCookie();

export const login = (payload: LoginRequest) => http.post("/login", payload);

export const forgotPassword = async (payload: { email: string }) => {
  await fetchCsrfCookie();
  return http.post("/password/forgot", payload);
};

export const resetPassword = async (payload: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}) => {
  await fetchCsrfCookie();
  return http.post("/password/reset", payload);
};

export const logout = async () => {
  await fetchCsrfCookie();
  return http.post("/logout");
};

export const switchActiveRole = async (role: string) => {
  await fetchCsrfCookie();
  return http.post("/api/auth/active-role", { role });
};

export const changePassword = async (payload: {
  current_password: string;
  password: string;
  password_confirmation: string;
}) => {
  await fetchCsrfCookie();
  return http.put("/profile/password", payload);
};
