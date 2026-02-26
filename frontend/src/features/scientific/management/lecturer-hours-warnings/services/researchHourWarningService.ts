import http from "@/lib/http";
import type {
  AcademicYearIdentifier,
  FacultyIdentifier,
  LecturerResearchHourWarningOverviewDTO,
  ResearchHourWarningFilterDTO,
  RequestWarningNotificationDTO,
} from "../contracts/lecturerResearchHourWarning.contract";

export interface ResearchHourWarningService {
  getOverview(
    filter: ResearchHourWarningFilterDTO
  ): Promise<LecturerResearchHourWarningOverviewDTO>;
  requestWarningNotification(
    payload: RequestWarningNotificationDTO
  ): Promise<void>;
}

function normalizeParams(params: Record<string, string>) {
  const cleaned: Record<string, string> = {};
  Object.entries(params).forEach(([key, value]) => {
    if (!value || value.trim() === "") return;
    cleaned[key] = value;
  });
  return cleaned;
}

async function fetchOverview(
  filter: ResearchHourWarningFilterDTO,
  basePath: string
) {
  const params = normalizeParams({
    faculty_identifier: filter.faculty_identifier,
    academic_year_identifier: filter.academic_year_identifier,
    severity_filter: filter.severity_filter,
    notification_state_filter: filter.notification_state_filter,
    keyword: filter.keyword,
  });

  const { data } = await http.get<{ data: LecturerResearchHourWarningOverviewDTO }>(
    basePath,
    { params }
  );

  return data.data;
}

async function sendWarning(
  payload: RequestWarningNotificationDTO,
  basePath: string
) {
  await http.post(`${basePath}/${payload.lecturer_identifier}/send`, payload);
}

export function createUniversityResearchHourWarningService(): ResearchHourWarningService {
  const basePath = "/api/admin/hours/warnings";
  return {
    async getOverview(filter) {
      return fetchOverview(filter, basePath);
    },
    async requestWarningNotification(payload) {
      await sendWarning(payload, basePath);
    },
  };
}

export function createFacultyResearchHourWarningService(): ResearchHourWarningService {
  const basePath = "/api/faculty/hours/warnings";
  return {
    async getOverview(filter) {
      return fetchOverview(filter, basePath);
    },
    async requestWarningNotification(payload) {
      await sendWarning(payload, basePath);
    },
  };
}
