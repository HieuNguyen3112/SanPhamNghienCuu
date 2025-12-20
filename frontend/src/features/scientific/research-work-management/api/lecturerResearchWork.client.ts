import { createMockApiClient } from "../lib/apiClient";
import type {
  AcademicYearOption,
  ApprovedDetail,
  ApprovedSummary,
  FacultyOption,
  FilterState,
  OverviewItem,
} from "../lecturerResearchWork.contracts";
import type { FilterDTO } from "../lecturerResearchWork.contracts";
import { mapper } from "../lecturerResearchWork.contracts";
import {
  buildApprovedDetail,
  buildApprovedSummaries,
  buildOverviewDtos,
  mockAcademicYearOptions,
  mockFacultyOptions,
  mockLecturerDirectory,
} from "../lecturerResearchWork.mockData";

export type Scope = "faculty" | "university";

export interface ClientConfig {
  scope: Scope;
  fixedFacultyId?: number;
}

export interface LecturerResearchWorkClient {
  loadFacultyOptions(): Promise<FacultyOption[]>;
  loadAcademicYearOptions(): Promise<AcademicYearOption[]>;

  loadOverview(filter: FilterState): Promise<OverviewItem[]>;
  loadApprovedWorks(
    lecturerId: number,
    filter: FilterState
  ): Promise<ApprovedSummary[]>;
  loadApprovedDetail(activityId: number): Promise<ApprovedDetail>;
}

/**
 * GỘP API + SERVICE:
 * - Mock “API DTO” nằm trong client (snake_case)
 * - Client trả ra UI model (camelCase) cho composable/page
 */
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

    async loadOverview(filter: FilterState) {
      const filterDto = mapper.filter.toDto(filter);

      const dtos = await apiClient.request("loadOverview", () => {
        const overviewDtos = buildOverviewDtos(filterDto.academic_year_id);
        return applyScopeToOverview(overviewDtos, filterDto);
      });

      // statusMode: chỉ ảnh hưởng COUNT ở overview
      const mapped = dtos.map(mapper.overviewFromDto);
      if (filter.statusMode === "all") return mapped;

      return mapped.map((item) => ({
        ...item,
        totalCount: item.approvedCount,
        pendingCount: 0,
        rejectedCount: 0,
      }));
    },

    async loadApprovedWorks(lecturerId: number, filter: FilterState) {
      const filterDto = mapper.filter.toDto(filter);

      const dtos = await apiClient.request("loadApprovedWorks", () => {
        if (!canAccessLecturer(lecturerId, filterDto)) return [];
        return buildApprovedSummaries(lecturerId, filterDto.academic_year_id);
      });

      return dtos.map(mapper.approvedSummaryFromDto);
    },

    async loadApprovedDetail(activityId: number) {
      const dto = await apiClient.request("loadApprovedDetail", () =>
        buildApprovedDetail(activityId)
      );
      return mapper.approvedDetailFromDto(dto);
    },
  };
}

/**
 * TODO (khi nối BE thật):
 * export function createLecturerResearchWorkHttpClient(axiosInstance): LecturerResearchWorkClient { ... }
 * - gọi endpoint thật
 * - nhận DTO snake_case
 * - map về UI model camelCase
 */
