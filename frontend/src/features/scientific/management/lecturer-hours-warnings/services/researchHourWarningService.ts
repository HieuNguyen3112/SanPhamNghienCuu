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

async function fetchOverview(filter: ResearchHourWarningFilterDTO) {
  const params = normalizeParams({
    faculty_identifier: filter.faculty_identifier,
    academic_year_identifier: filter.academic_year_identifier,
    severity_filter: filter.severity_filter,
    notification_state_filter: filter.notification_state_filter,
    keyword: filter.keyword,
  });

  const { data } = await http.get<{ data: LecturerResearchHourWarningOverviewDTO }>(
    "/api/admin/hours/warnings",
    { params }
  );

  return data.data;
}

async function sendWarning(payload: RequestWarningNotificationDTO) {
  await http.post(
    `/api/admin/hours/warnings/${payload.lecturer_identifier}/send`,
    payload
  );
}

export function createUniversityResearchHourWarningService(): ResearchHourWarningService {
  return {
    async getOverview(filter) {
      return fetchOverview(filter);
    },
    async requestWarningNotification(payload) {
      await sendWarning(payload);
    },
  };
}

export function createFacultyResearchHourWarningService(params: {
  facultyIdentifierLocked: FacultyIdentifier;
}): ResearchHourWarningService {
  return {
    async getOverview(filter) {
      const normalizedFilter: ResearchHourWarningFilterDTO = {
        ...filter,
        faculty_identifier: params.facultyIdentifierLocked,
      };

      const dto = await fetchOverview(normalizedFilter);
      const locked = params.facultyIdentifierLocked;

      const facultyOptionList = dto.faculty_option_list.filter(
        (f) =>
          f.faculty_identifier === locked || f.faculty_short_name === locked
      );

      return {
        ...dto,
        faculty_option_list:
          facultyOptionList.length > 0 ? facultyOptionList : dto.faculty_option_list,
      };
    },
    async requestWarningNotification(payload) {
      await sendWarning(payload);
    },
  };
}
