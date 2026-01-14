import type {
  ConferenceDTO,
  JournalDTO,
  JournalRankingDTO,
  ResearchFieldDTO,
  WorkCatalogListResponseDTO,
  WorkLevelDTO,
  WorkTypeDTO,
} from "../contracts/workCatalog.contract";
import {
  createConferenceApi,
  createJournalApi,
  createJournalRankingApi,
  createResearchFieldApi,
  createWorkLevelApi,
  createWorkTypeApi,
  listConferencesApi,
  listJournalsApi,
  listResearchFieldsApi,
  listWorkLevelsApi,
  listWorkTypesApi,
  updateConferenceApi,
  updateConferenceStatusApi,
  updateJournalApi,
  updateJournalStatusApi,
  updateResearchFieldApi,
  updateResearchFieldStatusApi,
  updateWorkLevelApi,
  updateWorkLevelStatusApi,
  updateWorkTypeApi,
  updateWorkTypeStatusApi,
} from "../api/workCatalogApi";

function resolveApiErrorMessage(error: unknown, fallback: string): string {
  if (typeof error === "object" && error !== null) {
    const anyError = error as {
      message?: string;
      response?: { data?: { message?: string } };
    };
    return anyError.response?.data?.message || anyError.message || fallback;
  }
  return fallback;
}

export const workCatalogService = {
  async listWorkTypes(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<WorkCatalogListResponseDTO<WorkTypeDTO>> {
    try {
      return await listWorkTypesApi(params);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to load work types."));
    }
  },

  async upsertWorkType(
    payload: Omit<WorkTypeDTO, "updated_at">
  ): Promise<WorkTypeDTO> {
    try {
      if (payload.id) {
        return await updateWorkTypeApi(payload.id, payload);
      }
      return await createWorkTypeApi(payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to save work type."));
    }
  },

  async setWorkTypeStatus(id: number, isActive: boolean): Promise<WorkTypeDTO> {
    try {
      return await updateWorkTypeStatusApi(id, isActive);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to update status."));
    }
  },

  async listWorkLevels(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<WorkCatalogListResponseDTO<WorkLevelDTO>> {
    try {
      return await listWorkLevelsApi(params);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to load work levels."));
    }
  },

  async upsertWorkLevel(
    payload: Omit<WorkLevelDTO, "updated_at">
  ): Promise<WorkLevelDTO> {
    try {
      if (payload.id) {
        return await updateWorkLevelApi(payload.id, payload);
      }
      return await createWorkLevelApi(payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to save work level."));
    }
  },

  async setWorkLevelStatus(
    id: number,
    isActive: boolean
  ): Promise<WorkLevelDTO> {
    try {
      return await updateWorkLevelStatusApi(id, isActive);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to update status."));
    }
  },

  async listJournals(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<WorkCatalogListResponseDTO<JournalDTO>> {
    try {
      return await listJournalsApi(params);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to load journals."));
    }
  },

  async upsertJournal(
    payload: Omit<JournalDTO, "updated_at">
  ): Promise<JournalDTO> {
    try {
      if (payload.id) {
        return await updateJournalApi(payload.id, payload);
      }
      return await createJournalApi(payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to save journal."));
    }
  },

  async setJournalStatus(id: number, isActive: boolean): Promise<JournalDTO> {
    try {
      return await updateJournalStatusApi(id, isActive);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to update status."));
    }
  },

  async setJournalRanking(payload: {
    journal_id: number;
    rank: string;
    effective_from: string;
    note: string | null;
  }): Promise<JournalRankingDTO> {
    try {
      return await createJournalRankingApi(payload.journal_id, {
        rank: payload.rank,
        effective_from: payload.effective_from,
        note: payload.note,
      });
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to save ranking."));
    }
  },

  async listConferences(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<WorkCatalogListResponseDTO<ConferenceDTO>> {
    try {
      return await listConferencesApi(params);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to load conferences."));
    }
  },

  async upsertConference(
    payload: Omit<ConferenceDTO, "updated_at">
  ): Promise<ConferenceDTO> {
    try {
      if (payload.id) {
        return await updateConferenceApi(payload.id, payload);
      }
      return await createConferenceApi(payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to save conference."));
    }
  },

  async setConferenceStatus(
    id: number,
    isActive: boolean
  ): Promise<ConferenceDTO> {
    try {
      return await updateConferenceStatusApi(id, isActive);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to update status."));
    }
  },

  async listResearchFields(params: {
    keyword?: string;
    page?: number;
    per_page?: number;
  }): Promise<WorkCatalogListResponseDTO<ResearchFieldDTO>> {
    try {
      return await listResearchFieldsApi(params);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to load research fields."));
    }
  },

  async upsertResearchField(
    payload: Omit<ResearchFieldDTO, "updated_at">
  ): Promise<ResearchFieldDTO> {
    try {
      if (payload.id) {
        return await updateResearchFieldApi(payload.id, payload);
      }
      return await createResearchFieldApi(payload);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to save research field."));
    }
  },

  async setResearchFieldStatus(
    id: number,
    isActive: boolean
  ): Promise<ResearchFieldDTO> {
    try {
      return await updateResearchFieldStatusApi(id, isActive);
    } catch (error) {
      throw new Error(resolveApiErrorMessage(error, "Failed to update status."));
    }
  },
};
