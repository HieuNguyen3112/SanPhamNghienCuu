import type {
  AcademicYearDTO,
  AcademicYearDerivedDTO,
  ActivityKindDTO,
  ActivityTypeDTO,
  HourRuleDerivedDTO,
  ListResponseDTO,
  ResearchHoursMetaDTO,
  UpsertHourRulePayloadDTO,
  WorkloadQuotaDerivedDTO,
} from "../contracts/researchHoursCatalog.contract";
import {
  applyAcademicYearApi,
  createAcademicYearApi,
  createHourRuleApi,
  createWorkloadQuotaApi,
  fetchResearchHoursMeta,
  listAcademicYearsApi,
  listHourRulesApi,
  listWorkloadQuotasApi,
  updateAcademicYearApi,
  updateHourRuleApi,
  updateHourRuleStatusApi,
  updateWorkloadQuotaApi,
} from "../api/researchHoursCatalogApi";

let metaCache: ResearchHoursMetaDTO | null = null;
let metaPromise: Promise<ResearchHoursMetaDTO> | null = null;

async function loadMeta(force = false): Promise<ResearchHoursMetaDTO> {
  if (!force && metaCache) return metaCache;
  if (metaPromise) return metaPromise;

  metaPromise = fetchResearchHoursMeta()
    .then((data) => {
      metaCache = data;
      return data;
    })
    .finally(() => {
      metaPromise = null;
    });

  return metaPromise;
}

export const researchHoursCatalogService = {
  async getMeta(): Promise<ResearchHoursMetaDTO> {
    return loadMeta();
  },

  async refreshMeta(): Promise<ResearchHoursMetaDTO> {
    metaCache = null;
    return loadMeta(true);
  },

  async getAcademicYears(): Promise<AcademicYearDTO[]> {
    const meta = await loadMeta();
    return meta.academic_years;
  },

  async getActivityKinds(): Promise<ActivityKindDTO[]> {
    const meta = await loadMeta();
    return meta.activity_kinds;
  },

  async getActivityTypes(): Promise<ActivityTypeDTO[]> {
    const meta = await loadMeta();
    return meta.activity_types;
  },

  async listHourRules(params: {
    academic_year_id?: number;
    status?: string;
    q?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<HourRuleDerivedDTO>> {
    return listHourRulesApi(params);
  },

  async createHourRule(payload: UpsertHourRulePayloadDTO): Promise<void> {
    await createHourRuleApi({
      ...payload,
      hours_per_occurrence: null,
      principal_fraction: null,
      others_fraction_total: null,
      max_occurrences_per_year: null,
    });
  },

  async updateHourRule(
    id: number,
    payload: UpsertHourRulePayloadDTO
  ): Promise<void> {
    await updateHourRuleApi(id, {
      ...payload,
      hours_per_occurrence: null,
      principal_fraction: null,
      others_fraction_total: null,
      max_occurrences_per_year: null,
    });
  },

  async setHourRuleActive(id: number, is_active: boolean): Promise<void> {
    await updateHourRuleStatusApi(id, is_active);
  },

  async listWorkloadQuotas(params: {
    academic_year_id?: number;
    status?: string;
    q?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<WorkloadQuotaDerivedDTO>> {
    return listWorkloadQuotasApi(params);
  },

  async createWorkloadQuota(payload: {
    academic_year_id: number;
    required_hours: number;
    notes?: string | null;
  }): Promise<void> {
    await createWorkloadQuotaApi(payload);
  },

  async updateWorkloadQuota(
    id: number,
    payload: { required_hours: number; notes?: string | null }
  ): Promise<void> {
    await updateWorkloadQuotaApi(id, payload);
  },

  async listAcademicYears(params: {
    status?: string;
    q?: string;
    page?: number;
    per_page?: number;
  }): Promise<ListResponseDTO<AcademicYearDerivedDTO>> {
    return listAcademicYearsApi(params);
  },

  async createAcademicYear(payload: {
    code: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
  }): Promise<void> {
    await createAcademicYearApi(payload);
  },

  async updateAcademicYear(
    id: number,
    payload: { code: string; start_date: string; end_date: string; is_active: boolean }
  ): Promise<void> {
    await updateAcademicYearApi(id, payload);
  },

  async applyAcademicYear(id: number): Promise<void> {
    await applyAcademicYearApi(id);
  },
};
