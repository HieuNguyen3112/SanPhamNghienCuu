import http, { ensureCsrfCookie } from "@/lib/http";

export interface ScientificProfileResponse {
  data: ScientificProfilePayload;
}

export interface ScientificProfilePayload {
  user?: {
    id?: number;
    name?: string | null;
    email?: string | null;
    roles?: string[];
    backend_roles?: string[];
  };
  lecturer?: {
    id?: number;
    code?: string | null;
    full_name?: string | null;
    email?: string | null;
    phone?: string | null;
    department_id?: number | null;
    department_name?: string | null;
    degree_id?: number | null;
    degree_name?: string | null;
    academic_rank_id?: number | null;
    academic_rank_name?: string | null;
    active?: boolean | null;
  };
  profile?: {
    gender?: string | null;
    date_of_birth?: string | null;
    place_of_birth?: string | null;
    ethnicity?: string | null;
    hometown?: string | null;
    personal_email?: string | null;
    alternate_phone?: string | null;
    address?: string | null;
    current_position?: string | null;
    current_unit?: string | null;
    research_area?: string | null;
    teaching_specialization?: string | null;
    orcid_id?: string | null;
    google_scholar_profile?: string | null;
    research_gate_profile?: string | null;
    scopus_id?: string | null;
    publons_id?: string | null;
    personal_website?: string | null;
    academic_portfolio_url?: string | null;
  } | null;
  party_membership?: PartyMembershipDTO | null;
  educations?: EducationDTO[];
  latest_education?: EducationDTO | null;
  languages?: LanguageDTO[];
  research_areas?: ResearchAreaDTO[];
  work_histories?: WorkHistoryDTO[];
  latest_work_history?: WorkHistoryDTO | null;
  scientific_profile?: ScientificProfileSummaryDTO;
  research_works?: ResearchWorksDTO;
}

export interface PartyMembershipDTO {
  is_member?: boolean;
  membership_no?: string | null;
  joined_at?: string | null;
  official_at?: string | null;
  joining_place?: string | null;
  current_branch?: string | null;
  position?: string | null;
  status?: string | null;
  notes?: string | null;
}

export interface EducationDTO {
  id: number;
  degree_id?: number | null;
  degree_name?: string | null;
  degree_title?: string | null;
  major?: string | null;
  institution?: string | null;
  country?: string | null;
  city?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  is_current?: boolean | null;
  training_form?: string | null;
  funding_source?: string | null;
  certificate_no?: string | null;
  notes?: string | null;
}

export interface WorkHistoryDTO {
  id: number;
  organization?: string | null;
  position?: string | null;
  department?: string | null;
  workplace?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  is_current?: boolean | null;
  employment_type?: string | null;
  reason_for_leaving?: string | null;
  notes?: string | null;
}

export interface LanguageDTO {
  id: number;
  language: string;
  level?: string | null;
  is_native?: boolean | null;
  certificate_name?: string | null;
  certificate_level?: string | null;
  certificate_score?: string | null;
  certificate_issuer?: string | null;
  issue_date?: string | null;
  expire_date?: string | null;
  note?: string | null;
  attachment_name?: string | null;
}

export interface ResearchAreaDTO {
  id: number;
  name: string;
  type?: "PRIMARY" | "SECONDARY" | string | null;
  start_year?: number | null;
  keywords?: string | null;
  description?: string | null;
}

export interface ScientificProfileSummaryDTO {
  personal_info?: {
    lecturer_id?: number;
    lecturer_code?: string | null;
    full_name?: string | null;
    gender?: string | null;
    date_of_birth?: string | null;
    place_of_birth?: string | null;
    ethnicity?: string | null;
    hometown?: string | null;
    current_position?: string | null;
    current_unit?: string | null;
    department_id?: number | null;
    department_name?: string | null;
    staff_type?: string | null;
    work_status?: string | null;
    active?: boolean | null;
  };
  contact_info?: {
    work_email?: string | null;
    personal_email?: string | null;
    phone?: string | null;
    alternate_phone?: string | null;
    address?: string | null;
    personal_website?: string | null;
    google_scholar_profile?: string | null;
    research_gate_profile?: string | null;
    orcid_id?: string | null;
    scopus_id?: string | null;
    publons_id?: string | null;
    academic_portfolio_url?: string | null;
  };
  academic_info?: {
    degree_id?: number | null;
    degree_name?: string | null;
    academic_rank_id?: number | null;
    academic_rank_name?: string | null;
    teaching_specialization?: string | null;
    research_area?: string | null;
  };
  research_fields?: ResearchAreaDTO[];
  languages?: LanguageDTO[];
  work_histories?: WorkHistoryDTO[];
  latest_work_history?: WorkHistoryDTO | null;
  educations?: EducationDTO[];
  latest_education?: EducationDTO | null;
  party_membership?: PartyMembershipDTO | null;
}

export interface ResearchWorksDTO {
  counts_by_kind?: Record<string, number>;
  items?: ResearchWorkItemDTO[];
  items_by_kind?: Record<string, ResearchWorkItemDTO[]>;
}

export interface ResearchWorkItemDTO {
  activity_id: number;
  activity_code?: string | null;
  title: string;
  kind_code?: string | null;
  kind_name?: string | null;
  type_code?: string | null;
  type_name?: string | null;
  academic_year_code?: string | null;
  status_code?: string | null;
  status_name?: string | null;
  work_year?: number | null;
  venue?: string | null;
  info?: string | null;
  location?: string | null;
  member_role_id?: number | null;
  member_role_name?: string | null;
  approved_at?: string | null;
}

export interface LookupItemDTO {
  id: number;
  code?: string | null;
  name: string;
}

export interface UpdateContactPayload {
  full_name?: string;
  phone?: string | null;
  department_id?: number | null;
  degree_id?: number | null;
  academic_rank_id?: number | null;
  gender?: string | null;
  date_of_birth?: string | null;
  place_of_birth?: string | null;
  ethnicity?: string | null;
  hometown?: string | null;
  personal_email?: string | null;
  alternate_phone?: string | null;
  address?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  emergency_contact_relation?: string | null;
  current_position?: string | null;
  current_unit?: string | null;
  staff_type?: string | null;
  work_status?: string | null;
  research_area?: string | null;
  teaching_specialization?: string | null;
  orcid_id?: string | null;
  google_scholar_profile?: string | null;
  research_gate_profile?: string | null;
  scopus_id?: string | null;
  publons_id?: string | null;
  personal_website?: string | null;
  academic_portfolio_url?: string | null;
}

export interface UpdateResearchAreasPayload {
  items: Array<string | { name?: string; value?: string; label?: string }>;
  teaching_specialization?: string | null;
}

export interface UpdateLanguagesPayload {
  items: Array<{
    id?: number | null;
    language: string;
    level: string;
    certificate_name?: string | null;
    certificate_level?: string | null;
    certificate_score?: string | null;
    certificate_issuer?: string | null;
    issue_date?: string | null;
    expire_date?: string | null;
    note?: string | null;
    is_native?: boolean;
  }>;
}

export interface UpdateAcademicTitlesPayload {
  items: Array<{
    degree_id?: number | null;
    academic_rank_id?: number | null;
  }>;
}

export interface SyncTrainingHistoriesPayload {
  items: Array<{
    id?: number;
    degree_id?: number | null;
    degree_title?: string | null;
    major?: string | null;
    institution?: string | null;
    country?: string | null;
    city?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    is_current?: boolean | null;
    training_form?: string | null;
    funding_source?: string | null;
    certificate_no?: string | null;
    notes?: string | null;
  }>;
}

export async function fetchDegrees(): Promise<LookupItemDTO[]> {
  const { data } = await http.get<{ data: LookupItemDTO[] }>(
    "/api/lookups/degrees",
  );
  return data.data;
}

export async function fetchAcademicRanks(): Promise<LookupItemDTO[]> {
  const { data } = await http.get<{ data: LookupItemDTO[] }>(
    "/api/lookups/academic-ranks",
  );
  return data.data;
}

export async function fetchScientificProfile(): Promise<ScientificProfilePayload> {
  const { data } = await http.get<ScientificProfileResponse>("/api/profile/me");
  return data.data;
}

export async function fetchTrainingHistories(): Promise<EducationDTO[]> {
  const { data } = await http.get<{ data: EducationDTO[] }>(
    "/api/profile/educations",
  );
  return data.data;
}

export async function updateAcademicTitles(
  payload: UpdateAcademicTitlesPayload,
): Promise<ScientificProfilePayload> {
  await ensureCsrfCookie();
  const { data } = await http.put<{ data: ScientificProfilePayload }>(
    "/api/profile/academic-titles",
    payload,
  );
  return data.data;
}

export async function updateProfileContact(
  payload: UpdateContactPayload,
): Promise<ScientificProfilePayload> {
  await ensureCsrfCookie();
  const { data } = await http.put<{ data: ScientificProfilePayload }>(
    "/api/profile/contact",
    payload,
  );
  return data.data;
}

export async function updateResearchAreas(
  payload: UpdateResearchAreasPayload,
): Promise<ScientificProfilePayload> {
  await ensureCsrfCookie();
  const { data } = await http.put<{ data: ScientificProfilePayload }>(
    "/api/profile/research-areas",
    payload,
  );
  return data.data;
}

export async function updateLanguages(
  payload: UpdateLanguagesPayload,
): Promise<LanguageDTO[]> {
  await ensureCsrfCookie();
  const { data } = await http.put<{ data: LanguageDTO[] }>(
    "/api/profile/languages",
    payload,
  );
  return data.data;
}

export async function syncTrainingHistories(
  payload: SyncTrainingHistoriesPayload,
): Promise<EducationDTO[]> {
  await ensureCsrfCookie();
  const { data } = await http.put<{ data: EducationDTO[] }>(
    "/api/profile/educations",
    payload,
  );
  return data.data;
}
