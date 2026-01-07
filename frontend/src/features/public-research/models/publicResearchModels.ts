import type { PublicResearchWorkTypeDto } from "../dto/publicResearchDtos";

export type PublicResearchWorkType = PublicResearchWorkTypeDto;

export type PublicResearchItem = {
  id: number;
  title: string;
  abstract: string;

  lecturerId: number;
  lecturerCode: string;
  lecturerName: string;

  facultyId: number;
  facultyName: string;

  workType: PublicResearchWorkType;

  academicYearId: number;
  academicYearCode: string;

  approvalStatus: "APPROVED";

  // ✅ UI bài báo
  pdfUrl: string | null;
  coverUrl: string | null;
  keywords: string[];
};

export type PublicResearchFilterState = {
  lecturerQuery: string;
  facultyId: number | null;
  workType: PublicResearchWorkType | null;
  academicYearId: number | null;
  page: number;
  pageSize: number;
};

export type LoadingState = "idle" | "loading" | "success" | "error";

export type SelectOption<TValue> = {
  value: TValue;
  label: string;
};
