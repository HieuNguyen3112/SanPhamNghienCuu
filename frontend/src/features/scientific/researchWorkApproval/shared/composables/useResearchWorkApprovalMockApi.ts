import { ref } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalScopeIdentifier,
  ResearchWorkRejectionReasonType,
} from "../models/researchWorkApprovalModels";
import { createResearchWorkApprovalListMockData } from "../mockData/researchWorkApprovalMockData";

/**
 * WHY: Sau này thay API thật -> chỉ thay file này,
 * UI + provider + filter không cần đụng.
 */
export function useResearchWorkApprovalMockApi() {
  const researchWorkApprovalList = ref<ResearchWorkApprovalEntry[]>([]);

  async function loadResearchWorkApprovalList(parameters: {
    approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;
    facultyIdentifier?: string;
    totalResearchWorkCount: number;
    seedValue: number;
  }): Promise<void> {
    // demo async
    await Promise.resolve();

    researchWorkApprovalList.value =
      createResearchWorkApprovalListMockData(parameters);
  }

  function approveResearchWorkAtFacultyLevel(parameters: {
    researchWorkIdentifier: number;
  }): void {
    const matchedEntry = researchWorkApprovalList.value.find(
      (entry) =>
        entry.researchWorkIdentifier === parameters.researchWorkIdentifier
    );
    if (!matchedEntry) return;

    const nowDateTimeString = new Date().toISOString();

    matchedEntry.approvalStatus = "APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY";
    matchedEntry.facultyReviewedAtDateTimeString = nowDateTimeString;
    matchedEntry.facultyApprovedAtDateTimeString = nowDateTimeString;

    // clear reject info
    matchedEntry.facultyRejectionReasonType = null;
    matchedEntry.facultyRejectionReasonDetail = null;

    matchedEntry.approvalHistoryList.push({
      historyIdentifier: Date.now(),
      reviewLevelDisplayName: "Cấp khoa",
      reviewActionDisplayName: "Duyệt",
      reviewedAtDateTimeString: nowDateTimeString,
      reviewNote: "Khoa xác nhận hồ sơ hợp lệ và chuyển lên cấp trường.",
    });
  }

  function rejectResearchWorkAtFacultyLevel(parameters: {
    researchWorkIdentifier: number;
    rejectionReasonType: ResearchWorkRejectionReasonType;
    rejectionReasonDetail: string | null;
  }): void {
    const matchedEntry = researchWorkApprovalList.value.find(
      (entry) =>
        entry.researchWorkIdentifier === parameters.researchWorkIdentifier
    );
    if (!matchedEntry) return;

    const nowDateTimeString = new Date().toISOString();

    matchedEntry.approvalStatus = "REJECTED_BY_FACULTY";
    matchedEntry.facultyReviewedAtDateTimeString = nowDateTimeString;
    matchedEntry.facultyApprovedAtDateTimeString = null;

    matchedEntry.facultyRejectionReasonType = parameters.rejectionReasonType;
    matchedEntry.facultyRejectionReasonDetail =
      parameters.rejectionReasonDetail;

    matchedEntry.approvalHistoryList.push({
      historyIdentifier: Date.now(),
      reviewLevelDisplayName: "Cấp khoa",
      reviewActionDisplayName: "Từ chối",
      reviewedAtDateTimeString: nowDateTimeString,
      reviewNote:
        "Khoa trả lại để hoàn thiện hồ sơ trước khi chuyển lên cấp trường.",
    });
  }

  function approveResearchWorkAtUniversityLevel(parameters: {
    researchWorkIdentifier: number;
    officialResearchHours: number;
  }): void {
    const matchedEntry = researchWorkApprovalList.value.find(
      (entry) =>
        entry.researchWorkIdentifier === parameters.researchWorkIdentifier
    );
    if (!matchedEntry) return;

    const nowDateTimeString = new Date().toISOString();

    matchedEntry.approvalStatus = "APPROVED_BY_UNIVERSITY_FINALIZED_HOURS";
    matchedEntry.universityReviewedAtDateTimeString = nowDateTimeString;
    matchedEntry.officialResearchHours = Math.round(
      parameters.officialResearchHours
    );

    matchedEntry.universityRejectionReasonType = null;
    matchedEntry.universityRejectionReasonDetail = null;

    matchedEntry.approvalHistoryList.push({
      historyIdentifier: Date.now(),
      reviewLevelDisplayName: "Cấp trường",
      reviewActionDisplayName: "Chốt giờ",
      reviewedAtDateTimeString: nowDateTimeString,
      reviewNote: "Cấp trường chốt giờ chính thức.",
    });
  }

  function rejectResearchWorkAtUniversityLevel(parameters: {
    researchWorkIdentifier: number;
    rejectionReasonType: ResearchWorkRejectionReasonType;
    rejectionReasonDetail: string | null;
  }): void {
    const matchedEntry = researchWorkApprovalList.value.find(
      (entry) =>
        entry.researchWorkIdentifier === parameters.researchWorkIdentifier
    );
    if (!matchedEntry) return;

    const nowDateTimeString = new Date().toISOString();

    matchedEntry.approvalStatus = "REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY";
    matchedEntry.universityReviewedAtDateTimeString = nowDateTimeString;
    matchedEntry.universityRejectionReasonType = parameters.rejectionReasonType;
    matchedEntry.universityRejectionReasonDetail =
      parameters.rejectionReasonDetail;

    matchedEntry.approvalHistoryList.push({
      historyIdentifier: Date.now(),
      reviewLevelDisplayName: "Cấp trường",
      reviewActionDisplayName: "Trả về khoa",
      reviewedAtDateTimeString: nowDateTimeString,
      reviewNote: "Cấp trường trả về khoa để đối soát và chỉnh sửa.",
    });
  }

  return {
    researchWorkApprovalList,
    loadResearchWorkApprovalList,

    approveResearchWorkAtFacultyLevel,
    rejectResearchWorkAtFacultyLevel,

    approveResearchWorkAtUniversityLevel,
    rejectResearchWorkAtUniversityLevel,
  };
}
