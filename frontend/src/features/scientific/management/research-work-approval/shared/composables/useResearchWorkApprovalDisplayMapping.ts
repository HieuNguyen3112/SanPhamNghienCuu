import type {
  ResearchWorkApprovalScopeIdentifier,
  ResearchWorkApprovalStatus,
  ResearchWorkRejectionReasonType,
  ResearchWorkType,
} from "../models/researchWorkApprovalModels";
import { parseBackendDateTime } from "../../../shared/utils/backendDateTime";
import {
  getResearchWorkRejectionReasonLabel,
  getResearchWorkRejectionReasonOptions,
} from "@/features/scientific/shared/utils/researchWorkRejectionReason";

export function useResearchWorkApprovalDisplayMapping(parameters: {
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;
}) {
  function mapResearchWorkTypeToDisplayName(
    researchWorkType: ResearchWorkType,
  ): string {
    const mapping: Record<ResearchWorkType, string> = {
      JOURNAL_ARTICLE: "Bài báo",
      RESEARCH_PROJECT: "Đề tài",
      CONFERENCE_PROCEEDING: "Hội thảo",
      BOOK_CHAPTER: "Chương sách",
      STUDENT_SUPERVISION: "Hướng dẫn sinh viên",
    };

    return mapping[researchWorkType];
  }

  function getApprovalStatusOptionList(): { value: string; label: string }[] {
    if (parameters.approvalScopeIdentifier === "FACULTY_SCOPE") {
      return [
        { value: "ALL_APPROVAL_STATUSES", label: "Tất cả trạng thái" },
        { value: "PENDING_FACULTY_APPROVAL", label: "Chờ khoa duyệt" },
        {
          value: "NEED_REVISION_BY_FACULTY",
          label: "Yêu cầu chỉnh sửa",
        },
        {
          value: "APPROVED_BY_FACULTY_FINAL",
          label: "Đã duyệt (cuối cùng tại khoa)",
        },
        { value: "REJECTED_BY_FACULTY", label: "Bị từ chối ở cấp khoa" },
      ];
    }

    return [
      { value: "ALL_APPROVAL_STATUSES", label: "Tất cả trạng thái" },
      {
        value: "PENDING_UNIVERSITY_APPROVAL",
        label: "Chờ duyệt cấp trường",
      },
      {
        value: "APPROVED_BY_UNIVERSITY_FINALIZED_HOURS",
        label: "Đã chốt giờ (cấp trường)",
      },
      {
        value: "REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY",
        label: "Đã trả về khoa",
      },
    ];
  }

  function getPendingApprovalStatusValue(): ResearchWorkApprovalStatus {
    return parameters.approvalScopeIdentifier === "FACULTY_SCOPE"
      ? "PENDING_FACULTY_APPROVAL"
      : "PENDING_UNIVERSITY_APPROVAL";
  }

  function mapApprovalStatusToDisplayName(
    approvalStatus: ResearchWorkApprovalStatus,
  ): string {
    const mapping: Record<ResearchWorkApprovalStatus, string> = {
      PENDING_FACULTY_APPROVAL: "Chờ khoa duyệt",
      APPROVED_BY_FACULTY_FINAL: "Đã duyệt (cuối cùng tại khoa)",
      APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY:
        "Đã duyệt (cuối cùng tại khoa)",
      NEED_REVISION_BY_FACULTY: "Yêu cầu chỉnh sửa",
      REJECTED_BY_FACULTY: "Bị từ chối ở cấp khoa",

      PENDING_UNIVERSITY_APPROVAL: "Chờ duyệt cấp trường",
      APPROVED_BY_UNIVERSITY_FINALIZED_HOURS: "Đã chốt giờ (cấp trường)",
      REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY: "Đã trả về khoa",
    };

    return mapping[approvalStatus];
  }

  function mapApprovalStatusToBadgeClass(
    approvalStatus: ResearchWorkApprovalStatus,
  ): string {
    const mapping: Record<ResearchWorkApprovalStatus, string> = {
      PENDING_FACULTY_APPROVAL:
        "inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-900",
      APPROVED_BY_FACULTY_FINAL:
        "inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-900",
      APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY:
        "inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-900",
      NEED_REVISION_BY_FACULTY:
        "inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-900",
      REJECTED_BY_FACULTY:
        "inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-900",

      PENDING_UNIVERSITY_APPROVAL:
        "inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-900",
      APPROVED_BY_UNIVERSITY_FINALIZED_HOURS:
        "inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-900",
      REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY:
        "inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-900",
    };

    return mapping[approvalStatus];
  }

  function getRejectionReasonOptionList(): {
    value: ResearchWorkRejectionReasonType;
    label: string;
  }[] {
    return getResearchWorkRejectionReasonOptions(
      parameters.approvalScopeIdentifier,
    ) as { value: ResearchWorkRejectionReasonType; label: string }[];
  }

  function mapRejectionReasonToDisplayName(
    rejectionReasonType: ResearchWorkRejectionReasonType | null,
  ): string {
    return getResearchWorkRejectionReasonLabel(rejectionReasonType);
  }

  function formatIntegerValue(value: number): string {
    return new Intl.NumberFormat("vi-VN").format(Math.round(value));
  }

  function formatDateTimeDisplayValue(isoString: string | null): string {
    if (!isoString) return "—";

    const dateValue = parseBackendDateTime(isoString);
    if (!dateValue) return isoString;

    return new Intl.DateTimeFormat("vi-VN", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
      timeZone: "Asia/Ho_Chi_Minh",
    }).format(dateValue);
  }

  return {
    mapResearchWorkTypeToDisplayName,

    getApprovalStatusOptionList,
    getPendingApprovalStatusValue,

    mapApprovalStatusToDisplayName,
    mapApprovalStatusToBadgeClass,

    getRejectionReasonOptionList,
    mapRejectionReasonToDisplayName,

    formatIntegerValue,
    formatDateTimeDisplayValue,
  };
}
