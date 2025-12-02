// src/features/declarations/types.ts
export type WorkType = "ARTICLE" | "PROJECT" | "BOOK" | "OTHER";
export type ArticleCategory = "HDGSNN_1_2" | "HDGSNN_TO_1" | "ISSN_ISBN";

export interface Member {
  id: number;
  isExternal: boolean; // true: ngoài trường
  fullName: string;
  academicTitle?: string; // chức danh khoa học – chủ yếu dùng cho ngoài trường
  degree?: string; // học vị
  organization?: string; // đơn vị công tác
  role: string; // vai trò trong bài báo
}

export interface ArticleFormModel {
  category: ArticleCategory | "";
  title: string; // Tên bài báo
  issue: string; // Số
  pages: string; // Trang
  journal: string; // Tạp chí
  doi: string;
  publicLink: string;
  evidenceNote: string;
  publishedYear: string; // ghi chú về minh chứng
}
// src/features/declarations/types.ts
export interface WorkEvidenceFile {
  id?: string;
  name: string;
  url?: string;
  file?: File;
  type?: string;
  size?: number;
}
export type ProjectLevel = "MINISTRY" | "PROVINCIAL" | "INSTITUTION" | "OTHER";

export interface ProjectHoursFormModel {
  level: ProjectLevel | "";
  title: string; // Tên đề tài
  code: string; // Mã số đề tài
  principalName: string; // Chủ nhiệm đề tài
  startYear: string; // Năm bắt đầu (hoặc có thể dùng date)
  endYear: string; // Năm kết thúc
  note: string; // Ghi chú khác (nếu cần)
}

export type BookMaterialType = "TEXTBOOK" | "REFERENCE";

export interface BookHoursFormModel {
  type: BookMaterialType | "";
  title: string; // Tên giáo trình / tài liệu
  isbn: string; // ISBN (nếu có)
  publisher: string; // Nhà xuất bản
  publishedYear: string; // Năm xuất bản
  principalEditor: string; // Chủ biên
  note: string; // Ghi chú khác
}

export interface WorkEvidenceFile {
  id?: string;
  name: string;
  url?: string;
  file?: File;
  type?: string;
  size?: number;
}

export interface ConferenceHoursFormModel {
  academicYear: string; // "2024-2025"
  semester: "ALL" | "HK1" | "HK2";
  note: string;
}

export type ConferenceRole = "PRESENTATION" | "ATTENDANCE";

export interface ConferenceEntry {
  id: number;
  name: string; // Tên hội nghị / hội thảo
  date: string; // YYYY-MM-DD
  location: string; // Nơi tổ chức
  role: ConferenceRole; // Báo cáo / Tham dự
  evidences: WorkEvidenceFile[]; // 👈 danh sách file minh chứng
}
