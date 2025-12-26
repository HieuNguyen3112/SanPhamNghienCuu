import type {
  AcademicYearDTO,
  ActivityKindDTO,
  ActivityTypeDTO,
  HourRuleDerivedDTO,
  WorkloadQuotaRuleDerivedDTO,
  AcademicYearPeriodDerivedDTO,
} from "../contracts/researchHoursCatalog.contract";

const nowIso = "2025-12-25T00:00:00Z";

export const researchHoursCatalogMock = {
  academicYears(): AcademicYearDTO[] {
    return [
      {
        id: 1,
        code: "2023-2024",
        start_date: "2023-09-01",
        end_date: "2024-08-31",
        is_active: false,
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 2,
        code: "2024-2025",
        start_date: "2024-09-01",
        end_date: "2025-08-31",
        is_active: true,
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 3,
        code: "2025-2026",
        start_date: "2025-09-01",
        end_date: "2026-08-31",
        is_active: false,
        created_at: nowIso,
        updated_at: nowIso,
      },
    ];
  },

  activityKinds(): ActivityKindDTO[] {
    return [
      {
        id: 1,
        code: "paper",
        name: "Bài báo",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 2,
        code: "book",
        name: "Sách",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 3,
        code: "project",
        name: "Đề tài",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 4,
        code: "conference",
        name: "Hội nghị",
        created_at: nowIso,
        updated_at: nowIso,
      },
    ];
  },

  activityTypes(): ActivityTypeDTO[] {
    return [
      // paper
      {
        id: 11,
        kind_id: 1,
        code: "hdgsnn_900",
        name: "Tạp chí HDGSNN 900",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 12,
        kind_id: 1,
        code: "hdgsnn_600",
        name: "Tạp chí HDGSNN 600",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 13,
        kind_id: 1,
        code: "hdgsnn_300",
        name: "Tạp chí HDGSNN 300",
        created_at: nowIso,
        updated_at: nowIso,
      },
      // book
      {
        id: 21,
        kind_id: 2,
        code: "textbook",
        name: "Giáo trình",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 22,
        kind_id: 2,
        code: "reference",
        name: "Sách tham khảo",
        created_at: nowIso,
        updated_at: nowIso,
      },
      // project
      {
        id: 31,
        kind_id: 3,
        code: "bo",
        name: "Đề tài cấp Bộ",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 32,
        kind_id: 3,
        code: "coso",
        name: "Đề tài cấp Cơ sở",
        created_at: nowIso,
        updated_at: nowIso,
      },
      // conference
      {
        id: 41,
        kind_id: 4,
        code: "attend",
        name: "Tham dự hội nghị",
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 42,
        kind_id: 4,
        code: "report",
        name: "Báo cáo tại hội nghị",
        created_at: nowIso,
        updated_at: nowIso,
      },
    ];
  },

  hourRules(): HourRuleDerivedDTO[] {
    return [
      {
        id: 1001,
        kind_id: 1,
        type_id: 11,
        distribution_strategy: "equal_all_members",
        hours_total_per_activity: "40.00",
        hours_per_occurrence: null,
        principal_fraction: null,
        others_fraction_total: null,
        max_occurrences_per_year: null,
        effective_from: "2024-09-01",
        effective_to: "2025-08-31",
        is_active: true,
        version: 1,
        created_at: nowIso,
        updated_at: nowIso,
        is_locked: true, // đã phát sinh logs
      },
      {
        id: 1002,
        kind_id: 1,
        type_id: 13,
        distribution_strategy: "equal_all_members",
        hours_total_per_activity: "25.00",
        hours_per_occurrence: null,
        principal_fraction: null,
        others_fraction_total: null,
        max_occurrences_per_year: null,
        effective_from: "2024-09-01",
        effective_to: "2025-08-31",
        is_active: true,
        version: 1,
        created_at: nowIso,
        updated_at: nowIso,
        is_locked: false,
      },
      {
        id: 1003,
        kind_id: 3,
        type_id: 31,
        distribution_strategy: "equal_all_members",
        hours_total_per_activity: "120.00",
        hours_per_occurrence: null,
        principal_fraction: null,
        others_fraction_total: null,
        max_occurrences_per_year: 2,
        effective_from: "2024-09-01",
        effective_to: "2025-08-31",
        is_active: true,
        version: 1,
        created_at: nowIso,
        updated_at: nowIso,
        is_locked: true,
      },
      {
        id: 1004,
        kind_id: 4,
        type_id: 42,
        distribution_strategy: "equal_all_members",
        hours_total_per_activity: "10.00",
        hours_per_occurrence: null,
        principal_fraction: null,
        others_fraction_total: null,
        max_occurrences_per_year: null,
        effective_from: "2023-09-01",
        effective_to: "2024-08-31",
        is_active: false,
        version: 1,
        created_at: nowIso,
        updated_at: nowIso,
        is_locked: false,
      },
    ];
  },

  /** Tab2: schema thiếu -> mock derived */
  quotaRules(): WorkloadQuotaRuleDerivedDTO[] {
    return [
      {
        id: 2001,
        academic_year_id: 2,
        academic_rank_id: 101,
        required_hours: "150.00",
        notes: "Giảng viên",
        is_active: true,
        is_locked: true,
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 2002,
        academic_year_id: 2,
        academic_rank_id: 102,
        required_hours: "180.00",
        notes: "Giảng viên chính",
        is_active: true,
        is_locked: true,
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 2003,
        academic_year_id: 2,
        academic_rank_id: 103,
        required_hours: "200.00",
        notes: "PGS",
        is_active: true,
        is_locked: true,
        created_at: nowIso,
        updated_at: nowIso,
      },
      {
        id: 2004,
        academic_year_id: 2,
        academic_rank_id: 104,
        required_hours: "220.00",
        notes: "GS",
        is_active: false,
        is_locked: false,
        created_at: nowIso,
        updated_at: nowIso,
      },
    ];
  },

  /** Tab3: schema thiếu “period” + “locked status” -> mock derived */
  yearPeriods(): AcademicYearPeriodDerivedDTO[] {
    return [
      {
        id: 3001,
        name: "Đợt 1 năm 2025",
        start_date: "2025-01-01",
        end_date: "2025-06-30",
        status: "locked",
        is_locked: true,
      },
      {
        id: 3002,
        name: "Đợt 2 năm 2025",
        start_date: "2025-07-01",
        end_date: "2025-12-31",
        status: "active",
        is_locked: false,
      },
    ];
  },
};
