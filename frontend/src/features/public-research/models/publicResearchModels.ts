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

export type PublicResearchDisplayMeta = {
  article?: {
    journalName: string | null;
    issn: string | null;
    journalScope: string | null;
    journalType: string | null;
    journalSourceName: string | null;
    researchField: string | null;
    year: number | null;
    volume: string | null;
    issue: string | null;
    pageStart: number | null;
    pageEnd: number | null;
    doi: string | null;
    articleUrl: string | null;
  };
  project?: {
    projectCode: string | null;
    managementLevel: string | null;
    projectCategory: string | null;
    researchField: string | null;
    objectives: string | null;
    contentSummary: string | null;
    startMonth: string | null;
    endMonth: string | null;
    decisionNo: string | null;
    decisionDate: string | null;
  };
  book?: {
    publisher: string | null;
    isbn: string | null;
    year: number | null;
    bookType: string | null;
    researchField: string | null;
    approvalDecisionNo: string | null;
    approvalDecisionDate: string | null;
  };
  conference?: {
    conferenceName: string | null;
    heldOn: string | null;
    location: string | null;
  };
};

export type PublicResearchDetail = PublicResearchItem & {
  activityCode: string;
  participants: PublicResearchParticipant[];
  evidenceFiles: { label: string; url: string; isPdf: boolean }[];
  displayMeta: PublicResearchDisplayMeta;
};
