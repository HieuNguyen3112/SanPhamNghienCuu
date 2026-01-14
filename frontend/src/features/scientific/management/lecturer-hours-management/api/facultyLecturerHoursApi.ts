import http from "@/lib/http";
import type {
  LecturerHoursOverviewResponseDTO,
  LecturerHoursDetailResponseDTO,
} from "../lecturerHours.contract";

export interface ReadFacultyLecturerHoursOverviewParamsDTO {
  academic_year_id?: number;
  kpi_status?: string;
  q?: string;
  page?: number;
  per_page?: number;
}

export interface ReadFacultyLecturerHoursDetailParamsDTO {
  lecturer_id: number;
  academic_year_id: number;
}

export const facultyLecturerHoursApi = {
  async readOverview(
    params: ReadFacultyLecturerHoursOverviewParamsDTO
  ): Promise<LecturerHoursOverviewResponseDTO> {
    const cleaned: Record<string, string | number> = {};
    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined) return;
      if (typeof value === "string" && value.trim() === "") return;
      cleaned[key] = typeof value === "string" ? value.trim() : value;
    });

    const { data } = await http.get<LecturerHoursOverviewResponseDTO>(
      "/api/faculty/hours/lecturers/summary",
      { params: cleaned }
    );
    return data;
  },

  async readDetail(
    params: ReadFacultyLecturerHoursDetailParamsDTO
  ): Promise<LecturerHoursDetailResponseDTO> {
    const { data } = await http.get<LecturerHoursDetailResponseDTO>(
      `/api/faculty/hours/lecturers/${params.lecturer_id}`,
      { params: { academic_year_id: params.academic_year_id } }
    );
    return data;
  },
};
