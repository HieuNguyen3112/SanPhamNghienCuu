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
  keyword: string;
};

export type LoadingState = "idle" | "loading" | "success" | "error";

export type SelectOption<TValue> = {
  value: TValue;
  label: string;
};

export type PublicResearchParticipant = {
  lecturerId: number;
  lecturerCode: string;
  lecturerName: string;
  facultyName: string;
  roleName: string;
};

export type PublicResearchDetail = PublicResearchItem & {
  activityCode: string;
  participants: PublicResearchParticipant[];
  evidenceFiles: { label: string; url: string }[];
};