import http from "@/lib/http";
import type {
  AcademicRankOption,
  DegreeOption,
  FacultyOption,
  GenderOption,
  LecturerReportFilters,
  LecturerReportCharts,
  LecturerReportFiltersResponse,
  LecturerReportPagination,
  LecturerReportResponse,
  LecturerReportRow,
  LecturerReportSummary,
  LecturerSortDirection,
  LecturerSortField,
} from "@/features/reports/lecturer/lecturerReportTypes";

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

interface LecturerReportFiltersResponseDTO {
  faculties: FacultyOption[];
  degrees: DegreeOption[];
  academic_ranks: AcademicRankOption[];
  genders: GenderOption[];
}

interface LecturerReportSummaryDTO {
  total_lecturers: number;
  doctor_count: number;
  master_count: number;
  bachelor_count: number;
  professor_associate_count: number;
}

interface LecturerReportChartsDTO {
  by_faculty: { labels: string[]; values: number[] };
  by_degree: { labels: string[]; values: number[] };
  by_academic_rank: { labels: string[]; values: number[] };
  by_gender: { labels: string[]; values: number[] };
}

interface LecturerReportRowDTO {
  id: number;
  full_name: string;
  faculty: { id: number | null; name: string | null };
  gender: string | null;
  degree: { id: number | null; code: string | null; name: string | null };
  academic_rank: { id: number | null; code: string | null; name: string | null };
  seniority_years: number;
}

interface LecturerReportPaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

interface LecturerReportTableDTO {
  items: LecturerReportRowDTO[];
  pagination: LecturerReportPaginationDTO;
}

interface LecturerReportResponseDTO {
  summary: LecturerReportSummaryDTO;
  charts: LecturerReportChartsDTO;
  table: LecturerReportTableDTO;
  applied_filters: {
    faculty_id: number | null;
    degree_id: number | null;
    academic_rank_id: number | null;
    gender: string | null;
    sort: string | null;
  };
}

export async function fetchFacultyLecturerReportFilters(): Promise<LecturerReportFiltersResponse> {
  const { data } = await http.get<ApiResponse<LecturerReportFiltersResponseDTO>>(
    "/api/faculty/reports/lecturers/lookups"
  );
  return {
    faculties: data.data.faculties,
    degrees: data.data.degrees,
    academicRanks: data.data.academic_ranks,
    genders: data.data.genders,
  };
}

export async function fetchFacultyLecturerReport(params: {
  faculty_id?: number;
  degree_id?: number;
  academic_rank_id?: number;
  gender?: string;
  sort?: `${LecturerSortField}:${LecturerSortDirection}`;
  page?: number;
  per_page?: number;
}): Promise<LecturerReportResponse> {
  const { data } = await http.get<ApiResponse<LecturerReportResponseDTO>>(
    "/api/faculty/reports/lecturers",
    { params }
  );

  const summaryDto = data.data.summary;
  const summary: LecturerReportSummary = {
    totalLecturers: summaryDto.total_lecturers,
    doctorCount: summaryDto.doctor_count,
    masterCount: summaryDto.master_count,
    bachelorCount: summaryDto.bachelor_count,
    professorAssociateCount: summaryDto.professor_associate_count,
  };

  const chartsDto = data.data.charts;
  const charts: LecturerReportCharts = {
    byFaculty: chartsDto.by_faculty,
    byDegree: chartsDto.by_degree,
    byAcademicRank: chartsDto.by_academic_rank,
    byGender: chartsDto.by_gender,
  };

  const items: LecturerReportRow[] = data.data.table.items.map((row) => ({
    id: row.id,
    fullName: row.full_name,
    faculty: row.faculty,
    gender: row.gender,
    degree: row.degree,
    academicRank: row.academic_rank,
    seniorityYears: row.seniority_years,
  }));

  const paginationDto = data.data.table.pagination;
  const pagination: LecturerReportPagination = {
    page: paginationDto.page,
    perPage: paginationDto.per_page,
    total: paginationDto.total,
    lastPage: paginationDto.last_page,
  };

  const appliedFilters: LecturerReportFilters = {
    facultyId: data.data.applied_filters.faculty_id ?? "ALL",
    degreeId: data.data.applied_filters.degree_id ?? "ALL",
    academicRankId: data.data.applied_filters.academic_rank_id ?? "ALL",
    gender: data.data.applied_filters.gender ?? "ALL",
  };

  return {
    summary,
    charts,
    table: { items, pagination },
    appliedFilters,
  };
}

export type ExportResult = {
  blob: Blob;
  filename: string;
};

function parseFilename(disposition?: string | null): string | null {
  if (!disposition) return null;

  const match =
    /filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i.exec(disposition);
  const raw = match?.[1] ?? match?.[2];
  if (!raw) return null;

  try {
    return decodeURIComponent(raw);
  } catch {
    return raw;
  }
}

async function exportReport(
  url: string,
  params: Record<string, string | number>,
  fallbackFilename: string
): Promise<ExportResult> {
  const response = await http.get(url, {
    params,
    responseType: "blob",
  });

  const disposition = response.headers?.["content-disposition"] as
    | string
    | undefined;
  const filename = parseFilename(disposition) ?? fallbackFilename;

  return { blob: response.data as Blob, filename };
}

export async function exportFacultyLecturerReportExcel(params: {
  faculty_id?: number;
  degree_id?: number;
  academic_rank_id?: number;
  gender?: string;
  sort?: `${LecturerSortField}:${LecturerSortDirection}`;
}): Promise<ExportResult> {
  return exportReport(
    "/api/faculty/reports/lecturers/export/excel",
    params,
    "lecturer_report.xlsx"
  );
}

export async function exportFacultyLecturerReportPdf(params: {
  faculty_id?: number;
  degree_id?: number;
  academic_rank_id?: number;
  gender?: string;
  sort?: `${LecturerSortField}:${LecturerSortDirection}`;
}): Promise<ExportResult> {
  return exportReport(
    "/api/faculty/reports/lecturers/export/pdf",
    params,
    "lecturer_report.pdf"
  );
}
