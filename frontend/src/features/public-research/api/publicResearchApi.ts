import { mockRequest } from "@/lib/apiClient";
import type {
  PublicResearchListQueryDto,
  PublicResearchListResponseDto,
} from "../dto/publicResearchDtos";
import { queryPublicResearchItems } from "../mock-data/publicResearchMockData";

export async function fetchPublicResearchItemsApi(
  query: PublicResearchListQueryDto
): Promise<PublicResearchListResponseDto> {
  return mockRequest(() => queryPublicResearchItems(query));
}
