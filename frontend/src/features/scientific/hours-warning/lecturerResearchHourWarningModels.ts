export type ResearchHourShortfallSeverityFilterCondition =
  | "ALL"
  | "LIGHT"
  | "MEDIUM"
  | "SEVERE";

export type LecturerResearchHourWarningNotificationRequestState =
  | "NOT_REQUESTED"
  | "REQUESTED";

export interface FacultyOption {
  facultyIdentifier: string;
  facultyDisplayName: string;
}

export interface LecturerResearchHourWarningEntry {
  lecturerIdentifier: string;
  lecturerDisplayName: string;
  facultyIdentifier: string;
  facultyDisplayName: string;
  academicYear: string;

  // REQUIRED VARIABLES
  minimumRequiredResearchHours: number;
  currentLecturerResearchHours: number;
  remainingResearchHoursToMeetStandard: number;

  // Chi tiết phục vụ đối soát
  researchHoursFromJournalArticles: number;
  researchHoursFromResearchProjects: number;
  researchHoursFromConferenceProceedings: number;
  researchHoursFromStudentSupervision: number;
  academicNotes: string | null;

  // Theo dõi việc đã gửi nhắc nhở để audit quy trình
  lecturerResearchHourWarningNotificationRequestState: LecturerResearchHourWarningNotificationRequestState;
  lecturerResearchHourWarningNotificationRequestedAtDateTimeString:
    | string
    | null;
}

export interface LecturerResearchHourWarningResponse {
  facultyOptions: FacultyOption[];
  academicYearOptions: string[];
  lecturerResearchHourWarningEntries: LecturerResearchHourWarningEntry[];
}
