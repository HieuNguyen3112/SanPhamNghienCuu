import type {
  LecturerRecord,
  GenderCategory,
  EducationLevelCategory,
  AcademicRankCategory,
} from "./lecturerStatisticsTypes";

type NonEmptyReadonlyArray<ValueType> = readonly [ValueType, ...ValueType[]];

function selectCyclicValueFromNonEmptyArray<ValueType>(
  optionValues: NonEmptyReadonlyArray<ValueType>,
  selectionIndexNumber: number
): ValueType {
  const normalizedIndexNumber =
    ((selectionIndexNumber % optionValues.length) + optionValues.length) %
    optionValues.length;
  return optionValues[normalizedIndexNumber]!;
}

export const lecturerMockRecords: LecturerRecord[] = [
  {
    lecturerIdentifier: "LECTURER-0001",
    lecturerFullName: "Nguyễn Minh Anh",
    departmentName: "Công nghệ thông tin",
    genderCategory: "Female",
    educationLevelCategory: "Doctor",
    academicRankCategory: "AssociateProfessor",
    teachingExperienceYears: 11,
  },
  {
    lecturerIdentifier: "LECTURER-0002",
    lecturerFullName: "Trần Quốc Bảo",
    departmentName: "Công nghệ thông tin",
    genderCategory: "Male",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 6,
  },
  {
    lecturerIdentifier: "LECTURER-0003",
    lecturerFullName: "Lê Thu Hà",
    departmentName: "Kinh tế",
    genderCategory: "Female",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 9,
  },
  {
    lecturerIdentifier: "LECTURER-0004",
    lecturerFullName: "Phạm Đức Long",
    departmentName: "Kinh tế",
    genderCategory: "Male",
    educationLevelCategory: "Doctor",
    academicRankCategory: "Professor",
    teachingExperienceYears: 22,
  },
  {
    lecturerIdentifier: "LECTURER-0005",
    lecturerFullName: "Đỗ Ngọc Mai",
    departmentName: "Toán học",
    genderCategory: "Female",
    educationLevelCategory: "Doctor",
    academicRankCategory: "AssociateProfessor",
    teachingExperienceYears: 14,
  },
  {
    lecturerIdentifier: "LECTURER-0006",
    lecturerFullName: "Vũ Thành Nam",
    departmentName: "Toán học",
    genderCategory: "Male",
    educationLevelCategory: "Bachelor",
    academicRankCategory: "None",
    teachingExperienceYears: 3,
  },
  {
    lecturerIdentifier: "LECTURER-0007",
    lecturerFullName: "Nguyễn Thanh Tùng",
    departmentName: "Vật lý",
    genderCategory: "Male",
    educationLevelCategory: "Doctor",
    academicRankCategory: "None",
    teachingExperienceYears: 8,
  },
  {
    lecturerIdentifier: "LECTURER-0008",
    lecturerFullName: "Bùi Phương Thảo",
    departmentName: "Vật lý",
    genderCategory: "Female",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 5,
  },
  {
    lecturerIdentifier: "LECTURER-0009",
    lecturerFullName: "Phan Văn Huy",
    departmentName: "Hóa học",
    genderCategory: "Male",
    educationLevelCategory: "Doctor",
    academicRankCategory: "AssociateProfessor",
    teachingExperienceYears: 16,
  },
  {
    lecturerIdentifier: "LECTURER-0010",
    lecturerFullName: "Đặng Thị Lan",
    departmentName: "Hóa học",
    genderCategory: "Female",
    educationLevelCategory: "Bachelor",
    academicRankCategory: "None",
    teachingExperienceYears: 4,
  },

  // Thêm đủ số lượng để thể hiện phân trang rõ ràng
  {
    lecturerIdentifier: "LECTURER-0011",
    lecturerFullName: "Hoàng Minh Quân",
    departmentName: "Sinh học",
    genderCategory: "Male",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 7,
  },
  {
    lecturerIdentifier: "LECTURER-0012",
    lecturerFullName: "Nguyễn Thảo Vy",
    departmentName: "Sinh học",
    genderCategory: "Female",
    educationLevelCategory: "Doctor",
    academicRankCategory: "None",
    teachingExperienceYears: 10,
  },
  {
    lecturerIdentifier: "LECTURER-0013",
    lecturerFullName: "Trịnh Khánh Linh",
    departmentName: "Ngữ văn",
    genderCategory: "Female",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 12,
  },
  {
    lecturerIdentifier: "LECTURER-0014",
    lecturerFullName: "Ngô Văn Phúc",
    departmentName: "Ngữ văn",
    genderCategory: "Male",
    educationLevelCategory: "Bachelor",
    academicRankCategory: "None",
    teachingExperienceYears: 2,
  },
  {
    lecturerIdentifier: "LECTURER-0015",
    lecturerFullName: "Mai Hải Yến",
    departmentName: "Lịch sử",
    genderCategory: "Female",
    educationLevelCategory: "Doctor",
    academicRankCategory: "AssociateProfessor",
    teachingExperienceYears: 18,
  },
  {
    lecturerIdentifier: "LECTURER-0016",
    lecturerFullName: "Nguyễn Văn Sơn",
    departmentName: "Lịch sử",
    genderCategory: "Male",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 9,
  },
  {
    lecturerIdentifier: "LECTURER-0017",
    lecturerFullName: "Trần Thu Trang",
    departmentName: "Ngoại ngữ",
    genderCategory: "Female",
    educationLevelCategory: "Doctor",
    academicRankCategory: "None",
    teachingExperienceYears: 13,
  },
  {
    lecturerIdentifier: "LECTURER-0018",
    lecturerFullName: "Lê Anh Dũng",
    departmentName: "Ngoại ngữ",
    genderCategory: "Male",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 6,
  },
  {
    lecturerIdentifier: "LECTURER-0019",
    lecturerFullName: "Phạm Thị Thu",
    departmentName: "Luật",
    genderCategory: "Female",
    educationLevelCategory: "Master",
    academicRankCategory: "None",
    teachingExperienceYears: 8,
  },
  {
    lecturerIdentifier: "LECTURER-0020",
    lecturerFullName: "Đinh Quốc Khánh",
    departmentName: "Luật",
    genderCategory: "Male",
    educationLevelCategory: "Doctor",
    academicRankCategory: "Professor",
    teachingExperienceYears: 25,
  },

  // Dãy bổ sung để phân trang (cố ý đa dạng)
  ...Array.from({ length: 24 }).map((_, generatedOffsetIndexNumber) => {
    const generatedLecturerSequenceNumber = generatedOffsetIndexNumber + 21;

    const departmentNameOptions: NonEmptyReadonlyArray<string> = [
      "Công nghệ thông tin",
      "Kinh tế",
      "Toán học",
      "Vật lý",
      "Hóa học",
      "Sinh học",
      "Ngữ văn",
      "Lịch sử",
      "Ngoại ngữ",
      "Luật",
    ];

    const genderCategoryOptions: NonEmptyReadonlyArray<GenderCategory> = [
      "Male",
      "Female",
      "Other",
    ];

    const educationLevelCategoryOptions: NonEmptyReadonlyArray<EducationLevelCategory> =
      ["Bachelor", "Master", "Doctor"];

    const academicRankCategoryOptions: NonEmptyReadonlyArray<AcademicRankCategory> =
      ["None", "AssociateProfessor", "Professor"];

    const selectedDepartmentName = selectCyclicValueFromNonEmptyArray(
      departmentNameOptions,
      generatedLecturerSequenceNumber
    );

    const selectedGenderCategory = selectCyclicValueFromNonEmptyArray(
      genderCategoryOptions,
      generatedLecturerSequenceNumber
    );

    const selectedEducationLevelCategory = selectCyclicValueFromNonEmptyArray(
      educationLevelCategoryOptions,
      generatedLecturerSequenceNumber
    );

    const selectedAcademicRankCategory = selectCyclicValueFromNonEmptyArray(
      academicRankCategoryOptions,
      generatedLecturerSequenceNumber
    );

    const generatedLecturerRecord: LecturerRecord = {
      lecturerIdentifier: `LECTURER-${String(
        generatedLecturerSequenceNumber
      ).padStart(4, "0")}`,
      lecturerFullName: `Giảng viên mẫu số ${generatedLecturerSequenceNumber}`,
      departmentName: selectedDepartmentName,
      genderCategory: selectedGenderCategory,
      educationLevelCategory: selectedEducationLevelCategory,
      academicRankCategory: selectedAcademicRankCategory,
      teachingExperienceYears: (generatedLecturerSequenceNumber % 26) + 1,
    };

    return generatedLecturerRecord;
  }),
];
