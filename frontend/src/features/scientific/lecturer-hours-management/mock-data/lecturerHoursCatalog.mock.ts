export interface AcademicYearOption {
  id: number;
  code: string;
  isActive: boolean;
}

export interface FacultyOption {
  id: number;
  name: string;
}

export const mockAcademicYears: AcademicYearOption[] = [
  { id: 1, code: "2024-2025", isActive: true },
  { id: 2, code: "2023-2024", isActive: false },
];

export const mockFaculties: FacultyOption[] = [
  { id: 10, name: "Khoa Công nghệ Thông tin" },
  { id: 20, name: "Khoa Kinh tế" },
];
