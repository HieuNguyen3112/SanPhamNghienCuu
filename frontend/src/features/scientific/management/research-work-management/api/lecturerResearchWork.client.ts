import { createMockApiClient } from "../lib/apiClient";
import type {
  AcademicYearOption,
  ApprovedDetail,
  ApprovedSummary,
  FacultyOption,
  FilterState,
  OverviewItem,
  Pagination,
  PaginatedResult,
} from "../lecturerResearchWork.contracts";
import type {
  AcademicYearOptionDTO,
  ApprovedDetailDTO,
  ApprovedSummaryDTO,
  FacultyOptionDTO,
  FilterDTO,
  OverviewDTO,
  PaginationDTO,
} from "../lecturerResearchWork.contracts";
import { mapper } from "../lecturerResearchWork.contracts";
import {
  buildApprovedDetail,
  buildApprovedSummaries,
  buildOverviewDtos,
  mockAcademicYearOptions,
  mockFacultyOptions,
  mockLecturerDirectory,
} from "../lecturerResearchWork.mockData";
import http from "@/lib/http";

export type Scope = "faculty" | "university";

export interface ClientConfig {
  scope: Scope;
  fixedFacultyId?: number;
}

export interface PaginationRequest {
  page: number;
  perPage: number;
}

export interface LecturerResearchWorkClient {
  loadFacultyOptions(): Promise<FacultyOption[]>;
  loadAcademicYearOptions(): Promise<AcademicYearOption[]>;

  loadOverview(
    filter: FilterState,
    pagination: PaginationRequest
  ): Promise<PaginatedResult<OverviewItem>>;
  loadApprovedWorks(
    lecturerId: number,
    filter: FilterState,
    pagination: PaginationRequest
  ): Promise<PaginatedResult<ApprovedSummary>>;
  loadApprovedDetail(activityId: number): Promise<ApprovedDetail>;
}

function buildPagination(total: number, pagination: PaginationRequest): Pagination {
  const perPage = Math.max(1, pagination.perPage || total || 1);
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const page = Math.min(Math.max(1, pagination.page || 1), lastPage);
  return { page, perPage, total, lastPage };
}

function sliceByPagination<T>(items: T[], pagination: Pagination): T[] {
  const startIndex = (pagination.page - 1) * pagination.perPage;
  return items.slice(startIndex, startIndex + pagination.perPage);
}

function mapPagination(dto?: PaginationDTO | null, fallback?: Pagination): Pagination {
  if (dto) return mapper.paginationFromDto(dto);
  if (fallback) return fallback;
  return { page: 1, perPage: 1, total: 0, lastPage: 1 };
}

export function createLecturerResearchWorkClient(
  config: ClientConfig
): LecturerResearchWorkClient {
  const apiClient = createMockApiClient({ minDelayMs: 200, maxDelayMs: 400 });
  const fixedFacultyId = config.fixedFacultyId ?? 1;

  function applyScopeToOverview(
    overviewDtos: ReturnType<typeof buildOverviewDtos>,
    filterDto: FilterDTO
  ) {
    const scopeFacultyId = config.scope === "faculty" ? fixedFacultyId : null;

    return overviewDtos.filter((item) => {
      const matchScopeFaculty = scopeFacultyId
        ? item.faculty_id === scopeFacultyId
        : true;
      const matchFilterFaculty = filterDto.faculty_id
        ? item.faculty_id === filterDto.faculty_id
        : true;

      const keyword = filterDto.lecturer_name.trim().toLowerCase();
      const matchName = keyword
        ? item.lecturer_full_name.toLowerCase().includes(keyword)
        : true;

      return matchScopeFaculty && matchFilterFaculty && matchName;
    });
  }

  function applyStatusMode(items: OverviewItem[], mode: FilterState["statusMode"]) {
    if (mode === "all") return items;

    return items.map((item) => {
      if (mode === "approved") {
        return {
          ...item,
          totalCount: item.approvedCount,
          pendingCount: 0,
          rejectedCount: 0,
        };
      }

      if (mode === "pending") {
        return {
          ...item,
          totalCount: item.pendingCount,
          approvedCount: 0,
          rejectedCount: 0,
        };
      }

      return {
        ...item,
        totalCount: item.rejectedCount,
        approvedCount: 0,
        pendingCount: 0,
      };
    });
  }

  function canAccessLecturer(lecturerId: number, filterDto: FilterDTO) {
    const lecturer = mockLecturerDirectory.find(
      (l) => l.lecturer_id === lecturerId
    );
    if (!lecturer) return false;

    if (config.scope === "faculty") {
      return lecturer.faculty_id === fixedFacultyId;
    }

    if (filterDto.faculty_id) {
      return lecturer.faculty_id === filterDto.faculty_id;
    }

    return true;
  }

  return {
    async loadFacultyOptions() {
      const dtos = await apiClient.request("loadFacultyOptions", () => {
        if (config.scope === "faculty") {
          return mockFacultyOptions.filter((f) => f.id === fixedFacultyId);
        }
        return mockFacultyOptions;
      });

      return dtos.map(mapper.facultyOptionFromDto);
    },

    async loadAcademicYearOptions() {
      const dtos = await apiClient.request(
        "loadAcademicYearOptions",
        () => mockAcademicYearOptions
      );
      return dtos.map(mapper.academicYearOptionFromDto);
    },

    async loadOverview(filter: FilterState, pagination: PaginationRequest) {
      const filterDto = mapper.filter.toDto(filter);

      const dtos = await apiClient.request("loadOverview", () => {
        const overviewDtos = buildOverviewDtos(filterDto.academic_year_id);
        return applyScopeToOverview(overviewDtos, filterDto);
      });

      const mapped = applyStatusMode(
        dtos.map(mapper.overviewFromDto),
        filter.statusMode
      );

      const paginationState = buildPagination(mapped.length, pagination);
      return {
        items: sliceByPagination(mapped, paginationState),
        pagination: paginationState,
      };
    },

    async loadApprovedWorks(
      lecturerId: number,
      filter: FilterState,
      pagination: PaginationRequest
    ) {
      const filterDto = mapper.filter.toDto(filter);

      const dtos = await apiClient.request("loadApprovedWorks", () => {
        if (!canAccessLecturer(lecturerId, filterDto)) return [];
        return buildApprovedSummaries(lecturerId, filterDto.academic_year_id);
      });

      const mapped = dtos.map(mapper.approvedSummaryFromDto);
      const paginationState = buildPagination(mapped.length, pagination);

      return {
        items: sliceByPagination(mapped, paginationState),
        pagination: paginationState,
      };
    },

    async loadApprovedDetail(activityId: number) {
      const dto = await apiClient.request("loadApprovedDetail", () =>
        buildApprovedDetail(activityId)
      );
      return mapper.approvedDetailFromDto(dto);
    },
  };
}

type FacultyLookupsResponse = {
  data: {
    academic_years: AcademicYearOptionDTO[];
    faculty: FacultyOptionDTO;
  };
};

export function createLecturerResearchWorkHttpClient(
  config: ClientConfig
): LecturerResearchWorkClient {
  const fixedFacultyId = config.fixedFacultyId ?? null;
  let facultyLookupsCache: FacultyLookupsResponse["data"] | null = null;
  let facultyLookupsPromise: Promise<FacultyLookupsResponse["data"]> | null = null;

  function buildScopedFacultyId(filterDto: FilterDTO) {
    if (config.scope === "faculty" && fixedFacultyId) {
      return filterDto.faculty_id ?? fixedFacultyId;
    }
    return filterDto.faculty_id;
  }

  async function loadFacultyLookups() {
    if (facultyLookupsCache) return facultyLookupsCache;
    if (!facultyLookupsPromise) {
      facultyLookupsPromise = http
        .get<FacultyLookupsResponse>("/api/faculty/works/lookups")
        .then(({ data }) => {
          facultyLookupsCache = data.data;
          return data.data;
        })
        .finally(() => {
          facultyLookupsPromise = null;
        });
    }
    return facultyLookupsPromise;
  }

  return {
    async loadFacultyOptions() {
      if (config.scope === "faculty") {
        const lookups = await loadFacultyLookups();
        return [mapper.facultyOptionFromDto(lookups.faculty)];
      }

      const { data } = await http.get<{ data: FacultyOptionDTO[] }>(
        "/api/lookups/faculties"
      );
      return data.data.map((dto) => mapper.facultyOptionFromDto(dto));
    },

    async loadAcademicYearOptions() {
      if (config.scope === "faculty") {
        const lookups = await loadFacultyLookups();
        return lookups.academic_years.map((dto) =>
          mapper.academicYearOptionFromDto(dto)
        );
      }

      const { data } = await http.get<{ data: AcademicYearOptionDTO[] }>(
        "/api/lookups/academic-years"
      );
      return data.data.map((dto) => mapper.academicYearOptionFromDto(dto));
    },

    async loadOverview(filter: FilterState, pagination: PaginationRequest) {
      const filterDto = mapper.filter.toDto(filter);

      if (config.scope === "faculty") {
        const params = {
          academic_year_id: filterDto.academic_year_id,
          q: filterDto.lecturer_name,
          count_status: filterDto.status_mode,
          page: pagination.page,
          per_page: pagination.perPage,
        };

        const { data } = await http.get<{
          data: OverviewDTO[];
          meta?: { pagination?: PaginationDTO };
        }>("/api/faculty/works/lecturers/summary", { params });

        return {
          items: data.data.map((dto) => mapper.overviewFromDto(dto)),
          pagination: mapPagination(data.meta?.pagination, {
            page: pagination.page,
            perPage: pagination.perPage,
            total: data.data.length,
            lastPage: 1,
          }),
        };
      }

      const params = {
        faculty_id: buildScopedFacultyId(filterDto),
        academic_year_id: filterDto.academic_year_id,
        lecturer_name: filterDto.lecturer_name,
        status_mode: filterDto.status_mode,
        page: pagination.page,
        per_page: pagination.perPage,
      };

      const { data } = await http.get<{ data: OverviewDTO[] }>(
        "/api/admin/works/lecturers/summary",
        { params }
      );

      const items = data.data.map((dto) => mapper.overviewFromDto(dto));
      const paginationState = buildPagination(items.length, pagination);

      return {
        items: sliceByPagination(items, paginationState),
        pagination: paginationState,
      };
    },

    async loadApprovedWorks(
      lecturerId: number,
      filter: FilterState,
      pagination: PaginationRequest
    ) {
      const filterDto = mapper.filter.toDto(filter);

      if (config.scope === "faculty") {
        const params = {
          academic_year_id: filterDto.academic_year_id,
          status: "approved",
          page: pagination.page,
          per_page: pagination.perPage,
        };

        const { data } = await http.get<{
          data: ApprovedSummaryDTO[];
          meta?: { pagination?: PaginationDTO };
        }>(`/api/faculty/works/lecturers/${lecturerId}/works`, { params });

        return {
          items: data.data.map((dto) => mapper.approvedSummaryFromDto(dto)),
          pagination: mapPagination(data.meta?.pagination, {
            page: pagination.page,
            perPage: pagination.perPage,
            total: data.data.length,
            lastPage: 1,
          }),
        };
      }

      const params = {
        academic_year_id: filterDto.academic_year_id,
      };

      const { data } = await http.get<{ data: ApprovedSummaryDTO[] }>(
        `/api/admin/works/lecturers/${lecturerId}/approved`,
        { params }
      );

      const items = data.data.map((dto) => mapper.approvedSummaryFromDto(dto));
      const paginationState = buildPagination(items.length, pagination);

      return {
        items: sliceByPagination(items, paginationState),
        pagination: paginationState,
      };
    },

    async loadApprovedDetail(activityId: number) {
      const url =
        config.scope === "faculty"
          ? `/api/faculty/works/activities/${activityId}/approved`
          : `/api/admin/works/activities/${activityId}/approved`;

      const { data } = await http.get<{ data: ApprovedDetailDTO }>(url);
      return mapper.approvedDetailFromDto(data.data);
    },
  };
}
