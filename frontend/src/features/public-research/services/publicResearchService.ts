import type {
  PublicResearchListQueryDto,
  PublicResearchListResponseDto,
} from "../dto/publicResearchDtos";
import { fetchPublicResearchItemsApi } from "../api/publicResearchApi";
import { mapPublicResearchItemDtoToModel } from "../mappers/publicResearchMappers";
import type { PublicResearchItem } from "../models/publicResearchModels";

export type PublicResearchListResult = {
  items: PublicResearchItem[];
  total: number;
};

export async function loadPublicResearchItemsService(
  query: PublicResearchListQueryDto
): Promise<PublicResearchListResult> {
  const response: PublicResearchListResponseDto =
    await fetchPublicResearchItemsApi(query);

  return {
    items: response.items.map(mapPublicResearchItemDtoToModel),
    total: response.total,
  };
}
