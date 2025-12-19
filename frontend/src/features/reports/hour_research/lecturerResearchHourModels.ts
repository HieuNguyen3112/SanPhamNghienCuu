export type ResearchHourStatusFilterCondition =
  | "ALL"
  | "MEETING_RESEARCH_HOUR_STANDARD"
  | "NOT_MEETING_RESEARCH_HOUR_STANDARD";

export interface FacultyOption {
  facultyIdentifier: string;
  facultyDisplayName: string;
}

export interface LecturerResearchHourRecord {
  lecturerIdentifier: string;
  lecturerDisplayName: string;
  facultyIdentifier: string;
  facultyDisplayName: string;
  academicYear: string;
  totalResearchHourCount: number;
  researchHourStandardCount: number;
}

export interface LecturerResearchHourStatisticsResponse {
  facultyOptions: FacultyOption[];
  academicYearOptions: string[];
  lecturerResearchHourRecords: LecturerResearchHourRecord[];
}
