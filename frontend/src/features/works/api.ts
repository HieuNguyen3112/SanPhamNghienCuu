// src/features/works/api.ts
import type {
  FetchMyWorksParams,
  PagedResult,
  WorkItem,
} from "@/features/works/types";

/**
 * TODO: Nối với backend thật.
 * Hiện tại trả về dữ liệu mock để dev giao diện.
 */
export async function fetchMyWorks(
  params: FetchMyWorksParams
): Promise<PagedResult<WorkItem>> {
  // fake delay
  await new Promise((resolve) => setTimeout(resolve, 400));

  const mockData: WorkItem[] = [
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

  // Lọc đơn giản theo status / workType / search phía frontend cho demo
  let filtered = mockData;

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
