// src/features/hours/types.ts
export interface HoursQuota {
  academicYear: string; // ví dụ: "2024–2025"
  semester?: string; // ví dụ: "HK1", "HK2" (optional)
  requiredHours: number; // định mức phải đạt
}

export interface HoursSummary {
  teacherId: string;
  teacherName: string;
  departmentName?: string;
  quota: HoursQuota;
  completedHours: number; // giờ đã được tính
  pendingHours: number; // giờ đang chờ duyệt
}

export interface FacultyHoursItem extends HoursSummary {
  // có thể mở rộng thêm field riêng cho màn khoa nếu cần
}
