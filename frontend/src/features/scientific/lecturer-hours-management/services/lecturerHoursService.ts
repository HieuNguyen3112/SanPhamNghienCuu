import { lecturerHoursApi } from "../api/lecturerHoursApi";
import type {
  LecturerHoursOverview,
  LecturerHoursDetailRow,
} from "../lecturerHours.contract";
import {
  lecturerHoursOverviewMapper,
  lecturerHoursDetailMapper,
} from "../lecturerHours.contract";

export type KpiStatusFilter = "all" | "hit" | "miss";

export interface LecturerHoursFilterModel {
  yearId: number;
  facultyId: number | null; // null = all (university)
  kpiStatus: KpiStatusFilter;
  keyword: string;
}

function normalizeKeyword(keyword: string) {
  return keyword.trim().toLowerCase();
}

function computeDifferenceHours(row: LecturerHoursOverview) {
  return row.hoursTotal - row.requiredHours;
}

export const lecturerHoursService = {
  async loadOverview(
    filter: LecturerHoursFilterModel
  ): Promise<LecturerHoursOverview[]> {
    const overviewDtos = await lecturerHoursApi.readOverview({
      academic_year_id: filter.yearId,
      faculty_id: filter.facultyId ?? undefined,
    });

    const overview = overviewDtos.map(lecturerHoursOverviewMapper.fromDto);

    const keyword = normalizeKeyword(filter.keyword);
    const filteredByKeyword =
      keyword.length === 0
        ? overview
        : overview.filter((row) => {
            return (
              row.lecturerFullName.toLowerCase().includes(keyword) ||
              row.lecturerCode.toLowerCase().includes(keyword)
            );
          });

    const filteredByKpi =
      filter.kpiStatus === "all"
        ? filteredByKeyword
        : filteredByKeyword.filter((row) => {
            const diff = computeDifferenceHours(row);
            return filter.kpiStatus === "hit" ? diff >= 0 : diff < 0;
          });

    // Sort: thiếu trước, sau đó theo tên
    return [...filteredByKpi].sort((a, b) => {
      const aDiff = computeDifferenceHours(a);
      const bDiff = computeDifferenceHours(b);

      if (aDiff >= 0 && bDiff < 0) return 1;
      if (aDiff < 0 && bDiff >= 0) return -1;

      return a.lecturerFullName.localeCompare(b.lecturerFullName, "vi");
    });
  },

  async loadDetail(
    lecturerId: number,
    yearId: number
  ): Promise<LecturerHoursDetailRow[]> {
    const detailDto = await lecturerHoursApi.readDetail({
      lecturer_id: lecturerId,
      academic_year_id: yearId,
    });

    const detail = lecturerHoursDetailMapper.fromDto(detailDto);

    // Sort: giờ giảm dần
    return [...detail.rows].sort((a, b) => b.hoursConverted - a.hoursConverted);
  },
};
