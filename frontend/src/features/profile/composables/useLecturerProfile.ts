import { ref } from "vue";
import {
  fetchAcademicRanks,
  fetchDegrees,
  fetchScientificProfile,
  fetchTrainingHistories,
  syncTrainingHistories,
  updateAcademicTitles,
  updateLanguages,
  updateProfileContact,
  updateResearchAreas,
  type EducationDTO,
  type LanguageDTO,
  type LookupItemDTO,
  type ResearchAreaDTO,
  type ResearchWorkItemDTO,
  type UpdateLanguagesPayload,
  type SyncTrainingHistoriesPayload,
  type ScientificProfilePayload,
} from "../services/lecturerProfileService";

export type Gender = "male" | "female" | "other";
export type EmploymentType = "full_time" | "visiting";
export type WorkStatus = "active" | "on_leave" | "retired" | "resigned";

export interface LecturerPersonalInfo {
  lecturerCode: string;
  fullName: string;
  birthDate: string | null; // YYYY-MM-DD
  gender: Gender;

  departmentName: string; // read-only
  jobTitle: string;

  employmentType: EmploymentType;
  workStatus: WorkStatus;

  avatarUrl: string | null;
}

export interface LecturerContactInfo {
  officialEmail: string; // read-only
  personalEmail: string;
  phone: string;
  address: string;
  website: string;
  googleScholar: string;
  orcid: string;
}

export interface LanguageRow {
  id: string;
  language: string;
  level: string;
}

export interface LecturerAcademicProfile {
  highestQualification: string;
  major: string;
  academicDegree: string; // VD: TS/ThS...
  academicRank: string; // VD: PGS/GS...
  degreeYear: number | null;
  rankYear: number | null;

  primaryArea: string;
  secondaryArea: string;
  keywords: string[];

  languages: LanguageRow[];
}

export type ResearchWorkKind = "paper" | "project" | "book" | "conference";
export type ResearchWorkStatus =
  | "DRAFT"
  | "SUBMITTED"
  | "APPROVED"
  | "REJECTED";

export interface ResearchWorkItem {
  id: number;
  kind: ResearchWorkKind;
  title: string;
  metaLine: string; // journal/publisher/level/location...
  typeName: string;
  year: number | null;
  role: string;
  status: ResearchWorkStatus;
}

const emptyPersonalInfo: LecturerPersonalInfo = {
  lecturerCode: "",
  fullName: "",
  birthDate: null,
  gender: "other",
  departmentName: "",
  jobTitle: "",
  employmentType: "full_time",
  workStatus: "active",
  avatarUrl: null,
};

const emptyContactInfo: LecturerContactInfo = {
  officialEmail: "",
  personalEmail: "",
  phone: "",
  address: "",
  website: "",
  googleScholar: "",
  orcid: "",
};

const emptyAcademicProfile: LecturerAcademicProfile = {
  highestQualification: "",
  major: "",
  academicDegree: "",
  academicRank: "",
  degreeYear: null,
  rankYear: null,
  primaryArea: "",
  secondaryArea: "",
  keywords: [],
  languages: [],
};

function normalizeGender(value?: string | null): Gender {
  const raw = (value ?? "").trim().toLowerCase();
  if (!raw) return "other";
  if (raw === "m") return "male";
  if (raw === "f") return "female";
  if (raw.includes("nữ")) return "female";
  const tokens = raw.split(/[^a-z]+/).filter(Boolean);
  if (tokens.includes("female") || tokens.includes("nu")) return "female";
  if (tokens.includes("male") || tokens.includes("nam")) return "male";
  return "other";
}

function normalizeGenderToBackend(value: Gender): string {
  if (value === "male") return "Male";
  if (value === "female") return "Female";
  return "Other";
}

function normalizeEmploymentType(value?: string | null): EmploymentType {
  const raw = (value ?? "").toLowerCase();
  if (["permanent", "full", "bien", "co huu"].some((x) => raw.includes(x))) {
    return "full_time";
  }
  if (["visiting", "contract", "thin"].some((x) => raw.includes(x))) {
    return "visiting";
  }
  return "full_time";
}

function normalizeWorkStatus(
  value?: string | null,
  active?: boolean | null,
): WorkStatus {
  const raw = (value ?? "").toLowerCase();
  if (raw.includes("on_leave")) return "on_leave";
  if (raw.includes("retired")) return "retired";
  if (raw.includes("resigned") || raw.includes("inactive")) return "resigned";
  if (active === false) return "resigned";
  return "active";
}

function parseYear(value?: string | number | null): number | null {
  if (value == null) return null;
  if (typeof value === "number" && Number.isFinite(value)) return value;
  const str = String(value);
  if (str.length < 4) return null;
  const year = Number(str.slice(0, 4));
  return Number.isFinite(year) ? year : null;
}

function normalizeOptionalText(value?: string | null): string | null {
  const normalized = (value ?? "").trim();
  return normalized.length > 0 ? normalized : null;
}

function yearToDateString(value: number | null): string | null {
  if (value == null || !Number.isFinite(value)) return null;
  const year = Math.floor(value);
  if (year < 1000 || year > 9999) return null;
  return `${year}-01-01`;
}

function toTrainingHistorySyncItem(item: EducationDTO) {
  return {
    id: item.id,
    degree_id: item.degree_id ?? null,
    degree_title: item.degree_title ?? null,
    major: item.major ?? null,
    institution: item.institution ?? null,
    country: item.country ?? null,
    city: item.city ?? null,
    start_date: item.start_date ?? null,
    end_date: item.end_date ?? null,
    is_current: item.is_current ?? null,
    training_form: item.training_form ?? null,
    funding_source: item.funding_source ?? null,
    certificate_no: item.certificate_no ?? null,
    notes: item.notes ?? null,
  };
}

function parseResearchAreaString(value?: string | null): string[] {
  if (!value) return [];
  return value
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function mapResearchFields(
  fields: ResearchAreaDTO[] | undefined,
  fallback: string | null | undefined,
) {
  const items = (fields ?? [])
    .map((f) => ({ name: f.name?.trim(), type: f.type }))
    .filter((f) => f.name);

  const primary =
    items.find((i) => i.type === "PRIMARY")?.name ??
    parseResearchAreaString(fallback)[0] ??
    "";
  const secondaryCandidates = items
    .filter((i) => i.type === "SECONDARY")
    .map((i) => i.name as string);
  const fallbackParts = parseResearchAreaString(fallback);
  const secondary = secondaryCandidates[0] ?? fallbackParts[1] ?? "";
  let keywords = secondaryCandidates.slice(1);

  if (keywords.length === 0 && fallbackParts.length > 2) {
    keywords = fallbackParts.slice(2);
  }

  return { primary, secondary, keywords };
}

function mapLanguageLevel(item: LanguageDTO): string {
  const base = item.certificate_name || item.level || "";
  const detail = item.certificate_score || item.certificate_level || "";
  return [base, detail].filter(Boolean).join(" ");
}

function mapLanguageRows(items?: LanguageDTO[]): LanguageRow[] {
  if (!items) return [];
  return items.map((item) => ({
    id: String(item.id ?? ""),
    language: item.language,
    level: mapLanguageLevel(item),
  }));
}

function mapWorkStatus(value?: string | null): ResearchWorkStatus {
  const raw = (value ?? "").toLowerCase();
  if (raw === "approved") return "APPROVED";
  if (raw === "submitted") return "SUBMITTED";
  if (raw === "rejected") return "REJECTED";
  return "DRAFT";
}

function normalizeLookupValue(value: string): string {
  return value.trim().toLowerCase();
}

function matchLookupId(value: string, items: LookupItemDTO[]): number | null {
  const key = normalizeLookupValue(value);
  if (!key) return null;
  const matched = items.find((item) => {
    const nameKey = normalizeLookupValue(item.name ?? "");
    const codeKey = normalizeLookupValue(item.code ?? "");
    return key === nameKey || key === codeKey;
  });
  return matched ? matched.id : null;
}

function mapResearchWorks(items?: ResearchWorkItemDTO[]): ResearchWorkItem[] {
  if (!items) return [];
  return items
    .map((item) => {
      const kind = item.kind_code as ResearchWorkKind | undefined;
      if (!kind || !["paper", "project", "book", "conference"].includes(kind)) {
        return null;
      }

      return {
        id: item.activity_id,
        kind,
        title: item.title,
        metaLine: item.info || item.venue || item.location || "",
        typeName: item.type_name || item.kind_name || "",
        year: item.work_year ?? parseYear(item.approved_at),
        role: item.member_role_name || "",
        status: mapWorkStatus(item.status_code),
      } as ResearchWorkItem;
    })
    .filter((item): item is ResearchWorkItem => item !== null);
}

function applyProfilePayload(
  payload: ScientificProfilePayload,
  personalInfoRef: { value: LecturerPersonalInfo },
  contactInfoRef: { value: LecturerContactInfo },
  academicProfileRef: { value: LecturerAcademicProfile },
  worksRef: { value: ResearchWorkItem[] },
) {
  const summary = payload.scientific_profile;
  const personal = summary?.personal_info ?? {};
  const contact = summary?.contact_info ?? {};
  const academic = summary?.academic_info ?? {};
  const latestEducation =
    summary?.latest_education ?? payload.latest_education ?? null;
  const latestWork =
    summary?.latest_work_history ?? payload.latest_work_history;

  const degreeName =
    academic.degree_name ?? payload.lecturer?.degree_name ?? "";
  const rankName =
    academic.academic_rank_name ?? payload.lecturer?.academic_rank_name ?? "";

  const { primary, secondary, keywords } = mapResearchFields(
    summary?.research_fields ?? payload.research_areas,
    academic.research_area ?? payload.profile?.research_area,
  );

  personalInfoRef.value = {
    lecturerCode: personal.lecturer_code ?? payload.lecturer?.code ?? "",
    fullName: personal.full_name ?? payload.lecturer?.full_name ?? "",
    birthDate: personal.date_of_birth ?? payload.profile?.date_of_birth ?? null,
    gender: normalizeGender(personal.gender ?? payload.profile?.gender),
    departmentName:
      personal.department_name ?? payload.lecturer?.department_name ?? "",
    jobTitle:
      personal.current_position ?? payload.profile?.current_position ?? "",
    employmentType: normalizeEmploymentType(
      personal.staff_type ?? latestWork?.employment_type,
    ),
    workStatus: normalizeWorkStatus(personal.work_status, personal.active),
    avatarUrl: null,
  };

  contactInfoRef.value = {
    officialEmail:
      contact.work_email ??
      payload.lecturer?.email ??
      payload.user?.email ??
      "",
    personalEmail:
      contact.personal_email ?? payload.profile?.personal_email ?? "",
    phone: contact.phone ?? payload.lecturer?.phone ?? "",
    address: contact.address ?? payload.profile?.address ?? "",
    website:
      contact.personal_website ?? payload.profile?.personal_website ?? "",
    googleScholar:
      contact.google_scholar_profile ??
      payload.profile?.google_scholar_profile ??
      "",
    orcid: contact.orcid_id ?? payload.profile?.orcid_id ?? "",
  };

  academicProfileRef.value = {
    highestQualification:
      latestEducation?.degree_title ??
      latestEducation?.degree_name ??
      degreeName,
    major: latestEducation?.major ?? "",
    academicDegree: degreeName,
    academicRank: rankName,
    degreeYear: parseYear(
      latestEducation?.end_date ?? latestEducation?.start_date,
    ),
    rankYear: null,
    primaryArea: primary,
    secondaryArea: secondary,
    keywords,
    languages: mapLanguageRows(summary?.languages ?? payload.languages),
  };

  worksRef.value = mapResearchWorks(payload.research_works?.items ?? []);
}

function buildResearchAreaItems(profile: LecturerAcademicProfile): string[] {
  const items = [
    profile.primaryArea,
    profile.secondaryArea,
    ...profile.keywords,
  ]
    .map((item) => item.trim())
    .filter(Boolean);
  return Array.from(new Set(items));
}

function buildLanguagePayload(
  rows: LanguageRow[],
): UpdateLanguagesPayload["items"] {
  return rows
    .map((row) => {
      const language = row.language.trim().slice(0, 100);
      const level = row.level.trim().slice(0, 100);
      const idNum = Number(row.id);

      return {
        id: Number.isInteger(idNum) && idNum > 0 ? idNum : undefined,
        language,
        level,
      };
    })
    .filter((row) => row.language.length > 0 && row.level.length > 0);
}

function resolveErrorMessage(error: unknown, fallback: string): string {
  const anyError = error as { response?: { data?: { message?: string } } };
  return (
    anyError?.response?.data?.message || (error as Error)?.message || fallback
  );
}

export function useLecturerProfile() {
  const loading = ref(false);

  const personalInfo = ref<LecturerPersonalInfo>({ ...emptyPersonalInfo });
  const contactInfo = ref<LecturerContactInfo>({ ...emptyContactInfo });
  const academicProfile = ref<LecturerAcademicProfile>({
    ...emptyAcademicProfile,
  });
  const works = ref<ResearchWorkItem[]>([]);
  const degreeOptions = ref<LookupItemDTO[]>([]);
  const rankOptions = ref<LookupItemDTO[]>([]);

  async function syncLatestEducation(
    next: LecturerAcademicProfile,
    degreeIdToPersist?: number | null,
    fallbackDegreeId?: number | null,
  ) {
    const histories = await fetchTrainingHistories();
    const items: SyncTrainingHistoriesPayload["items"] = histories.map((item) =>
      toTrainingHistorySyncItem(item),
    );
    for (const item of items) {
      if (!item.institution || !item.institution.trim()) {
        item.institution = "Chưa cập nhật";
      }
    }
    const resolvedDegreeId =
      degreeIdToPersist !== undefined ? degreeIdToPersist : fallbackDegreeId;

    if (items.length === 0) {
      items.push({
        degree_id: resolvedDegreeId ?? null,
        degree_title: normalizeOptionalText(next.highestQualification),
        major: normalizeOptionalText(next.major),
        institution: "Chưa cập nhật",
        end_date: yearToDateString(next.degreeYear),
      });
    } else {
      const latest = items[0];
      if (latest) {
        if (!latest.institution || !latest.institution.trim()) {
          latest.institution = "Chưa cập nhật";
        }
        if (degreeIdToPersist !== undefined) {
          latest.degree_id = resolvedDegreeId ?? null;
        }
        latest.degree_title = normalizeOptionalText(next.highestQualification);
        latest.major = normalizeOptionalText(next.major);
        latest.end_date = yearToDateString(next.degreeYear);
      }
    }

    await syncTrainingHistories({ items });
  }

  async function loadProfile() {
    loading.value = true;
    try {
      const [payload, degrees, ranks] = await Promise.all([
        fetchScientificProfile(),
        fetchDegrees(),
        fetchAcademicRanks(),
      ]);
      degreeOptions.value = degrees;
      rankOptions.value = ranks;
      applyProfilePayload(
        payload,
        personalInfo,
        contactInfo,
        academicProfile,
        works,
      );
    } catch (error) {
      throw new Error(resolveErrorMessage(error, "Unable to load profile"));
    } finally {
      loading.value = false;
    }
  }

  async function updatePersonalInfo(next: LecturerPersonalInfo) {
    loading.value = true;
    try {
      const payload = await updateProfileContact({
        full_name: next.fullName,
        date_of_birth: next.birthDate,
        gender: normalizeGenderToBackend(next.gender),
        current_position: next.jobTitle,
        staff_type: next.employmentType,
        work_status: next.workStatus,
      });
      applyProfilePayload(
        payload,
        personalInfo,
        contactInfo,
        academicProfile,
        works,
      );
    } catch (error) {
      throw new Error(
        resolveErrorMessage(error, "Unable to update personal info"),
      );
    } finally {
      loading.value = false;
    }
  }

  async function updateContactInfo(next: LecturerContactInfo) {
    loading.value = true;
    try {
      const payload = await updateProfileContact({
        personal_email: next.personalEmail || null,
        phone: next.phone || null,
        address: next.address || null,
        personal_website: next.website || null,
        google_scholar_profile: next.googleScholar || null,
        orcid_id: next.orcid || null,
      });
      applyProfilePayload(
        payload,
        personalInfo,
        contactInfo,
        academicProfile,
        works,
      );
    } catch (error) {
      throw new Error(
        resolveErrorMessage(error, "Unable to update contact info"),
      );
    } finally {
      loading.value = false;
    }
  }

  async function updateAcademicProfile(next: LecturerAcademicProfile) {
    loading.value = true;
    try {
      const prev = academicProfile.value;
      const degreeInput = next.academicDegree.trim();
      const rankInput = next.academicRank.trim();
      const degreeChanged = degreeInput !== prev.academicDegree.trim();
      const rankChanged = rankInput !== prev.academicRank.trim();
      const [degrees, ranks] = await Promise.all([
        degreeOptions.value.length > 0
          ? Promise.resolve(degreeOptions.value)
          : fetchDegrees(),
        rankOptions.value.length > 0
          ? Promise.resolve(rankOptions.value)
          : fetchAcademicRanks(),
      ]);
      degreeOptions.value = degrees;
      rankOptions.value = ranks;
      const matchedDegreeId = degreeInput
        ? matchLookupId(degreeInput, degrees)
        : null;
      const matchedRankId = rankInput ? matchLookupId(rankInput, ranks) : null;

      if (degreeChanged && degreeInput && !matchedDegreeId) {
        throw new Error("Khong tim thay hoc vi trong danh muc.");
      }
      if (rankChanged && rankInput && !matchedRankId) {
        throw new Error("Khong tim thay hoc ham trong danh muc.");
      }

      if (degreeChanged || rankChanged) {
        const payload = await updateAcademicTitles({
          items: [
            {
              degree_id: matchedDegreeId ?? null,
              academic_rank_id: matchedRankId ?? null,
            },
          ],
        });
        applyProfilePayload(
          payload,
          personalInfo,
          contactInfo,
          academicProfile,
          works,
        );
      }

      await syncLatestEducation(
        next,
        degreeChanged ? (matchedDegreeId ?? null) : undefined,
        matchedDegreeId,
      );

      const researchItems = buildResearchAreaItems(next);
      const payload = await updateResearchAreas({ items: researchItems });
      const languagePayload = buildLanguagePayload(next.languages);
      const languages = await updateLanguages({ items: languagePayload });

      applyProfilePayload(
        payload,
        personalInfo,
        contactInfo,
        academicProfile,
        works,
      );
      academicProfile.value = {
        ...academicProfile.value,
        languages: mapLanguageRows(languages),
      };
    } catch (error) {
      throw new Error(
        resolveErrorMessage(error, "Unable to update academic profile"),
      );
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    personalInfo,
    contactInfo,
    academicProfile,
    works,
    degreeOptions,
    rankOptions,

    loadProfile,
    updatePersonalInfo,
    updateContactInfo,
    updateAcademicProfile,
  };
}
