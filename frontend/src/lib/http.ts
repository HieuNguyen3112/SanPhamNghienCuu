import axios from "axios";

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000",
  withCredentials: true, // bắt buộc cho Sanctum cookie
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
});

const readCookie = (name: string) => {
  const match = document.cookie.match(new RegExp("(^|; )" + name + "=([^;]+)"));
  const value = match?.[2];
  return value ? decodeURIComponent(value) : null;
};

let csrfPromise: Promise<void> | null = null;

// Axios chỉ tự thêm XSRF header cho same-origin; SPA chạy khác port nên cần tự gắn header.
http.interceptors.request.use((config) => {
  const token = readCookie("XSRF-TOKEN");
  if (token) {
    config.headers = config.headers || {};
    config.headers["X-XSRF-TOKEN"] = token;
  }
  return config;
});

// Đảm bảo lấy CSRF cookie trước khi gửi POST/PUT/PATCH/DELETE
export const getCsrfCookie = async () => {
  if (readCookie("XSRF-TOKEN")) return;
  if (csrfPromise) return csrfPromise;

  csrfPromise = http
    .get("/sanctum/csrf-cookie")
    .then(() => undefined)
    .finally(() => {
      csrfPromise = null;
    });

  return csrfPromise;
};

export const ensureCsrfCookie = () => getCsrfCookie();

export default http;
