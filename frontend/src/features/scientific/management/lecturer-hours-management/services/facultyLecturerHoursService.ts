import type {
  LecturerHoursOverview,
  LecturerHoursDetailRow,
  AcademicYearOption,
  FacultyOption,
  KpiStatusOption,
} from "../lecturerHours.contract";
import {
  lecturerHoursOverviewMapper,
  lecturerHoursDetailMapper,
  academicYearOptionMapper,
  facultyOptionMapper,
  kpiStatusOptionMapper,
} from "../lecturerHours.contract";
import { facultyLecturerHoursApi } from "../api/facultyLecturerHoursApi";

export type KpiStatusFilter = "all" | "hit" | "miss";

export interface LecturerHoursFilterModel {
  yearId: number | null;
  facultyId: number | null;
  kpiStatus: KpiStatusFilter;
  keyword: string;
}

export interface LecturerHoursPaginationModel {
  page: number;
  perPage: number;
}

export interface LecturerHoursOverviewTotals {
  totalLecturers: number;
  hitCount: number;
  missCount: number;
  hitRate: number;
}

export interface LecturerHoursOverviewOptions {
  academicYears: AcademicYearOption[];
  faculties: FacultyOption[];
  kpiStatuses: KpiStatusOption[];
}

export interface LecturerHoursOverviewMeta {
  pagination: {
    currentPage: number;
    perPage: number;
    total: number;
    lastPage: number;
  } | null;
  filters: {
    yearId: number | null;
    facultyId: number | null;
    kpiStatus: KpiStatusFilter;
    keyword: string;
  };
}

export interface LecturerHoursOverviewPayload {
  overview: LecturerHoursOverview[];
  totals: LecturerHoursOverviewTotals;
  options: LecturerHoursOverviewOptions;
  meta: LecturerHoursOverviewMeta;
}

export const facultyLecturerHoursService = {
  async loadOverview(
    filter: LecturerHoursFilterModel,
    pagination: LecturerHoursPaginationModel
  ): Promise<LecturerHoursOverviewPayload> {
    const response = await facultyLecturerHoursApi.readOverview({
      academic_year_id: filter.yearId ?? undefined,
      kpi_status: filter.kpiStatus,
      q: filter.keyword,
      page: pagination.page,
      per_page: pagination.perPage,
    });

    const overview = response.data.map(lecturerHoursOverviewMapper.fromDto);

    const totals = {
      totalLecturers: response.meta.totals.total_lecturers,
      hitCount: response.meta.totals.met_count,
      missCount: response.meta.totals.missing_count,
      hitRate: response.meta.totals.kpi_ratio_percent,
    };

    const options = {
      academicYears: response.meta.options.academic_years.map(
        academicYearOptionMapper.fromDto
      ),
      faculties: response.meta.options.faculties.map(facultyOptionMapper.fromDto),
      kpiStatuses: response.meta.options.kpi_statuses.map(
        kpiStatusOptionMapper.fromDto
      ),
    };

    const paginationMeta = response.meta.pagination
      ? {
          currentPage: response.meta.pagination.current_page,
          perPage: response.meta.pagination.per_page,
          total: response.meta.pagination.total,
          lastPage: response.meta.pagination.last_page,
        }
      : null;

    const meta = {
      pagination: paginationMeta,
      filters: {
        yearId: response.meta.filters.academic_year_id ?? null,
        facultyId: response.meta.filters.faculty_id ?? null,
        kpiStatus: (response.meta.filters.kpi_status as KpiStatusFilter) ?? "all",
        keyword: response.meta.filters.q ?? "",
      },
    };

    return {
      overview,
      totals,
      options,
      meta,
    };
  },

  async loadDetail(
    lecturerId: number,
    yearId: number
  ): Promise<LecturerHoursDetailRow[]> {
    const detailDto = await facultyLecturerHoursApi.readDetail({
      lecturer_id: lecturerId,
      academic_year_id: yearId,
    });

    const detail = lecturerHoursDetailMapper.fromDto(detailDto.data);

    return [...detail.rows].sort((a, b) => b.hoursConverted - a.hoursConverted);
  },
};
