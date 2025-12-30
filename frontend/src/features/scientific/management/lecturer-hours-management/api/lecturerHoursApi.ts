import http from "@/lib/http";
import type {
  LecturerHoursOverviewResponseDTO,
  LecturerHoursDetailResponseDTO,
} from "../lecturerHours.contract";

export interface ReadLecturerHoursOverviewParamsDTO {
  academic_year_id?: number;
  faculty_id?: number;
  kpi_status?: string;
  q?: string;
  page?: number;
  per_page?: number;
}

export interface ReadLecturerHoursDetailParamsDTO {
  lecturer_id: number;
  academic_year_id: number;
}

export const lecturerHoursApi = {
  async readOverview(
    params: ReadLecturerHoursOverviewParamsDTO
  ): Promise<LecturerHoursOverviewResponseDTO> {
    const cleaned: Record<string, string | number> = {};
    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined) return;
      if (typeof value === "string" && value.trim() === "") return;
      cleaned[key] = typeof value === "string" ? value.trim() : value;
    });

    const { data } = await http.get<LecturerHoursOverviewResponseDTO>(
      "/api/admin/hours/lecturers/summary",
      { params: cleaned }
    );
    return data;
  },

  async readDetail(
    params: ReadLecturerHoursDetailParamsDTO
  ): Promise<LecturerHoursDetailResponseDTO> {
    const { data } = await http.get<LecturerHoursDetailResponseDTO>(
      `/api/admin/hours/lecturers/${params.lecturer_id}`,
      { params: { academic_year_id: params.academic_year_id } }
    );
    return data;
  },
};
