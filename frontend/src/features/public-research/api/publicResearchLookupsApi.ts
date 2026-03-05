import http from "@/lib/http";

type ApiResponse<T> = {
  success: boolean;
  message?: string;
  data: T;
};

export type PublicResearchLookupsDto = {
  faculties: { id: number; name: string }[];
  academic_years: { id: number; code: string }[];
};

export async function fetchPublicResearchLookupsApi(): Promise<PublicResearchLookupsDto> {
  const { data } = await http.get<ApiResponse<PublicResearchLookupsDto>>(
    "/api/public/research-works/lookups"
  );
  return data.data;
}