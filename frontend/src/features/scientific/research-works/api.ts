import type {
  DepartmentOption,
  FetchMyWorksParams,
  FetchOverviewParams,
  FetchOverviewResult,
  Lecturer,
  LecturerProjectsSummary,
  OverviewStats,
  PagedResult,
  ScopeOption,
  SelectOption,
  WorkItem,
} from "@/features/scientific/research-works/types";

/**
 * ================================
 * 1. MOCK DATA CHUNG
 * ================================
 */

// Mock dữ liệu giảng viên + tổng hợp giờ/công trình (cho màn QLKH/BCN xem tổng hợp)
const MOCK_LECTURERS: Lecturer[] = [
  {
    id: "1",
    code: "GV001",
    fullName: "Nguyễn Văn A",
    departmentName: "Bộ môn Công nghệ thông tin",
  },
  {
    id: "2",
    code: "GV002",
    fullName: "Trần Thị B",
    departmentName: "Bộ môn Toán",
  },
  {
    id: "3",
    code: "GV003",
    fullName: "Lê Văn C",
    departmentName: "Bộ môn Công nghệ thông tin",
  },
];

const MOCK_SUMMARIES: LecturerProjectsSummary[] = [
  {
    lecturer: MOCK_LECTURERS[0]!,
    totalProjects: 5,
    approvedProjects: 3,
    pendingProjects: 2,
    rejectedProjects: 0,
    totalHours: 120,
  },
  {
    lecturer: MOCK_LECTURERS[1]!,
    totalProjects: 3,
    approvedProjects: 2,
    pendingProjects: 1,
    rejectedProjects: 0,
    totalHours: 80,
  },
  {
    lecturer: MOCK_LECTURERS[2]!,
    totalProjects: 2,
    approvedProjects: 1,
    pendingProjects: 1,
    rejectedProjects: 0,
    totalHours: 40,
  },
];

// Mock danh sách công trình của *một* giảng viên (màn "Công trình của tôi")
const MOCK_MY_WORKS: WorkItem[] = [
  {
    id: "1",
    title: "Bài báo về trí tuệ nhân tạo trong giáo dục đại học",
    workType: "ARTICLE",
    status: "COMPLETED",
    startYear: 2022,
    endYear: 2023,
    role: "MAIN",
    totalHours: 45,
    approved: true,
  },
  {
    id: "2",
    title:
      "Đề tài cấp trường: Ứng dụng học máy trong phân tích dữ liệu sinh viên",
    workType: "PROJECT",
    status: "ONGOING",
    startYear: 2023,
    endYear: null,
    role: "MAIN",
    totalHours: 80,
    approved: false,
  },
  {
    id: "3",
    title: "Giáo trình Lập trình Web với Vue.js",
    workType: "BOOK",
    status: "PENDING_APPROVAL",
    startYear: 2024,
    endYear: 2024,
    role: "CO",
    totalHours: 60,
    approved: false,
  },
];

/**
 * ================================
 * 2. API MÀN "CÔNG TRÌNH CỦA TÔI"
 * ================================
 */

/**
 * TODO: Nối với backend thật.
 * Hiện tại trả về dữ liệu mock để dev giao diện.
 */
export async function fetchMyWorks(
  params: FetchMyWorksParams
): Promise<PagedResult<WorkItem>> {
  // fake delay
  await new Promise((resolve) => setTimeout(resolve, 400));

  let filtered = [...MOCK_MY_WORKS];

  if (params.status && params.status !== "ALL") {
    filtered = filtered.filter((w) => w.status === params.status);
  }

  if (params.workType && params.workType !== "ALL") {
    filtered = filtered.filter((w) => w.workType === params.workType);
  }

  if (params.search) {
    const keyword = params.search.toLowerCase();
    filtered = filtered.filter((w) => w.title.toLowerCase().includes(keyword));
  }

  const page = params.page ?? 1;
  const pageSize = params.pageSize ?? 10;
  const start = (page - 1) * pageSize;
  const end = start + pageSize;

  return {
    items: filtered.slice(start, end),
    totalItems: filtered.length,
    page,
    pageSize,
  };
}

/**
 * ================================
 * 3. API OPTION FILTER (năm học, học kỳ, phạm vi, bộ môn)
 * ================================
 */

export async function fetchSelectOptions(): Promise<{
  years: SelectOption<string>[];
  semesters: SelectOption<string>[];
  scopes: ScopeOption[];
}> {
  // Thực tế: call API lấy options
  const years: SelectOption<string>[] = [
    { label: "2024–2025", value: "2024-2025" },
    { label: "2023–2024", value: "2023-2024" },
    { label: "2022–2023", value: "2022-2023" },
  ];

  const semesters: SelectOption<string>[] = [
    { label: "Cả năm", value: "all" },
    { label: "Học kỳ 1", value: "HK1" },
    { label: "Học kỳ 2", value: "HK2" },
    { label: "Học kỳ 3", value: "HK3" },
  ];

  const scopes: ScopeOption[] = [
    { label: "Trong khoa", value: "faculty" },
    { label: "Toàn trường", value: "university" },
  ];

  return new Promise((resolve) => {
    setTimeout(() => resolve({ years, semesters, scopes }), 300);
  });
}

export async function fetchDepartmentOptions(): Promise<DepartmentOption[]> {
  const departments: DepartmentOption[] = [
    { id: "all", name: "Tất cả bộ môn" },
    { id: "ict", name: "Bộ môn Công nghệ thông tin" },
    { id: "math", name: "Bộ môn Toán" },
  ];

  return new Promise((resolve) => {
    setTimeout(() => resolve(departments), 200);
  });
}

/**
 * ================================
 * 4. API MÀN "TỔNG HỢP CÔNG TRÌNH / GIỜ NCKH GIẢNG VIÊN"
 * ================================
 */

export async function fetchLecturerProjectsOverview(
  params: FetchOverviewParams
): Promise<FetchOverviewResult> {
  const { filters, pagination } = params;

  let filtered = [...MOCK_SUMMARIES];

  // Lọc theo tên giảng viên
  if (filters.lecturerName) {
    const keyword = filters.lecturerName.toLowerCase();
    filtered = filtered.filter((item) =>
      item.lecturer.fullName.toLowerCase().includes(keyword)
    );
  }

  // Lọc theo bộ môn
  if (filters.departmentId && filters.departmentId !== "all") {
    filtered = filtered.filter(
      (item) =>
        item.lecturer.departmentName.toLowerCase().includes("công nghệ") &&
        filters.departmentId === "ict"
    );
  }

  // TODO: nếu sau này cần lọc theo year/semester/scope thì xử lý thêm ở đây

  // Phân trang
  const start = (pagination.page - 1) * pagination.pageSize;
  const end = start + pagination.pageSize;
  const pagedItems = filtered.slice(start, end);

  const stats: OverviewStats = {
    lecturerCount: filtered.length,
    totalProjectsCount: filtered.reduce(
      (sum, item) => sum + item.totalProjects,
      0
    ),
    pendingProjectsCount: filtered.reduce(
      (sum, item) => sum + item.pendingProjects,
      0
    ),
  };

  const resultPagination = {
    ...pagination,
    totalItems: filtered.length,
  };

  return new Promise((resolve) => {
    setTimeout(
      () =>
        resolve({
          items: pagedItems,
          stats,
          pagination: resultPagination,
        }),
      400
    );
  });
}
