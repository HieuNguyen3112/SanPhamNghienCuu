// src/features/organization-category/mock-data/organizationCategory.mock.ts
import type {
  FacultyDTO,
  DepartmentDTO,
} from "../contracts/organizationCategory.contract";

export const facultiesMock: FacultyDTO[] = [
  {
    id: 1,
    code: "CNTT",
    name: "Khoa Công nghệ Thông tin",
    created_at: "2025-01-01T08:00:00Z",
    updated_at: "2025-12-01T10:20:00Z",
  },
  {
    id: 2,
    code: "KT",
    name: "Khoa Kinh tế",
    created_at: "2025-01-01T08:00:00Z",
    updated_at: "2025-11-20T09:15:00Z",
  },
];

export const departmentsMock: DepartmentDTO[] = [
  {
    id: 11,
    faculty_id: 1,
    code: "BM-KTPM",
    name: "Bộ môn Kỹ thuật Phần mềm",
    created_at: "2025-01-05T08:00:00Z",
    updated_at: "2025-12-10T07:30:00Z",
  },
  {
    id: 12,
    faculty_id: 1,
    code: "TT-DL",
    name: "Trung tâm Dữ liệu",
    created_at: "2025-01-07T08:00:00Z",
    updated_at: "2025-10-15T06:00:00Z",
  },
  {
    id: 21,
    faculty_id: 2,
    code: "BM-TC",
    name: "Bộ môn Tài chính",
    created_at: "2025-02-01T08:00:00Z",
    updated_at: "2025-11-02T05:40:00Z",
  },
];
