import { computed, ref } from "vue";

export type ResearchType = "ISI" | "SCOPUS" | "CONFERENCE" | "PROJECT" | "BOOK";

export type Department = {
  id: string;
  name: string;
};

export type Lecturer = {
  id: string;
  name: string;
  departmentId: string;
};

export type ResearchWork = {
  id: string;
  title: string;
  type: ResearchType;
  venue: string;
  lecturerIds: string[];
  departmentId: string;
  year: number;
  score: number;
};

function mulberry32(seed: number) {
  return function () {
    let value = (seed += 0x6d2b79f5);
    value = Math.imul(value ^ (value >>> 15), value | 1);
    value ^= value + Math.imul(value ^ (value >>> 7), value | 61);
    return ((value ^ (value >>> 14)) >>> 0) / 4294967296;
  };
}

function clampNumber(value: number, min: number, max: number) {
  return Math.max(min, Math.min(max, value));
}

/**
 * Never returns undefined. Throws early if list is empty.
 * This also makes TS happy (return type is T).
 */
function pickOneOrThrow<T>(
  random: () => number,
  list: readonly T[],
  errorMessage: string
): T {
  if (list.length === 0) throw new Error(errorMessage);
  return list[Math.floor(random() * list.length)] as T;
}

function uniqueStrings(values: string[]) {
  return values.filter((value, index) => values.indexOf(value) === index);
}

export function useResearchMockData() {
  const departments = ref<Department[]>([
    { id: "dept-cntt", name: "Khoa CNTT" },
    { id: "dept-toan", name: "Khoa Toán" },
    { id: "dept-ly", name: "Khoa Vật lý" },
    { id: "dept-hoa", name: "Khoa Hóa" },
    { id: "dept-sinh", name: "Khoa Sinh" },
    { id: "dept-kt", name: "Khoa Kinh tế" },
    { id: "dept-qlgd", name: "Khoa QLGD" },
  ]);

  const lecturers = ref<Lecturer[]>([
    { id: "lec-001", name: "Nguyễn Văn An", departmentId: "dept-cntt" },
    { id: "lec-002", name: "Trần Thị Bình", departmentId: "dept-cntt" },
    { id: "lec-003", name: "Lê Quốc Cường", departmentId: "dept-toan" },
    { id: "lec-004", name: "Phạm Gia Dũng", departmentId: "dept-ly" },
    { id: "lec-005", name: "Vũ Minh Em", departmentId: "dept-hoa" },
    { id: "lec-006", name: "Hoàng Thị Giang", departmentId: "dept-sinh" },
    { id: "lec-007", name: "Đặng Văn Hải", departmentId: "dept-kt" },
    { id: "lec-008", name: "Bùi Thu Hương", departmentId: "dept-kt" },
    { id: "lec-009", name: "Ngô Đức Khánh", departmentId: "dept-qlgd" },
  ]);

  const formatResearchTypeLabel = (type: ResearchType) => {
    switch (type) {
      case "ISI":
        return "ISI";
      case "SCOPUS":
        return "Scopus";
      case "CONFERENCE":
        return "Hội nghị";
      case "PROJECT":
        return "Đề tài";
      case "BOOK":
        return "Sách / Giáo trình";
    }
  };

  // Non-empty pools (readonly arrays) so pickOneOrThrow is always safe
  const years = [2020, 2021, 2022, 2023, 2024, 2025] as const;
  const typePool: readonly ResearchType[] = [
    "ISI",
    "SCOPUS",
    "CONFERENCE",
    "PROJECT",
    "BOOK",
  ] as const;

  const journalSamples = [
    "Journal of Advanced Studies",
    "International Science Review",
    "Vietnam Journal of Research",
    "Applied Computing Letters",
  ] as const;

  const conferenceSamples = [
    "ICSE Workshop",
    "IEEE Regional Conference",
    "National Symposium on Science",
    "ACM Student Research Forum",
  ] as const;

  const projectSamples = [
    "Đề tài cấp Trường",
    "Đề tài cấp Bộ",
    "Nhiệm vụ cấp Tỉnh",
    "Dự án hợp tác doanh nghiệp",
  ] as const;

  const bookSamples = [
    "NXB Giáo dục",
    "NXB Khoa học",
    "NXB Đại học Quốc gia",
  ] as const;

  const titlePrefixes = [
    "Nghiên cứu",
    "Phân tích",
    "Xây dựng",
    "Ứng dụng",
    "Đánh giá",
    "Tối ưu",
    "Thiết kế",
  ] as const;

  const titleTopics = [
    "AI trong giáo dục",
    "hệ thống khuyến nghị",
    "mô hình dự báo",
    "tối ưu hóa mạng",
    "phân tích dữ liệu lớn",
    "hóa học vật liệu",
    "sinh học phân tử",
    "kinh tế học hành vi",
  ] as const;

  const works = ref<ResearchWork[]>([]);

  // Generate deterministic mock works
  const random = mulberry32(20251217);

  // Small runtime guards (helpful if someone edits lists later)
  if (departments.value.length === 0)
    throw new Error("Mock departments must not be empty.");
  if (lecturers.value.length === 0)
    throw new Error("Mock lecturers must not be empty.");

  const totalWorks = 68;

  for (let index = 0; index < totalWorks; index += 1) {
    const department = pickOneOrThrow(
      random,
      departments.value,
      "No departments available."
    );

    // If this department has no lecturers, fall back to all lecturers (so never empty)
    const lecturersInDepartment = lecturers.value.filter(
      (l) => l.departmentId === department.id
    );
    const lecturerPool =
      lecturersInDepartment.length > 0
        ? lecturersInDepartment
        : lecturers.value;

    const type = pickOneOrThrow(
      random,
      typePool,
      "No research types available."
    );
    const year = pickOneOrThrow(random, years, "No years available.");

    const lecturerCount = clampNumber(
      1 + Math.floor(random() * 3),
      1,
      Math.min(3, lecturerPool.length)
    );
    const lecturerIds = uniqueStrings(
      Array.from({ length: lecturerCount }).map(
        () => pickOneOrThrow(random, lecturerPool, "No lecturers available.").id
      )
    );

    const title = `${pickOneOrThrow(
      random,
      titlePrefixes,
      "No title prefixes."
    )} ${pickOneOrThrow(random, titleTopics, "No title topics.")}`;

    const scoreBase =
      type === "ISI"
        ? 10
        : type === "SCOPUS"
        ? 8
        : type === "CONFERENCE"
        ? 5
        : type === "PROJECT"
        ? 7
        : 6;

    const score = clampNumber(
      Math.round((scoreBase + random() * 2 - 1) * 10) / 10,
      1,
      10
    );

    const venue =
      type === "ISI"
        ? pickOneOrThrow(random, journalSamples, "No journal samples.")
        : type === "SCOPUS"
        ? pickOneOrThrow(random, journalSamples, "No journal samples.")
        : type === "CONFERENCE"
        ? pickOneOrThrow(random, conferenceSamples, "No conference samples.")
        : type === "PROJECT"
        ? pickOneOrThrow(random, projectSamples, "No project samples.")
        : pickOneOrThrow(random, bookSamples, "No book samples.");

    works.value.push({
      id: `work-${String(index + 1).padStart(3, "0")}`,
      title,
      type,
      venue,
      lecturerIds,
      departmentId: department.id,
      year,
      score,
    });
  }

  return {
    departments: computed(() => departments.value),
    lecturers: computed(() => lecturers.value),
    works: computed(() => works.value),
    formatResearchTypeLabel,
  };
}
