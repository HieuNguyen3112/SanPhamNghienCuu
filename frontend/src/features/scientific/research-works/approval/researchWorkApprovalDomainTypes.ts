/**
 * Lý do tồn tại file này:
 * - Tách kiểu dữ liệu (domain types) khỏi UI để giảm phụ thuộc vòng (page/table/drawer).
 * - Đảm bảo naming phản ánh nghiệp vụ quản lý công trình nghiên cứu trong môi trường đại học.
 */

export type ResearchWorkType =
  | "JOURNAL_ARTICLE"
  | "RESEARCH_PROJECT"
  | "TEXTBOOK_OR_MONOGRAPH"
  | "CONFERENCE_PAPER"
  | "OTHER_RESEARCH_WORK";

export type ResearchWorkApprovalStatus = "PENDING" | "APPROVED" | "REJECTED";

export type ResearchWorkApprovalScopeType = "UNIVERSITY" | "FACULTY";

export type ResearchWorkRejectionReason =
  | "Minh chứng không hợp lệ"
  | "Công trình không đủ tiêu chí"
  | "Trùng lặp công trình"
  | "Sai loại công trình"
  | "Khác";

export type ResearchWorkContributorRole =
  | "Tác giả chính"
  | "Đồng tác giả"
  | "Chủ nhiệm đề tài"
  | "Thành viên";

export interface ResearchWorkContributorInformation {
  contributorIdentifier: string;
  contributorFullName: string;
  contributorRole?: ResearchWorkContributorRole;
}

export interface ResearchWorkEvidenceFileInformation {
  evidenceFileIdentifier: string;
  evidenceFileDisplayName: string;
  evidenceFileWebAddress?: string;
}

/**
 * Thông tin công trình phục vụ quy trình xét duyệt của đơn vị quản lý nghiên cứu.
 */
export interface ResearchWorkApprovalInformation {
  researchWorkIdentifier: string;
  researchWorkTitle: string;
  researchWorkType: ResearchWorkType;

  lecturerFullName: string;
  facultyName: string;
  contributorInformationCollection: ResearchWorkContributorInformation[];

  academicYearDisplayName: string;
  researchWorkLevelDisplayName: string;

  proofDocumentFileName: string;
  proofDocumentWebAddress?: string;

  digitalObjectIdentifierOrPublicWebAddress?: string;

  decisionDocumentFileName?: string;
  decisionDocumentWebAddress?: string;

  proposedResearchHourValue: number;
  conversionCoefficientValue: number;
  approvedResearchHourValue?: number;

  rejectionReason?: ResearchWorkRejectionReason;
  rejectionExplanation?: string;

  approvalStatus: ResearchWorkApprovalStatus;

  // Cho phép mô phỏng tệp minh chứng bổ sung.
  additionalEvidenceFileInformationCollection?: ResearchWorkEvidenceFileInformation[];
}
