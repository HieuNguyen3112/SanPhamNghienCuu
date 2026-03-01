import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  Journal,
  JournalDTO,
  JournalUpsertDTO,
} from "../contracts/journals.contract";
import { journalFromDto } from "../contracts/journals.contract";
import { journalsApi } from "../api/journals.api";

export const journalService = {
  async list(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<Journal>> {
    const data = await journalsApi.list(params);
    return {
      items: data.items.map(journalFromDto),
      pagination: data.pagination,
    };
  },

  async upsert(dto: JournalUpsertDTO): Promise<Journal> {
    const payload: Omit<JournalUpsertDTO, "id"> = {
      name: dto.name,
      issn: dto.issn ?? null,
      address: dto.address ?? null,
      country: dto.country ?? null,
      notes: dto.notes ?? null,
      source_name: dto.source_name ?? null,
      point_min: dto.point_min ?? null,
      point_max: dto.point_max ?? null,
      is_active: dto.is_active,
    };

    let saved: JournalDTO;
    if (!dto.id) saved = await journalsApi.create(payload);
    else saved = await journalsApi.update(dto.id, payload);

    return journalFromDto(saved);
  },

  async setActive(id: number, isActive: boolean): Promise<Journal> {
    const saved = await journalsApi.updateStatus(id, { is_active: isActive });
    return journalFromDto(saved);
  },
};
