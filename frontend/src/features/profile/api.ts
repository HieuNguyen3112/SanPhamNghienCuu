import http, { ensureCsrfCookie } from "@/lib/http";

export const fetchProfile = () => http.get("/api/profile/me");

export const updateContact = async (payload: Record<string, any>) => {
  await ensureCsrfCookie();
  return http.put("/api/profile/contact", payload);
};

export const updateAcademicTitles = async (items: any[]) => {
  await ensureCsrfCookie();
  return http.put("/api/profile/academic-titles", { items });
};

export const updateEducations = async (items: any[]) => {
  await ensureCsrfCookie();
  return http.put("/api/profile/educations", { items });
};

export const updateLanguages = async (items: any[]) => {
  await ensureCsrfCookie();
  return http.put("/api/profile/languages", { items });
};

export const updateResearchAreas = async (items: any[]) => {
  await ensureCsrfCookie();
  return http.put("/api/profile/research-areas", { items });
};

export const updateWorkHistories = async (items: any[]) => {
  await ensureCsrfCookie();
  return http.put("/api/profile/work-histories", { items });
};
