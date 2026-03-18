import type { ListResponseDTO } from "../contracts/pagination.contract";
import type {
  Journal,
  JournalDTO,
  JournalSuggestion,
  JournalSuggestionDTO,
  JournalUpsertDTO,
} from "../contracts/journals.contract";
import {
  journalFromDto,
  journalSuggestionFromDto,
} from "../contracts/journals.contract";
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
      journal_type: dto.journal_type ?? null,
      research_field: dto.research_field ?? null,
      website: dto.website ?? null,
      address: dto.address ?? null,
      country: dto.country ?? null,
      notes: dto.notes ?? null,
      source_name: dto.source_name ?? null,
      publisher: dto.publisher ?? null,
      point: dto.point ?? null,
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

  async listSuggestions(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<JournalSuggestion>> {
    const data = await journalsApi.listSuggestions(params);
    return {
      items: data.items.map(journalSuggestionFromDto),
      pagination: data.pagination,
    };
  },

  async approveSuggestion(
    id: number,
    review_note?: string,
  ): Promise<{
    suggestion: JournalSuggestion | null;
    catalog: Journal | null;
  }> {
    const data = await journalsApi.approveSuggestion(
      id,
      review_note ? { review_note } : undefined,
    );

    return {
      suggestion: data.suggestion
        ? journalSuggestionFromDto(data.suggestion as JournalSuggestionDTO)
        : null,
      catalog: data.catalog ? journalFromDto(data.catalog) : null,
    };
  },
  async rejectSuggestion(
    id: number,
    review_note?: string,
  ): Promise<{
    suggestion: JournalSuggestion | null;
    catalog: Journal | null;
  }> {
    const data = await journalsApi.rejectSuggestion(
      id,
      review_note ? { review_note } : undefined,
    );

    return {
      suggestion: data.suggestion
        ? journalSuggestionFromDto(data.suggestion as JournalSuggestionDTO)
        : null,
      catalog: data.catalog ? journalFromDto(data.catalog) : null,
    };
  },
};
