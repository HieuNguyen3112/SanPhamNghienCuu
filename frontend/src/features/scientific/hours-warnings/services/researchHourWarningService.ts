import type {
  AcademicYearIdentifier,
  FacultyIdentifier,
  LecturerResearchHourWarningOverviewDTO,
  ResearchHourWarningFilterDTO,
  RequestWarningNotificationDTO,
  ResearchHourShortfallSeverity,
  ResearchHourWarningReasonCode,
} from "../contracts/lecturerResearchHourWarning.contract";

type ScopeMode = "FACULTY" | "UNIVERSITY";

export interface ResearchHourWarningService {
  getOverview(
    filter: ResearchHourWarningFilterDTO
  ): Promise<LecturerResearchHourWarningOverviewDTO>;
  requestWarningNotification(
    payload: RequestWarningNotificationDTO
  ): Promise<void>;
}

/**
 * ✅ MOCK STORE (in-memory)
 * Sau này thay bằng BE thật: chỉ cần đổi implementation trong create...Service()
 */
function createMockStore(scope: {
  mode: ScopeMode;
  facultyIdentifierLocked?: FacultyIdentifier;
}) {
  const facultyList = [
    {
      faculty_identifier: "FACULTY_IT",
      faculty_short_name: "CNTT",
      faculty_full_name: "Khoa Công nghệ Thông tin",
    },
    {
      faculty_identifier: "FACULTY_ECON",
      faculty_short_name: "Kinh tế",
      faculty_full_name: "Khoa Kinh tế",
    },
    {
      faculty_identifier: "FACULTY_EDU",
      faculty_short_name: "Sư phạm",
      faculty_full_name: "Khoa Sư phạm",
    },
  ] as const;

  const yearList = [
    { academic_year_identifier: "2023-2024", label: "2023–2024" },
    { academic_year_identifier: "2024-2025", label: "2024–2025" },
    { academic_year_identifier: "2025-2026", label: "2025–2026" },
  ] as const;

  type Entry = {
    lecturer_identifier: string;
    lecturer_code: string;
    lecturer_full_name: string;
    faculty_identifier: FacultyIdentifier;
    faculty_short_name: string;
    academic_year_identifier: AcademicYearIdentifier;
    required_hours: number;
    current_hours: number;
    remaining_hours: number;
    severity: ResearchHourShortfallSeverity;
    has_requested_warning: boolean;
    last_requested_at: string | null;
    last_requested_reason_code: ResearchHourWarningReasonCode | null;
    last_requested_reason_note: string | null;
  };

  const warningMap = new Map<
    string,
    { at: string; reason: ResearchHourWarningReasonCode; note: string | null }
  >();

  const seedEntries: Entry[] = [
    {
      lecturer_identifier: "L001",
      lecturer_code: "GV001",
      lecturer_full_name: "Nguyễn Văn A",
      faculty_identifier: "FACULTY_IT",
      faculty_short_name: "CNTT",
      academic_year_identifier: "2024-2025",
      required_hours: 300,
      current_hours: 180,
      remaining_hours: 120,
      severity: "SEVERE",
      has_requested_warning: false,
      last_requested_at: null,
      last_requested_reason_code: null,
      last_requested_reason_note: null,
    },
    {
      lecturer_identifier: "L002",
      lecturer_code: "GV002",
      lecturer_full_name: "Trần Thị B",
      faculty_identifier: "FACULTY_IT",
      faculty_short_name: "CNTT",
      academic_year_identifier: "2024-2025",
      required_hours: 300,
      current_hours: 250,
      remaining_hours: 50,
      severity: "MILD",
      has_requested_warning: true,
      last_requested_at: new Date(
        Date.now() - 1000 * 60 * 60 * 24 * 2
      ).toISOString(),
      last_requested_reason_code: "MISSING_HOURS",
      last_requested_reason_note: null,
    },
    {
      lecturer_identifier: "L003",
      lecturer_code: "GV003",
      lecturer_full_name: "Lê Văn C",
      faculty_identifier: "FACULTY_ECON",
      faculty_short_name: "Kinh tế",
      academic_year_identifier: "2024-2025",
      required_hours: 300,
      current_hours: 210,
      remaining_hours: 90,
      severity: "MODERATE",
      has_requested_warning: false,
      last_requested_at: null,
      last_requested_reason_code: null,
      last_requested_reason_note: null,
    },
    {
      lecturer_identifier: "L004",
      lecturer_code: "GV004",
      lecturer_full_name: "Phạm Thị D",
      faculty_identifier: "FACULTY_EDU",
      faculty_short_name: "Sư phạm",
      academic_year_identifier: "2024-2025",
      required_hours: 300,
      current_hours: 120,
      remaining_hours: 180,
      severity: "SEVERE",
      has_requested_warning: false,
      last_requested_at: null,
      last_requested_reason_code: null,
      last_requested_reason_note: null,
    },
  ];

  function inScopeFaculty(facultyIdentifier: FacultyIdentifier) {
    if (scope.mode === "UNIVERSITY") return true;
    return facultyIdentifier === scope.facultyIdentifierLocked;
  }

  function filterEntries(filter: ResearchHourWarningFilterDTO): Entry[] {
    return seedEntries
      .filter((e) => inScopeFaculty(e.faculty_identifier))
      .filter(
        (e) => e.academic_year_identifier === filter.academic_year_identifier
      )
      .filter((e) => {
        if (filter.faculty_identifier === "ALL_FACULTIES") return true;
        // FACULTY scope vẫn ok: filter này sẽ luôn là faculty locked hoặc ALL
        return e.faculty_identifier === filter.faculty_identifier;
      })
      .filter((e) => {
        if (filter.severity_filter === "ALL") return true;
        return e.severity === filter.severity_filter;
      })
      .filter((e) => {
        if (filter.notification_state_filter === "ALL") return true;
        if (filter.notification_state_filter === "REQUESTED")
          return e.has_requested_warning;
        return !e.has_requested_warning;
      })
      .filter((e) => {
        const kw = filter.keyword.trim().toLowerCase();
        if (!kw) return true;
        const hay = `${e.lecturer_code} ${e.lecturer_full_name}`.toLowerCase();
        return hay.includes(kw);
      })
      .map((e) => {
        const key = `${e.lecturer_identifier}__${e.academic_year_identifier}`;
        const w = warningMap.get(key);
        if (!w) return e;

        return {
          ...e,
          has_requested_warning: true,
          last_requested_at: w.at,
          last_requested_reason_code: w.reason,
          last_requested_reason_note: w.note,
        };
      });
  }

  function computeSummary(entries: Entry[]) {
    const totalLecturers = entries.length;
    const totalRemaining = entries.reduce((s, x) => s + x.remaining_hours, 0);
    return {
      total_lecturers_not_meeting_standard: totalLecturers,
      total_remaining_hours_to_meet_standard: totalRemaining,
      average_remaining_hours_to_meet_standard:
        totalLecturers === 0 ? 0 : Math.round(totalRemaining / totalLecturers),
      minimum_required_hours: 300,
    };
  }

  return {
    async getOverview(
      filter: ResearchHourWarningFilterDTO
    ): Promise<LecturerResearchHourWarningOverviewDTO> {
      const scopedFacultyOptions =
        scope.mode === "UNIVERSITY"
          ? facultyList
          : facultyList.filter(
              (f) => f.faculty_identifier === scope.facultyIdentifierLocked
            );

      const entries = filterEntries(filter);
      return {
        faculty_option_list: scopedFacultyOptions as any,
        academic_year_option_list: yearList as any,
        summary_statistics: computeSummary(entries),
        entry_list: entries,
      };
    },

    async requestWarningNotification(payload: RequestWarningNotificationDTO) {
      const key = `${payload.lecturer_identifier}__${payload.academic_year_identifier}`;
      warningMap.set(key, {
        at: new Date().toISOString(),
        reason: payload.reason_code,
        note: payload.reason_note,
      });
    },
  };
}

/**
 * ✅ UNIVERSITY (toàn trường)
 * TODO BE: gọi endpoint toàn trường (QLKH)
 */
export function createUniversityResearchHourWarningService(): ResearchHourWarningService {
  const store = createMockStore({ mode: "UNIVERSITY" });
  return {
    getOverview: store.getOverview,
    requestWarningNotification: store.requestWarningNotification,
  };
}

/**
 * ✅ FACULTY (BCN khoa)
 * TODO BE: facultyIdentifierLocked lấy từ user session/claims.
 * Khi gọi BE, filter faculty ở server theo user.
 */
export function createFacultyResearchHourWarningService(params: {
  facultyIdentifierLocked: FacultyIdentifier;
}): ResearchHourWarningService {
  const store = createMockStore({
    mode: "FACULTY",
    facultyIdentifierLocked: params.facultyIdentifierLocked,
  });
  return {
    getOverview: store.getOverview,
    requestWarningNotification: store.requestWarningNotification,
  };
}
