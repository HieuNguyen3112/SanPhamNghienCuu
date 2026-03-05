import http from "@/lib/http";
import type { PublicResearchDetailResponseDto } from "../dto/publicResearchDtos";
import type {
  PublicResearchListQueryDto,
  PublicResearchListResponseDto,
} from "../dto/publicResearchDtos";

type ApiResponse<T> = {
  success: boolean;
  message?: string;
  data: T;
};

export async function fetchPublicResearchItemsApi(
  query: PublicResearchListQueryDto
): Promise<PublicResearchListResponseDto> {
  const { data } = await http.get<ApiResponse<PublicResearchListResponseDto>>(
    "/api/public/research-works",
    { params: query }
  );
  return data.data;
}

export async function fetchPublicResearchDetailApi(
  activityId: number
): Promise<PublicResearchDetailResponseDto> {
  const { data } = await http.get<{ success: boolean; data: PublicResearchDetailResponseDto }>(
    `/api/public/research-works/${activityId}`
  );
  return data.data;
}