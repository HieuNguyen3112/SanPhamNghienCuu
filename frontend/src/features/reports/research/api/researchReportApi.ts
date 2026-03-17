import http from "@/lib/http";
import type {
  ResearchCategoryKey,
  ResearchReportCharts,
  ResearchReportFilters,
  ResearchReportFiltersResponse,
  ResearchReportKpis,
  ResearchReportPagination,
  ResearchReportResponse,
  ResearchReportRow,
  ResearchReportSortDirection,
  ResearchReportSortField,
  ResearchReportTable,
} from "../researchReportTypes";

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

interface FiltersResponseDTO {
  years: string[];
  departments: Array<{
    id: number;
    name: string;
    faculty_id: number | null;
    faculty_name: string | null;
  }>;
  research_types: Array<{ value: string; label: string }>;
  lecturers: Array<{
    id: number;
    name: string;
    department_id: number | null;
    department_name: string | null;
  }>;
}

interface ReportResponseDTO {
  kpis: {
    total_count: number;
    isi_count: number;
    scopus_count: number;
    conference_count: number;
    project_count: number;
    book_count: number;
  };
  charts: {
    by_department_stacked: {
      labels: string[];
      isi: number[];
      scopus: number[];
      conference: number[];
      project: number[];
      book: number[];
    };
    distribution_donut: { labels: string[]; values: number[] };
    by_year_line: { labels: string[]; values: number[] };
  };
  table: {
    items: Array<{
      id: number;
      activity_code: string | null;
      title: string;
      category_key: string;
      category_label: string;
      venue_label: string;
      lecturer_names: string;
      department_name: string;
      year: number | null;
    }>;
    pagination: {
      page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  };
  applied_filters: {
    year: number | null;
    department_id: number | null;
    research_type: string | null;
    lecturer_id: number | null;
    q: string | null;
    sort: string | null;
  };
}

export async function fetchResearchReportFilters(): Promise<ResearchReportFiltersResponse> {
  const { data } = await http.get<ApiResponse<FiltersResponseDTO>>(
    "/api/admin/reports/research/filters"
  );

  return {
    years: data.data.years,
    departments: data.data.departments.map((dept) => ({
      id: dept.id,
      name: dept.name,
      facultyId: dept.faculty_id,
      facultyName: dept.faculty_name,
    })),
    researchTypes: data.data.research_types.map((type) => ({
      value: type.value as ResearchCategoryKey,
      label: type.label,
    })),
    lecturers: data.data.lecturers.map((lecturer) => ({
      id: lecturer.id,
      name: lecturer.name,
      departmentId: lecturer.department_id,
      departmentName: lecturer.department_name,
    })),
  };
}

export async function fetchResearchReport(params: {
  year?: number;
  department_id?: number;
  research_type?: ResearchReportFilters["researchType"];
  lecturer_id?: number;
  q?: string;
  sort?: `${ResearchReportSortField}:${ResearchReportSortDirection}`;
  page?: number;
  per_page?: number;
}): Promise<ResearchReportResponse> {
  const { data } = await http.get<ApiResponse<ReportResponseDTO>>(
    "/api/admin/reports/research",
    { params }
  );

  const kpis: ResearchReportKpis = {
    totalCount: data.data.kpis.total_count,
    isiCount: data.data.kpis.isi_count,
    scopusCount: data.data.kpis.scopus_count,
    conferenceCount: data.data.kpis.conference_count,
    projectCount: data.data.kpis.project_count,
    bookCount: data.data.kpis.book_count,
  };

  const charts: ResearchReportCharts = {
    byDepartment: data.data.charts.by_department_stacked,
    distribution: data.data.charts.distribution_donut,
    byYear: data.data.charts.by_year_line,
  };

  const items: ResearchReportRow[] = data.data.table.items.map((row) => ({
    id: row.id,
    activityCode: row.activity_code,
    title: row.title,
    categoryKey: row.category_key as ResearchReportRow["categoryKey"],
    categoryLabel: row.category_label,
    venueLabel: row.venue_label,
    lecturerNames: row.lecturer_names,
    departmentName: row.department_name,
    year: row.year,
  }));

  const pagination: ResearchReportPagination = {
    page: data.data.table.pagination.page,
    perPage: data.data.table.pagination.per_page,
    total: data.data.table.pagination.total,
    lastPage: data.data.table.pagination.last_page,
  };

  const table: ResearchReportTable = {
    items,
    pagination,
  };

  return {
    kpis,
    charts,
    table,
    appliedFilters: {
      year: data.data.applied_filters.year,
      departmentId: data.data.applied_filters.department_id,
      researchType: data.data.applied_filters.research_type as
        | ResearchReportRow["categoryKey"]
        | null,
      lecturerId: data.data.applied_filters.lecturer_id,
      q: data.data.applied_filters.q,
      sort: data.data.applied_filters.sort,
    },
  };
}

export type ResearchReportExportResult = {
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
): Promise<ResearchReportExportResult> {
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

export async function exportResearchReportExcel(params: {
  year?: number;
  department_id?: number;
  research_type?: ResearchReportFilters["researchType"];
  lecturer_id?: number;
  q?: string;
  sort?: `${ResearchReportSortField}:${ResearchReportSortDirection}`;
}): Promise<ResearchReportExportResult> {
  return exportReport(
    "/api/admin/reports/research/export/excel",
    params,
    "research_report.xlsx"
  );
}

export async function exportResearchReportPdf(params: {
  year?: number;
  department_id?: number;
  research_type?: ResearchReportFilters["researchType"];
  lecturer_id?: number;
  q?: string;
  sort?: `${ResearchReportSortField}:${ResearchReportSortDirection}`;
}): Promise<ResearchReportExportResult> {
  return exportReport(
    "/api/admin/reports/research/export/pdf",
    params,
    "research_report.pdf"
  );
}
