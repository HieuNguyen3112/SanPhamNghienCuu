import type {
  AcademicYearDTO,
  ActivityKindDTO,
  ActivityTypeDTO,
  HourRuleDerivedDTO,
  WorkloadQuotaRuleDerivedDTO,
  AcademicYearPeriodDerivedDTO,
  UpsertHourRulePayloadDTO,
} from "../contracts/researchHoursCatalog.contract";
import { researchHoursCatalogMock } from "../mock-data/researchHoursCatalog.mock";

const USE_MOCK = (import.meta.env.VITE_USE_MOCK ?? "true") === "true";

function delay(ms: number) {
  return new Promise<void>((resolve) => setTimeout(resolve, ms));
}

/**
 * TODO: Replace mock with real API calls.
 * - hour_rules: GET/POST/PUT endpoints needed
 * - workload quota rules: schema & endpoints missing (P0)
 * - academic year periods: schema & endpoints missing (P0)
 */
export const researchHoursCatalogService = {
  async getAcademicYears(): Promise<AcademicYearDTO[]> {
    if (!USE_MOCK) {
      // TODO endpoint
      // const { data } = await http.get<AcademicYearDTO[]>("/api/academic-years");
      // return data;
      throw new Error("TODO: getAcademicYears endpoint");
    }
    await delay(180);
    return researchHoursCatalogMock.academicYears();
  },

  async getActivityKinds(): Promise<ActivityKindDTO[]> {
    if (!USE_MOCK) throw new Error("TODO: getActivityKinds endpoint");
    await delay(120);
    return researchHoursCatalogMock.activityKinds();
  },

  async getActivityTypes(): Promise<ActivityTypeDTO[]> {
    if (!USE_MOCK) throw new Error("TODO: getActivityTypes endpoint");
    await delay(120);
    return researchHoursCatalogMock.activityTypes();
  },

  async getHourRules(): Promise<HourRuleDerivedDTO[]> {
    if (!USE_MOCK) throw new Error("TODO: getHourRules endpoint");
    await delay(220);
    return researchHoursCatalogMock.hourRules();
  },

  async upsertHourRule(_payload: UpsertHourRulePayloadDTO): Promise<void> {
    if (!USE_MOCK) throw new Error("TODO: upsertHourRule endpoint");
    // mock: no persistence, just delay
    await delay(260);
  },

  async setHourRuleActive(_id: number, _is_active: boolean): Promise<void> {
    if (!USE_MOCK) throw new Error("TODO: setHourRuleActive endpoint");
    await delay(200);
  },

  async getQuotaRules(): Promise<WorkloadQuotaRuleDerivedDTO[]> {
    if (!USE_MOCK) throw new Error("TODO(P0): quota rules schema+endpoint");
    await delay(200);
    return researchHoursCatalogMock.quotaRules();
  },

  async upsertQuotaRule(): Promise<void> {
    if (!USE_MOCK) throw new Error("TODO(P0): upsertQuotaRule endpoint");
    await delay(260);
  },

  async setQuotaRuleActive(): Promise<void> {
    if (!USE_MOCK) throw new Error("TODO(P0): setQuotaRuleActive endpoint");
    await delay(200);
  },

  async getAcademicYearPeriods(): Promise<AcademicYearPeriodDerivedDTO[]> {
    if (!USE_MOCK)
      throw new Error("TODO(P0): academic year periods schema+endpoint");
    await delay(200);
    return researchHoursCatalogMock.yearPeriods();
  },

  async upsertAcademicYear(): Promise<void> {
    if (!USE_MOCK) throw new Error("TODO: upsertAcademicYear endpoint");
    await delay(260);
  },

  async setAcademicYearActive(): Promise<void> {
    if (!USE_MOCK) throw new Error("TODO: setAcademicYearActive endpoint");
    await delay(220);
  },
};
