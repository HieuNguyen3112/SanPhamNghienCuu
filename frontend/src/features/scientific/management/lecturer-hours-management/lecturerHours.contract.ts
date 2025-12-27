/**
 * Single contract file:
 * - DTO (snake_case)
 * - UI Model (camelCase)
 * - Mapper (fromDto/toDto)
 *
 * NOTE:
 * - DTO keys: snake_case
 * - UI model keys: camelCase
 * - Service layer chỉ dùng UI model
 */

// =========================
// DTOs (snake_case)
// =========================
export interface LecturerHoursOverviewDTO {
  lecturer_id: number;
  lecturer_code: string;
  lecturer_full_name: string;

  faculty_id: number;
  faculty_name: string;

  department_id: number;
  department_name: string;

  degree_name: string | null;
  academic_rank_name: string | null;

  academic_year_id: number;
  academic_year_code: string;

  hours_total: number;
  required_hours: number;
}

export interface LecturerHoursDetailRowDTO {
  activity_id: number;
  activity_title: string;
  activity_kind_name: string;
  academic_year_code: string;
  hours_converted: number;
}

export interface LecturerHoursDetailDTO {
  lecturer_id: number;
  academic_year_id: number;
  rows: LecturerHoursDetailRowDTO[];
}

// =========================
// UI Models (camelCase)
// =========================
export interface LecturerHoursOverview {
  lecturerId: number;
  lecturerCode: string;
  lecturerFullName: string;

  facultyId: number;
  facultyName: string;

  departmentId: number;
  departmentName: string;

  degreeName: string | null;
  academicRankName: string | null;

  academicYearId: number;
  academicYearCode: string;

  hoursTotal: number;
  requiredHours: number;
}

export interface LecturerHoursDetailRow {
  activityId: number;
  activityTitle: string;
  activityKindName: string;
  academicYearCode: string;
  hoursConverted: number;
}

export interface LecturerHoursDetail {
  lecturerId: number;
  academicYearId: number;
  rows: LecturerHoursDetailRow[];
}

// =========================
// Mappers
// =========================
export const lecturerHoursOverviewMapper = {
  fromDto(dto: LecturerHoursOverviewDTO): LecturerHoursOverview {
    return {
      lecturerId: dto.lecturer_id,
      lecturerCode: dto.lecturer_code,
      lecturerFullName: dto.lecturer_full_name,

      facultyId: dto.faculty_id,
      facultyName: dto.faculty_name,

      departmentId: dto.department_id,
      departmentName: dto.department_name,

      degreeName: dto.degree_name,
      academicRankName: dto.academic_rank_name,

      academicYearId: dto.academic_year_id,
      academicYearCode: dto.academic_year_code,

      hoursTotal: dto.hours_total,
      requiredHours: dto.required_hours,
    };
  },

  toDto(model: LecturerHoursOverview): LecturerHoursOverviewDTO {
    return {
      lecturer_id: model.lecturerId,
      lecturer_code: model.lecturerCode,
      lecturer_full_name: model.lecturerFullName,

      faculty_id: model.facultyId,
      faculty_name: model.facultyName,

      department_id: model.departmentId,
      department_name: model.departmentName,

      degree_name: model.degreeName,
      academic_rank_name: model.academicRankName,

      academic_year_id: model.academicYearId,
      academic_year_code: model.academicYearCode,

      hours_total: model.hoursTotal,
      required_hours: model.requiredHours,
    };
  },
};

function mapDetailRowFromDto(
  dto: LecturerHoursDetailRowDTO
): LecturerHoursDetailRow {
  return {
    activityId: dto.activity_id,
    activityTitle: dto.activity_title,
    activityKindName: dto.activity_kind_name,
    academicYearCode: dto.academic_year_code,
    hoursConverted: dto.hours_converted,
  };
}

function mapDetailRowToDto(
  model: LecturerHoursDetailRow
): LecturerHoursDetailRowDTO {
  return {
    activity_id: model.activityId,
    activity_title: model.activityTitle,
    activity_kind_name: model.activityKindName,
    academic_year_code: model.academicYearCode,
    hours_converted: model.hoursConverted,
  };
}

export const lecturerHoursDetailMapper = {
  fromDto(dto: LecturerHoursDetailDTO): LecturerHoursDetail {
    return {
      lecturerId: dto.lecturer_id,
      academicYearId: dto.academic_year_id,
      rows: dto.rows.map(mapDetailRowFromDto),
    };
  },

  toDto(model: LecturerHoursDetail): LecturerHoursDetailDTO {
    return {
      lecturer_id: model.lecturerId,
      academic_year_id: model.academicYearId,
      rows: model.rows.map(mapDetailRowToDto),
    };
  },
};
