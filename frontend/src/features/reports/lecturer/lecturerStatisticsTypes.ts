export type GenderCategory = "Male" | "Female" | "Other";

export type EducationLevelCategory = "Bachelor" | "Master" | "Doctor";

export type AcademicRankCategory = "None" | "AssociateProfessor" | "Professor";

export interface LecturerRecord {
  lecturerIdentifier: string;
  lecturerFullName: string;
  departmentName: string;
  genderCategory: GenderCategory;
  educationLevelCategory: EducationLevelCategory;
  academicRankCategory: AcademicRankCategory;
  teachingExperienceYears: number;
}

export interface LecturerFilterConditions {
  selectedDepartmentName: string | "AllDepartments";
  selectedEducationLevelCategory: EducationLevelCategory | "AllEducationLevels";
  selectedAcademicRankCategory: AcademicRankCategory | "AllAcademicRanks";
  selectedGenderCategory: GenderCategory | "AllGenders";
}
