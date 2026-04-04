import axios from "axios";

const normalizeBaseUrl = (value?: string) => {
  const rawValue = (value || "http://localhost:8000").trim();
  return rawValue.replace(/\/+$/, "");
};

const http = axios.create({
  baseURL: normalizeBaseUrl(import.meta.env.VITE_API_URL),
  withCredentials: true, // Required for Sanctum session cookies.
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
});

const readCookie = (name: string) => {
  const match = document.cookie.match(new RegExp("(^|; )" + name + "=([^;]+)"));
  const value = match?.[2];
  return value ? decodeURIComponent(value) : null;
};

let csrfPromise: Promise<void> | null = null;

// Axios only auto-adds the XSRF header for same-origin requests, so attach it manually here.
http.interceptors.request.use((config) => {
  const token = readCookie("XSRF-TOKEN");
  if (token) {
    config.headers = config.headers || {};
    config.headers["X-XSRF-TOKEN"] = token;
  }
  return config;
});

type CsrfCookieOptions = {
  force?: boolean;
};

// Ensure the SPA has a fresh CSRF cookie before mutating requests.
export const getCsrfCookie = async (options: CsrfCookieOptions = {}) => {
  const { force = false } = options;
  if (!force && readCookie("XSRF-TOKEN")) return;
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
export const refreshCsrfCookie = () => getCsrfCookie({ force: true });

export default http;
