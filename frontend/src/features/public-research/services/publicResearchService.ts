import type {
  PublicResearchListQueryDto,
  PublicResearchListResponseDto,
} from "../dto/publicResearchDtos";
import { fetchPublicResearchItemsApi } from "../api/publicResearchApi";
import { mapPublicResearchItemDtoToModel } from "../mappers/publicResearchMappers";
import type { PublicResearchItem } from "../models/publicResearchModels";
import { fetchPublicResearchDetailApi } from "../api/publicResearchApi";
import { mapPublicResearchDetailDtoToModel } from "../mappers/publicResearchMappers";
import type { PublicResearchDetail } from "../models/publicResearchModels";
import type { PublicResearchDetailResponseDto } from "../dto/publicResearchDtos";

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
export async function loadPublicResearchDetailService(activityId: number): Promise<PublicResearchDetail> {
  const res: PublicResearchDetailResponseDto = await fetchPublicResearchDetailApi(activityId);
  return mapPublicResearchDetailDtoToModel(res.item);
}
