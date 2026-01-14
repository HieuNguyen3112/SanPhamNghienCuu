export type GenderValue = string;

export interface FacultyOption {
  id: number;
  name: string;
}

export interface DegreeOption {
  id: number;
  code: string;
  name: string;
}

export interface AcademicRankOption {
  id: number;
  code: string;
  name: string;
}

export interface GenderOption {
  value: GenderValue;
  label: string;
}

export interface LecturerReportFilters {
  facultyId: number | "ALL";
  degreeId: number | "ALL";
  academicRankId: number | "ALL";
  gender: GenderValue | "ALL";
}

export interface LecturerReportRow {
  id: number;
  fullName: string;
  faculty: {
    id: number | null;
    name: string | null;
  };
  gender: GenderValue | null;
  degree: {
    id: number | null;
    code: string | null;
    name: string | null;
  };
  academicRank: {
    id: number | null;
    code: string | null;
    name: string | null;
  };
  seniorityYears: number;
}

export interface LecturerReportSummary {
  totalLecturers: number;
  doctorCount: number;
  masterCount: number;
  bachelorCount: number;
  professorAssociateCount: number;
}

export interface ChartDataset {
  labels: string[];
  values: number[];
}

export interface LecturerReportCharts {
  byFaculty: ChartDataset;
  byDegree: ChartDataset;
  byAcademicRank: ChartDataset;
  byGender: ChartDataset;
}

export interface LecturerReportPagination {
  page: number;
  perPage: number;
  total: number;
  lastPage: number;
}

export interface LecturerReportTable {
  items: LecturerReportRow[];
  pagination: LecturerReportPagination;
}

export interface LecturerReportResponse {
  summary: LecturerReportSummary;
  charts: LecturerReportCharts;
  table: LecturerReportTable;
  appliedFilters: LecturerReportFilters;
}

export interface LecturerReportFiltersResponse {
  faculties: FacultyOption[];
  degrees: DegreeOption[];
  academicRanks: AcademicRankOption[];
  genders: GenderOption[];
}

export type LecturerSortField =
  | "full_name"
  | "faculty_name"
  | "gender"
  | "degree_name"
  | "academic_rank_name"
  | "seniority_years";

export type LecturerSortDirection = "asc" | "desc";

export interface LecturerSortCondition {
  sortFieldIdentifier: LecturerSortField;
  sortDirection: LecturerSortDirection;
}
