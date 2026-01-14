export type HourResearchStatusCode = "all" | "met" | "not_met";

export interface HourResearchFacultyOption {
  id: number;
  name: string;
}

export interface HourResearchAcademicYearOption {
  id: number;
  code: string;
  isActive: boolean;
}

export interface HourResearchStatusOption {
  code: HourResearchStatusCode;
  label: string;
}

export interface HourResearchReportFiltersResponse {
  faculties: HourResearchFacultyOption[];
  academicYears: HourResearchAcademicYearOption[];
  statusOptions: HourResearchStatusOption[];
}

export interface HourResearchReportKpis {
  lecturerCount: number;
  totalHours: number;
  avgHours: number;
  metCount: number;
  notMetCount: number;
  complianceRate: number;
}

export interface HourResearchReportCharts {
  hoursByFaculty: {
    labels: string[];
    values: number[];
  };
  statusDistribution: {
    labels: string[];
    values: number[];
  };
  hoursByYear: {
    labels: string[];
    values: number[];
  };
}

export interface HourResearchReportRow {
  lecturerId: number;
  lecturerName: string;
  facultyId: number | null;
  facultyName: string | null;
  academicYearId: number | null;
  academicYearCode: string | null;
  totalHours: number;
  requiredHours: number;
  status: "met" | "not_met";
}

export interface HourResearchReportPagination {
  page: number;
  perPage: number;
  total: number;
  lastPage: number;
}

export interface HourResearchReportTable {
  items: HourResearchReportRow[];
  pagination: HourResearchReportPagination;
}
