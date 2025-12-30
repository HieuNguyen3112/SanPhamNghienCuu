import type {
  PublicResearchItemDto,
  PublicResearchListQueryDto,
  PublicResearchListResponseDto,
  PublicResearchWorkTypeDto,
} from "../dto/publicResearchDtos";

type LecturerSeed = { id: number; code: string; name: string; faculty_id: number };
type FacultySeed = { id: number; name: string };
type AcademicYearSeed = { id: number; code: string };

const faculties: FacultySeed[] = [
  { id: 1, name: "Khoa Công nghệ thông tin" },
  { id: 2, name: "Khoa Kinh tế" },
  { id: 3, name: "Khoa Sư phạm" },
];

const lecturers: LecturerSeed[] = [
  { id: 101, code: "GV001", name: "Nguyễn Văn An", faculty_id: 1 },
  { id: 102, code: "GV002", name: "Trần Thị Bình", faculty_id: 1 },
  { id: 103, code: "GV003", name: "Lê Minh Châu", faculty_id: 2 },
  { id: 104, code: "GV004", name: "Phạm Quốc Dũng", faculty_id: 2 },
  { id: 105, code: "GV005", name: "Võ Hoàng Em", faculty_id: 3 },
  { id: 106, code: "GV006", name: "Đặng Thị Hoa", faculty_id: 3 },
];

const academicYears: AcademicYearSeed[] = [
  { id: 201, code: "2021-2022" },
  { id: 202, code: "2022-2023" },
  { id: 203, code: "2023-2024" },
  { id: 204, code: "2024-2025" },
];

const workTypes: PublicResearchWorkTypeDto[] = [
  "ARTICLE",
  "BOOK",
  "PROJECT",
  "CONFERENCE",
  "OTHER",
];

function removeDiacritics(input: string): string {
  return input
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D");
}

function normalizeSearch(input: string): string {
  return removeDiacritics(input).toLowerCase().trim();
}

function facultyNameById(faculty_id: number): string {
  return faculties.find((f) => f.id === faculty_id)?.name ?? "Không rõ khoa";
}

function yearCodeById(academic_year_id: number): string {
  return academicYears.find((y) => y.id === academic_year_id)?.code ?? "Không rõ";
}

function makeAbstract(seed: number): string {
  const snippets = [
    "Nghiên cứu tập trung vào phương pháp tiếp cận thực nghiệm và đánh giá định lượng.",
    "Bài viết đề xuất mô hình cải tiến nhằm nâng cao hiệu quả và độ tin cậy.",
    "Công trình trình bày kết quả phân tích dữ liệu và so sánh với các hướng tiếp cận hiện có.",
    "Đề tài hướng tới ứng dụng thực tiễn, kèm theo bộ tiêu chí đánh giá rõ ràng.",
    "Nghiên cứu tổng quan, hệ thống hóa tài liệu và đề xuất khung triển khai.",
  ];
  // ✅ noUncheckedIndexedAccess => dùng !
  return snippets[seed % snippets.length]!;
}

const workTypeLabel: Record<PublicResearchWorkTypeDto, string> = {
  ARTICLE: "Bài báo",
  BOOK: "Sách chuyên khảo",
  PROJECT: "Đề tài",
  CONFERENCE: "Hội thảo",
  OTHER: "Công trình",
};

function makeTitle(workType: PublicResearchWorkTypeDto, index: number): string {
  const topics = [
    "hệ thống thông tin",
    "phân tích dữ liệu",
    "giáo dục số",
    "kinh tế số",
    "AI ứng dụng",
    "quản trị đổi mới",
    "an ninh mạng",
    "tối ưu hoá",
    "hành vi người dùng",
    "mô hình dự báo",
  ];

  const base = workTypeLabel[workType]; // ✅ luôn là string, không undefined
  const topic = topics[index % topics.length]!; // ✅ noUncheckedIndexedAccess
  return `${base}: ${topic} (mẫu #${index + 1})`;
}

// >= 20 items, 100% APPROVED
export const PUBLIC_RESEARCH_ITEMS_SEED: PublicResearchItemDto[] = Array.from(
  { length: 24 },
  (_, i) => {
    // ✅ noUncheckedIndexedAccess => dùng !
    const lecturer = lecturers[i % lecturers.length]!;
    const workType = workTypes[i % workTypes.length]!;
    const year = academicYears[i % academicYears.length]!;

    return {
      id: 1000 + i,
      title: makeTitle(workType, i),
      abstract: makeAbstract(i),

      lecturer_id: lecturer.id,
      lecturer_code: lecturer.code,
      lecturer_name: lecturer.name,

      faculty_id: lecturer.faculty_id,
      faculty_name: facultyNameById(lecturer.faculty_id),

      work_type: workType,

      academic_year_id: year.id,
      academic_year_code: yearCodeById(year.id),

      approval_status: "APPROVED",
    };
  }
);

export function queryPublicResearchItems(
  query: PublicResearchListQueryDto
): PublicResearchListResponseDto {
  const q = normalizeSearch(query.lecturer_query);

  const filtered = PUBLIC_RESEARCH_ITEMS_SEED.filter((item) => {
    if (item.approval_status !== "APPROVED") return false;

    const matchesLecturer =
      q.length === 0
        ? true
        : normalizeSearch(item.lecturer_name).includes(q) ||
          normalizeSearch(item.lecturer_code).includes(q);

    const matchesFaculty =
      query.faculty_id === null ? true : item.faculty_id === query.faculty_id;

    const matchesType =
      query.work_type === null ? true : item.work_type === query.work_type;

    const matchesYear =
      query.academic_year_id === null
        ? true
        : item.academic_year_id === query.academic_year_id;

    return matchesLecturer && matchesFaculty && matchesType && matchesYear;
  });

  const page = Math.max(1, query.page);
  const pageSize = Math.min(Math.max(5, query.page_size), 50);
  const start = (page - 1) * pageSize;
  const items = filtered.slice(start, start + pageSize);

  return { items, total: filtered.length };
}

export function getPublicResearchStaticFaculties(): FacultySeed[] {
  return [...faculties];
}

export function getPublicResearchStaticAcademicYears(): AcademicYearSeed[] {
  return [...academicYears];
}

export function getPublicResearchStaticWorkTypes(): PublicResearchWorkTypeDto[] {
  return [...workTypes];
}
