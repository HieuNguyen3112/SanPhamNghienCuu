import type {
  LecturerResearchHourWarningResponse,
  LecturerResearchHourWarningEntry,
  FacultyOption,
} from "./lecturerResearchHourWarningModels";

export function createLecturerResearchHourWarningResponseForDemonstration(
  calculateRemainingResearchHours: (
    minimumRequiredResearchHours: number,
    currentLecturerResearchHours: number
  ) => number
): LecturerResearchHourWarningResponse {
  const facultyOptions: FacultyOption[] = [
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
    { facultyIdentifier: "FACULTY_LAW", facultyDisplayName: "Khoa Luật" },
  ];

  const academicYearOptions: string[] = ["2022-2023", "2023-2024", "2024-2025"];

  const lecturerResearchHourWarningEntries: LecturerResearchHourWarningEntry[] =
    [
      createLecturerResearchHourWarningEntryForDemonstration(
        {
          lecturerIdentifier: "LECTURER_101",
          lecturerDisplayName: "Trần Thị Bình",
          facultyIdentifier: "FACULTY_INFORMATION_TECHNOLOGY",
          facultyDisplayName: "Khoa Công nghệ thông tin",
          academicYear: "2024-2025",
          minimumRequiredResearchHours: 100,
          currentLecturerResearchHours: 70,
          researchHoursFromJournalArticles: 20,
          researchHoursFromResearchProjects: 30,
          researchHoursFromConferenceProceedings: 10,
          researchHoursFromStudentSupervision: 10,
          academicNotes:
            "Đang tham gia đề tài cấp trường, dự kiến nghiệm thu học kỳ 2.",
        },
        calculateRemainingResearchHours
      ),
      createLecturerResearchHourWarningEntryForDemonstration(
        {
          lecturerIdentifier: "LECTURER_205",
          lecturerDisplayName: "Phạm Quốc Dũng",
          facultyIdentifier: "FACULTY_ECONOMICS",
          facultyDisplayName: "Khoa Kinh tế",
          academicYear: "2023-2024",
          minimumRequiredResearchHours: 90,
          currentLecturerResearchHours: 40,
          researchHoursFromJournalArticles: 10,
          researchHoursFromResearchProjects: 15,
          researchHoursFromConferenceProceedings: 5,
          researchHoursFromStudentSupervision: 10,
          academicNotes: null,
        },
        calculateRemainingResearchHours
      ),
      createLecturerResearchHourWarningEntryForDemonstration(
        {
          lecturerIdentifier: "LECTURER_309",
          lecturerDisplayName: "Nguyễn Thị Lan",
          facultyIdentifier: "FACULTY_LAW",
          facultyDisplayName: "Khoa Luật",
          academicYear: "2024-2025",
          minimumRequiredResearchHours: 90,
          currentLecturerResearchHours: 78,
          researchHoursFromJournalArticles: 20,
          researchHoursFromResearchProjects: 18,
          researchHoursFromConferenceProceedings: 20,
          researchHoursFromStudentSupervision: 20,
          academicNotes:
            "Cần bổ sung bài báo theo định hướng công bố của khoa.",
        },
        calculateRemainingResearchHours
      ),
      createLecturerResearchHourWarningEntryForDemonstration(
        {
          lecturerIdentifier: "LECTURER_412",
          lecturerDisplayName: "Võ Thị Hạnh",
          facultyIdentifier: "FACULTY_EDUCATION",
          facultyDisplayName: "Khoa Sư phạm",
          academicYear: "2023-2024",
          minimumRequiredResearchHours: 100,
          currentLecturerResearchHours: 55,
          researchHoursFromJournalArticles: 15,
          researchHoursFromResearchProjects: 20,
          researchHoursFromConferenceProceedings: 10,
          researchHoursFromStudentSupervision: 10,
          academicNotes:
            "Đề xuất tham gia hội thảo quốc gia để bổ sung giờ quy đổi hợp lệ.",
        },
        calculateRemainingResearchHours
      ),
    ];

  return {
    facultyOptions,
    academicYearOptions,
    lecturerResearchHourWarningEntries,
  };
}

function createLecturerResearchHourWarningEntryForDemonstration(
  input: Omit<
    LecturerResearchHourWarningEntry,
    | "remainingResearchHoursToMeetStandard"
    | "lecturerResearchHourWarningNotificationRequestState"
    | "lecturerResearchHourWarningNotificationRequestedAtDateTimeString"
  >,
  calculateRemainingResearchHours: (
    minimumRequiredResearchHours: number,
    currentLecturerResearchHours: number
  ) => number
): LecturerResearchHourWarningEntry {
  return {
    ...input,
    remainingResearchHoursToMeetStandard: calculateRemainingResearchHours(
      input.minimumRequiredResearchHours,
      input.currentLecturerResearchHours
    ),
    lecturerResearchHourWarningNotificationRequestState: "NOT_REQUESTED",
    lecturerResearchHourWarningNotificationRequestedAtDateTimeString: null,
  };
}
