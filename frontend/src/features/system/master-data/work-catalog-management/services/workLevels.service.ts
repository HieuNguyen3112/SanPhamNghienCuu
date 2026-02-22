import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  WorkLevel,
  WorkLevelDTO,
  WorkLevelUpsertDTO,
} from "../contracts/workLevels.contract";
import { workLevelFromDto } from "../contracts/workLevels.contract";
import { workLevelsApi } from "../api/workLevels.api";

export const workLevelService = {
  async list(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<WorkLevel>> {
    const data = await workLevelsApi.list(params);
    return {
      items: data.items.map(workLevelFromDto),
      pagination: data.pagination,
    };
  },

  async upsert(dto: WorkLevelUpsertDTO): Promise<WorkLevel> {
    const payload = {
      name: dto.name,
      priority: dto.priority,
      notes: dto.notes ?? null,
      is_active: dto.is_active,
    };

    let saved: WorkLevelDTO;
    if (!dto.id) saved = await workLevelsApi.create(payload);
    else saved = await workLevelsApi.update(dto.id, payload);

    return workLevelFromDto(saved);
  },

  async setActive(id: number, isActive: boolean): Promise<WorkLevel> {
    const saved = await workLevelsApi.updateStatus(id, { is_active: isActive });
    return workLevelFromDto(saved);
  },
};
