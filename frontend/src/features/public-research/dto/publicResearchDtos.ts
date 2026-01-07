export type PublicResearchWorkTypeDto =
  | "ARTICLE"
  | "BOOK"
  | "PROJECT"
  | "CONFERENCE"
  | "OTHER";

export type PublicResearchApprovalStatusDto = "APPROVED";

export type PublicResearchItemDto = {
  id: number;
  title: string;
  abstract: string;

  lecturer_id: number;
  lecturer_code: string;
  lecturer_name: string;

  faculty_id: number;
  faculty_name: string;

  // ✅ BẮT BUỘC KHÔNG undefined
  work_type: PublicResearchWorkTypeDto;

  academic_year_id: number;
  academic_year_code: string;

  approval_status: PublicResearchApprovalStatusDto;

  // ✅ optional fields cho UI “giống bài báo”
  pdf_url?: string | null;
  cover_url?: string | null;
  keywords?: string[];
};

export type PublicResearchListQueryDto = {
  // ✅ bắt buộc string (không undefined)
  lecturer_query: string;
  faculty_id: number | null;
  work_type: PublicResearchWorkTypeDto | null;
  academic_year_id: number | null;
  page: number;
  page_size: number;
};

export type PublicResearchListResponseDto = {
  items: PublicResearchItemDto[];
  total: number;
};
