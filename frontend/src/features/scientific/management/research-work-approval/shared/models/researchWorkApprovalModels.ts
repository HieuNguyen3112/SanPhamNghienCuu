export type ResearchWorkApprovalScopeIdentifier =
  | "FACULTY_SCOPE"
  | "UNIVERSITY_SCOPE";

export type ResearchWorkType =
  | "JOURNAL_ARTICLE"
  | "RESEARCH_PROJECT"
  | "CONFERENCE_PROCEEDING"
  | "BOOK_CHAPTER"
  | "STUDENT_SUPERVISION";

export type ResearchWorkApprovalStatus =
  | "PENDING_FACULTY_APPROVAL"
  | "APPROVED_BY_FACULTY_FINAL"
  | "APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY"
  | "REJECTED_BY_FACULTY"
  | "PENDING_UNIVERSITY_APPROVAL"
  | "APPROVED_BY_UNIVERSITY_FINALIZED_HOURS"
  | "REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY";

export type ResearchWorkRejectionReasonType =
  // Faculty reasons
  | "MISSING_EVIDENCE"
  | "INACCURATE_INFORMATION"
  | "OUTSIDE_FACULTY_SCOPE"
  // University reasons
  | "WRONG_HOUR_CONVERSION"
  | "EVIDENCE_NOT_QUALIFIED"
  | "NOT_COMPLIANT_WITH_RESEARCH_POLICY"
  // Shared
  | "OTHER";

export interface EvidenceAttachment {
  evidenceAttachmentIdentifier: number;
  evidenceAttachmentDisplayName: string;
  evidenceAttachmentFileType: string;
  evidenceAttachmentPreviewUrl: string;
  evidenceAttachmentDownloadUrl?: string | null;
}

export interface ResearchWorkAuthor {
  authorIdentifier: number;
  authorDisplayName: string;
  authorFacultyIdentifier: string;
  authorFacultyDisplayName: string;
  authorFacultyId?: number | null;
  ownerFacultyId?: number | null;
  isOutsideFaculty?: boolean;
  isPrimaryAuthor: boolean;
  isSubmittingLecturer: boolean;
  authorRoleDisplayName?: string;
  declaredHours?: number | null;
  computedMemberHours?: number | null;
  recommendedHoursByPolicy?: number | null;
  officialHours?: number | null;
}
export type ResearchWorkCoAuthor = {
  lecturerIdentifier: number;
  lecturerDisplayName: string;
  facultyDisplayName?: string; // optional
  roleDisplayName?: string; // optional: "Đồng tác giả", "Tác giả liên hệ"...
};
export interface ApprovalHistoryEntry {
  historyIdentifier: number;
  reviewLevelDisplayName: string;
  reviewActionDisplayName: string;
  reviewedAtDateTimeString: string;
  reviewNote: string;
}

export interface ResearchWorkApprovalEntry {
  researchWorkIdentifier: number;
  researchWorkTitle: string;

  researchWorkKindDisplayName?: string | null;
  researchWorkCategoryDisplayName?: string | null;
  journalInfo?: {
    journalName: string | null;
    issn: string | null;
    journalScope: string | null;
    journalSourceName: string | null;
    journalPublisher: string | null;
    journalWebsite: string | null;
    workScore: number | null;
  } | null;

  submittingLecturerDisplayName: string;

  facultyIdentifier: string;
  facultyDisplayName: string;

  academicYear: string;
  researchWorkType: ResearchWorkType;

  submittedAtDateTimeString: string;

  // Faculty review
  facultyReviewedAtDateTimeString: string | null;
  facultyApprovedAtDateTimeString: string | null;
  facultyApprovalNote: string | null;
  facultyRejectionReasonType: ResearchWorkRejectionReasonType | null;
  facultyRejectionReasonDetail: string | null;

  // University review
  universityReviewedAtDateTimeString: string | null;
  universityRejectionReasonType: ResearchWorkRejectionReasonType | null;
  universityRejectionReasonDetail: string | null;

  // Hours
  lecturerDeclaredResearchHours: number;
  recommendedResearchHoursByPolicy: number;
  officialResearchHours: number;
  ruleResolved?: boolean;
  ruleSummary?: string | null;
  hoursResolutionNote?: string | null;

  approvalStatus: ResearchWorkApprovalStatus;
  hasApproverConflict?: boolean;
  approverConflictCode?: string | null;
  approverConflictMessage?: string | null;

  evidenceAttachmentList: EvidenceAttachment[];
  researchWorkAuthorList: ResearchWorkAuthor[];
  coAuthorList: ResearchWorkCoAuthor[];
  approvalHistoryList: ApprovalHistoryEntry[];
}

export interface ResearchWorkApprovalUiConfiguration {
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;

  pageTitle: string;
  pageSubtitle: string;

  summaryStripText: string;

  isDepartmentFilterVisible: boolean;
  isOfficialResearchHoursEditable: boolean;

  tableActionButtonLabel: string;

  drawerTitle: string;
  drawerSubtitle: string;

  primaryActionButtonLabel: string;
  dangerActionButtonLabel: string;
}
