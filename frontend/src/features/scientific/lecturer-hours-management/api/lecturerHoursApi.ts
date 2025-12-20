import { mockApiRequest } from "../lib/apiClient";
import type {
  LecturerHoursOverviewDTO,
  LecturerHoursDetailDTO,
} from "../lecturerHours.contract";
import { mockLecturerHoursOverviewRows } from "../mock-data/lecturerHoursOverview.mock";
import { readMockLecturerHoursDetailRows } from "../mock-data/lecturerHoursDetail.mock";

export interface ReadLecturerHoursOverviewParamsDTO {
  academic_year_id: number;
  faculty_id?: number;
}

export interface ReadLecturerHoursDetailParamsDTO {
  lecturer_id: number;
  academic_year_id: number;
}

/**
 * TODO (P0 - backend thật):
 * - hours_total phải là tổng giờ từ công trình fully approved
 * - rows detail chỉ trả công trình fully approved
 */
export const lecturerHoursApi = {
  async readOverview(
    params: ReadLecturerHoursOverviewParamsDTO
  ): Promise<LecturerHoursOverviewDTO[]> {
    return mockApiRequest(() => {
      const byYear = mockLecturerHoursOverviewRows.filter(
        (row) => row.academic_year_id === params.academic_year_id
      );

      const byFaculty =
        params.faculty_id !== undefined
          ? byYear.filter((row) => row.faculty_id === params.faculty_id)
          : byYear;

      return byFaculty;
    });
  },

  async readDetail(
    params: ReadLecturerHoursDetailParamsDTO
  ): Promise<LecturerHoursDetailDTO> {
    return mockApiRequest(() => {
      return {
        lecturer_id: params.lecturer_id,
        academic_year_id: params.academic_year_id,
        rows: readMockLecturerHoursDetailRows(
          params.lecturer_id,
          params.academic_year_id
        ),
      };
    });
  },
};
