export type ResearchCategoryKey =
  | "ISI"
  | "SCOPUS"
  | "CONFERENCE"
  | "PROJECT"
  | "BOOK";

export interface ResearchDepartmentOption {
  id: number;
  name: string;
  facultyId: number | null;
  facultyName: string | null;
}

export interface ResearchLecturerOption {
  id: number;
  name: string;
  departmentId: number | null;
  departmentName: string | null;
}

export interface ResearchTypeOption {
  value: ResearchCategoryKey;
  label: string;
}

export interface ResearchReportFilters {
  year: string | "all";
  departmentId: string | "all";
  researchType: ResearchCategoryKey | "all";
  lecturerId: string | "all";
}

export interface ResearchReportKpis {
  totalCount: number;
  isiCount: number;
  scopusCount: number;
  conferenceCount: number;
  projectCount: number;
  bookCount: number;
}

export interface ResearchReportCharts {
  byDepartment: {
    labels: string[];
    isi: number[];
    scopus: number[];
    conference: number[];
    project: number[];
    book: number[];
  };
  distribution: {
    labels: string[];
    values: number[];
  };
  byYear: {
    labels: string[];
    values: number[];
  };
}

export interface ResearchReportRow {
  id: number;
  activityCode: string | null;
  title: string;
  categoryKey: ResearchCategoryKey;
  categoryLabel: string;
  venueLabel: string;
  lecturerNames: string;
  departmentName: string;
  year: number | null;
}

export interface ResearchReportPagination {
  page: number;
  perPage: number;
  total: number;
  lastPage: number;
}

export interface ResearchReportTable {
  items: ResearchReportRow[];
  pagination: ResearchReportPagination;
}

export interface ResearchReportFiltersResponse {
  years: string[];
  departments: ResearchDepartmentOption[];
  researchTypes: ResearchTypeOption[];
  lecturers: ResearchLecturerOption[];
}

export interface ResearchReportResponse {
  kpis: ResearchReportKpis;
  charts: ResearchReportCharts;
  table: ResearchReportTable;
  appliedFilters: {
    year: number | null;
    departmentId: number | null;
    researchType: ResearchCategoryKey | null;
    lecturerId: number | null;
    q: string | null;
    sort: string | null;
  };
}

export type ResearchReportSortField =
  | "title"
  | "category"
  | "venue"
  | "lecturer"
  | "department"
  | "year";

export type ResearchReportSortDirection = "asc" | "desc";

export interface ResearchReportSortCondition {
  sortFieldIdentifier: ResearchReportSortField;
  sortDirection: ResearchReportSortDirection;
}
