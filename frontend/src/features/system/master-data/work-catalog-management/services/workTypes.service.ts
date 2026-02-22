import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  WorkType,
  WorkTypeDTO,
  WorkTypeUpsertDTO,
} from "../contracts/workTypes.contract";
import { workTypeFromDto } from "../contracts/workTypes.contract";
import { workTypesApi } from "../api/workTypes.api";

export const workTypeService = {
  async list(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<WorkType>> {
    const data = await workTypesApi.list(params);
    return {
      items: data.items.map(workTypeFromDto),
      pagination: data.pagination,
    };
  },

  async upsert(dto: WorkTypeUpsertDTO): Promise<WorkType> {
    const payload = {
      name: dto.name,
      description: dto.description ?? null,
      is_active: dto.is_active,
    };
    let saved: WorkTypeDTO;

    if (!dto.id) saved = await workTypesApi.create(payload);
    else saved = await workTypesApi.update(dto.id, payload);

    return workTypeFromDto(saved);
  },

  async setActive(id: number, isActive: boolean): Promise<WorkType> {
    const saved = await workTypesApi.updateStatus(id, { is_active: isActive });
    return workTypeFromDto(saved);
  },
};
