import http from "@/lib/http";
import type {
  HourResearchAcademicYearOption,
  HourResearchFacultyOption,
  HourResearchReportCharts,
  HourResearchReportFiltersResponse,
  HourResearchReportKpis,
  HourResearchReportPagination,
  HourResearchReportRow,
  HourResearchStatusCode,
  HourResearchStatusOption,
  HourResearchReportTable,
} from "../hourResearchReportTypes";

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

interface FiltersResponseDTO {
  faculties: HourResearchFacultyOption[];
  academic_years: HourResearchAcademicYearOption[];
  status_options: HourResearchStatusOption[];
}

interface ReportKpisDTO {
  lecturer_count: number;
  total_hours: number;
  avg_hours: number;
  met_count: number;
  not_met_count: number;
  compliance_rate: number;
}

interface ReportChartsDTO {
  hours_by_faculty: { labels: string[]; values: number[] };
  status_distribution: { labels: string[]; values: number[] };
  hours_by_year: { labels: string[]; values: number[] };
}

interface ReportRowDTO {
  lecturer_id: number;
  lecturer_name: string;
  faculty_id: number | null;
  faculty_name: string | null;
  academic_year_id: number | null;
  academic_year_code: string | null;
  total_hours: number;
  required_hours: number;
  status: "met" | "not_met";
}

interface ReportPaginationDTO {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

interface ReportTableDTO {
  items: ReportRowDTO[];
  pagination: ReportPaginationDTO;
}

interface ReportResponseDTO {
  kpis: ReportKpisDTO;
  charts: ReportChartsDTO;
  table: ReportTableDTO;
  applied_filters: {
    faculty_id: number | null;
    academic_year_id: number | null;
    status: HourResearchStatusCode;
  };
}

export async function fetchHourResearchReportFilters(): Promise<HourResearchReportFiltersResponse> {
  const { data } = await http.get<ApiResponse<FiltersResponseDTO>>(
    "/api/admin/reports/hour-research/filters"
  );

  return {
    faculties: data.data.faculties,
    academicYears: data.data.academic_years,
    statusOptions: data.data.status_options,
  };
}

export async function fetchHourResearchReport(params: {
  faculty_id?: number;
  academic_year_id?: number;
  status?: HourResearchStatusCode;
  page?: number;
  per_page?: number;
}): Promise<{
  kpis: HourResearchReportKpis;
  charts: HourResearchReportCharts;
  table: HourResearchReportTable;
  appliedFilters: {
    facultyId: number | null;
    academicYearId: number | null;
    status: HourResearchStatusCode;
  };
}> {
  const { data } = await http.get<ApiResponse<ReportResponseDTO>>(
    "/api/admin/reports/hour-research",
    { params }
  );

  const kpisDto = data.data.kpis;
  const kpis: HourResearchReportKpis = {
    lecturerCount: kpisDto.lecturer_count,
    totalHours: kpisDto.total_hours,
    avgHours: kpisDto.avg_hours,
    metCount: kpisDto.met_count,
    notMetCount: kpisDto.not_met_count,
    complianceRate: kpisDto.compliance_rate,
  };

  const chartsDto = data.data.charts;
  const charts: HourResearchReportCharts = {
    hoursByFaculty: chartsDto.hours_by_faculty,
    statusDistribution: chartsDto.status_distribution,
    hoursByYear: chartsDto.hours_by_year,
  };

  const items: HourResearchReportRow[] = data.data.table.items.map((row) => ({
    lecturerId: row.lecturer_id,
    lecturerName: row.lecturer_name,
    facultyId: row.faculty_id,
    facultyName: row.faculty_name,
    academicYearId: row.academic_year_id,
    academicYearCode: row.academic_year_code,
    totalHours: row.total_hours,
    requiredHours: row.required_hours,
    status: row.status,
  }));

  const paginationDto = data.data.table.pagination;
  const pagination: HourResearchReportPagination = {
    page: paginationDto.page,
    perPage: paginationDto.per_page,
    total: paginationDto.total,
    lastPage: paginationDto.last_page,
  };

  return {
    kpis,
    charts,
    table: { items, pagination },
    appliedFilters: {
      facultyId: data.data.applied_filters.faculty_id,
      academicYearId: data.data.applied_filters.academic_year_id,
      status: data.data.applied_filters.status,
    },
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

export async function exportHourResearchReportExcel(params: {
  faculty_id?: number;
  academic_year_id?: number;
  status?: HourResearchStatusCode;
}): Promise<ExportResult> {
  return exportReport(
    "/api/admin/reports/hour-research/export/excel",
    params,
    "hour_research_report.xlsx"
  );
}

export async function exportHourResearchReportPdf(params: {
  faculty_id?: number;
  academic_year_id?: number;
  status?: HourResearchStatusCode;
}): Promise<ExportResult> {
  return exportReport(
    "/api/admin/reports/hour-research/export/pdf",
    params,
    "hour_research_report.pdf"
  );
}
