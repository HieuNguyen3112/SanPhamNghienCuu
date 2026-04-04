import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalScopeIdentifier,
  ResearchWorkApprovalStatus,
  ResearchWorkRejectionReasonType,
  ResearchWorkType,
} from "../models/researchWorkApprovalModels";

type FacultyDirectoryEntry = {
  facultyIdentifier: string;
  facultyDisplayName: string;
};

function createSeededRandom(seedValue: number): () => number {
  let value = seedValue >>> 0;
  return () => {
    value += 0x6d2b79f5;
    let nextValue = Math.imul(value ^ (value >>> 15), 1 | value);
    nextValue ^=
      nextValue + Math.imul(nextValue ^ (nextValue >>> 7), 61 | nextValue);
    return ((nextValue ^ (nextValue >>> 14)) >>> 0) / 4294967296;
  };
}

function pickOne<T>(
  randomNumberGenerator: () => number,
  list: readonly T[]
): T {
  if (list.length === 0) {
    throw new Error("pickOne(): list is empty");
  }
  const index = Math.floor(randomNumberGenerator() * list.length);
  return list[index]!;
}

function createIsoDateTimeStringFromNowMinusDays(dayCount: number): string {
  const date = new Date();
  date.setDate(date.getDate() - dayCount);
  return date.toISOString();
}

const facultyDirectory: readonly FacultyDirectoryEntry[] = [
  {
    facultyIdentifier: "FACULTY_INFORMATION_TECHNOLOGY",
    facultyDisplayName: "Khoa Công nghệ thông tin",
  },
  {
    facultyIdentifier: "FACULTY_ECONOMICS",
    facultyDisplayName: "Khoa Kinh tế",
  },
  {
    facultyIdentifier: "FACULTY_EDUCATION",
    facultyDisplayName: "Khoa Sư phạm",
  },
] as const;

const researchWorkTypeOptions: readonly ResearchWorkType[] = [
  "JOURNAL_ARTICLE",
  "RESEARCH_PROJECT",
  "CONFERENCE_PROCEEDING",
  "BOOK_CHAPTER",
  "STUDENT_SUPERVISION",
] as const;

function getFacultyDirectoryEntryForScope(parameters: {
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;
  facultyIdentifier?: string;
  randomNumberGenerator: () => number;
}): FacultyDirectoryEntry {
  if (parameters.approvalScopeIdentifier === "FACULTY_SCOPE") {
    const matched =
      facultyDirectory.find(
        (item) => item.facultyIdentifier === parameters.facultyIdentifier
      ) ?? facultyDirectory[0]!;
    return matched;
  }

  return pickOne(parameters.randomNumberGenerator, facultyDirectory);
}

export function createResearchWorkApprovalListMockData(parameters: {
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;
  facultyIdentifier?: string;
  totalResearchWorkCount: number;
  seedValue: number;
}): ResearchWorkApprovalEntry[] {
  const random = createSeededRandom(parameters.seedValue);
  const academicYearOptions = ["2022-2023", "2023-2024", "2024-2025"] as const;

  const list: ResearchWorkApprovalEntry[] = [];

  for (let index = 0; index < parameters.totalResearchWorkCount; index += 1) {
    const faculty = getFacultyDirectoryEntryForScope({
      approvalScopeIdentifier: parameters.approvalScopeIdentifier,
      facultyIdentifier: parameters.facultyIdentifier,
      randomNumberGenerator: random,
    });

    const researchWorkType = pickOne(random, researchWorkTypeOptions);
    const academicYear = pickOne(random, academicYearOptions);

    const submittedAtDateTimeString = createIsoDateTimeStringFromNowMinusDays(
      20 + Math.floor(random() * 120)
    );

    const lecturerDeclaredResearchHours = 10 + Math.floor(random() * 120);
    const recommendedResearchHoursByPolicy = 10 + Math.floor(random() * 120);
    const officialResearchHours = recommendedResearchHoursByPolicy;

    let approvalStatus: ResearchWorkApprovalStatus;

    if (parameters.approvalScopeIdentifier === "FACULTY_SCOPE") {
      approvalStatus = pickOne(random, [
        "PENDING_FACULTY_APPROVAL",
        "PENDING_FACULTY_APPROVAL",
        "APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY",
        "NEED_REVISION_BY_FACULTY",
        "REJECTED_BY_FACULTY",
      ] as const);
    } else {
      approvalStatus = pickOne(random, [
        "PENDING_UNIVERSITY_APPROVAL",
        "PENDING_UNIVERSITY_APPROVAL",
        "APPROVED_BY_UNIVERSITY_FINALIZED_HOURS",
        "REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY",
      ] as const);
    }

    const facultyApprovedAtDateTimeString =
      parameters.approvalScopeIdentifier === "UNIVERSITY_SCOPE"
        ? createIsoDateTimeStringFromNowMinusDays(5 + Math.floor(random() * 30))
        : approvalStatus === "APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY"
        ? createIsoDateTimeStringFromNowMinusDays(5 + Math.floor(random() * 30))
        : null;

    const facultyReviewedAtDateTimeString =
      approvalStatus === "PENDING_FACULTY_APPROVAL"
        ? null
        : createIsoDateTimeStringFromNowMinusDays(5 + Math.floor(random() * 40));

    const universityReviewedAtDateTimeString =
      approvalStatus === "PENDING_UNIVERSITY_APPROVAL"
        ? null
        : parameters.approvalScopeIdentifier === "UNIVERSITY_SCOPE"
        ? createIsoDateTimeStringFromNowMinusDays(1 + Math.floor(random() * 20))
        : null;

    const evidenceAttachmentCount = Math.floor(random() * 5);
    const evidenceAttachmentList = Array.from({
      length: evidenceAttachmentCount,
    }).map((_, attachmentIndex) => ({
      evidenceAttachmentIdentifier: index * 100 + attachmentIndex,
      evidenceAttachmentDisplayName: `Minh chứng ${attachmentIndex + 1}.pdf`,
      evidenceAttachmentFileType: "PDF",
      evidenceAttachmentPreviewUrl: "#",
    }));

    const authorCount = 1 + Math.floor(random() * 4);
    const researchWorkAuthorList = Array.from({ length: authorCount }).map(
      (_, authorIndex) => {
        const randomFacultyForCoAuthor = pickOne(random, facultyDirectory);
        const authorFaculty =
          authorIndex === 0 ? faculty : randomFacultyForCoAuthor;

        return {
          authorIdentifier: index * 10 + authorIndex,
          lecturerId: index * 10 + authorIndex,
          authorDisplayName:
            authorIndex === 0
              ? "Nguyễn Văn A"
              : `Đồng tác giả ${authorIndex + 1}`,
          authorFacultyIdentifier: authorFaculty.facultyIdentifier,
          authorFacultyDisplayName: authorFaculty.facultyDisplayName,
          isPrimaryAuthor: authorIndex === 0,
          isSubmittingLecturer: authorIndex === 0,
        };
      }
    );

    let facultyRejectionReasonType: ResearchWorkRejectionReasonType | null =
      null;
    let facultyRejectionReasonDetail: string | null = null;

    if (
      approvalStatus === "REJECTED_BY_FACULTY" ||
      approvalStatus === "NEED_REVISION_BY_FACULTY"
    ) {
      facultyRejectionReasonType = pickOne(random, [
        "MISSING_EVIDENCE",
        "INACCURATE_INFORMATION",
        "OUTSIDE_FACULTY_SCOPE",
        "OTHER",
      ] as const);

      facultyRejectionReasonDetail =
        facultyRejectionReasonType === "OTHER"
          ? "Cần bổ sung thông tin/định dạng theo mẫu."
          : null;
    }

    let universityRejectionReasonType: ResearchWorkRejectionReasonType | null =
      null;
    let universityRejectionReasonDetail: string | null = null;

    if (approvalStatus === "REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY") {
      universityRejectionReasonType = pickOne(random, [
        "WRONG_HOUR_CONVERSION",
        "EVIDENCE_NOT_QUALIFIED",
        "NOT_COMPLIANT_WITH_RESEARCH_POLICY",
        "OTHER",
      ] as const);

      universityRejectionReasonDetail =
        universityRejectionReasonType === "OTHER"
          ? "Vui lòng đối soát lại minh chứng và quy định quy đổi."
          : null;
    }

    const approvalHistoryList = [
      {
        historyIdentifier: index * 1000 + 1,
        reviewLevelDisplayName: "Giảng viên",
        reviewActionDisplayName: "Kê khai",
        reviewedAtDateTimeString: submittedAtDateTimeString,
        reviewNote: "Nộp hồ sơ và minh chứng ban đầu.",
      },
      ...(facultyReviewedAtDateTimeString
        ? [
            {
              historyIdentifier: index * 1000 + 2,
              reviewLevelDisplayName: "Cấp khoa",
              reviewActionDisplayName:
                approvalStatus === "REJECTED_BY_FACULTY"
                  ? "Từ chối"
                  : approvalStatus === "NEED_REVISION_BY_FACULTY"
                  ? "Yêu cầu chỉnh sửa"
                  : "Duyệt",
              reviewedAtDateTimeString: facultyReviewedAtDateTimeString,
              reviewNote:
                approvalStatus === "REJECTED_BY_FACULTY"
                  ? "Khoa từ chối hồ sơ."
                  : approvalStatus === "NEED_REVISION_BY_FACULTY"
                  ? "Khoa yêu cầu chỉnh sửa và nộp lại."
                  : "Khoa xác nhận hồ sơ hợp lệ và chuyển lên cấp trường.",
            },
          ]
        : []),
      ...(universityReviewedAtDateTimeString
        ? [
            {
              historyIdentifier: index * 1000 + 3,
              reviewLevelDisplayName: "Cấp trường",
              reviewActionDisplayName:
                approvalStatus === "REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY"
                  ? "Trả về khoa"
                  : "Chốt giờ",
              reviewedAtDateTimeString: universityReviewedAtDateTimeString,
              reviewNote:
                approvalStatus === "REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY"
                  ? "Cấp trường trả về khoa để đối soát."
                  : "Cấp trường chốt giờ chính thức.",
            },
          ]
        : []),
    ];

    list.push({
      researchWorkIdentifier: 10000 + index,
      researchWorkTitle: `Công trình nghiên cứu số ${
        index + 1
      } (${academicYear})`,
      submittingLecturerDisplayName:
        researchWorkAuthorList.find((a) => a.isSubmittingLecturer)
          ?.authorDisplayName ??
        researchWorkAuthorList[0]?.authorDisplayName ??
        "Nguyễn Văn A",

      facultyIdentifier: faculty.facultyIdentifier,
      facultyDisplayName: faculty.facultyDisplayName,

      academicYear,
      researchWorkType,

      submittedAtDateTimeString,

      facultyReviewedAtDateTimeString,
      facultyApprovedAtDateTimeString,
      facultyApprovalNote: facultyApprovedAtDateTimeString
        ? "Hồ sơ đạt yêu cầu theo khoa."
        : null,
      facultyRejectionReasonType,
      facultyRejectionReasonDetail,

      universityReviewedAtDateTimeString,
      universityRejectionReasonType,
      universityRejectionReasonDetail,

      lecturerDeclaredResearchHours,
      recommendedResearchHoursByPolicy,
      officialResearchHours,

      approvalStatus,

      evidenceAttachmentList,
      researchWorkAuthorList,
      approvalHistoryList,
      coAuthorList: [],
    });
  }

  return list;
}
