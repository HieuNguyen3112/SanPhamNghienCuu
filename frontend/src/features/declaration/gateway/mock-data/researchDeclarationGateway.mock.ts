import type {
  DeclarationDraftRowDTO,
  DeclarationTypeCardDTO,
} from "../contracts/ResearchDeclarationGateway.contract";

export const declarationTypeCardsMockDTO: DeclarationTypeCardDTO[] = [
  {
    type_key: "article",
    title: "Bài báo khoa học",
    description_lines: ["Tạp chí trong nước / quốc tế", "Có phản biện"],
    to: "/declarations/articles",
  },
  {
    type_key: "project",
    title: "Đề tài nghiên cứu",
    description_lines: [
      "Cấp khoa / cấp trường / cấp bộ",
      "Có quyết định phê duyệt",
    ],
    to: "/declarations/projects",
  },
  {
    type_key: "book",
    title: "Sách – Giáo trình",
    description_lines: [
      "Chủ biên / đồng tác giả",
      "Có ISBN / quyết định xuất bản",
    ],
    to: "/declarations/books",
  },
  {
    type_key: "conference",
    title: "Hội thảo – Báo cáo khoa học",
    description_lines: ["Trong nước / quốc tế", "Có kỷ yếu / chứng nhận"],
    to: "/declarations/conferences",
  },
];

export const draftDeclarationsMockDTO: DeclarationDraftRowDTO[] = [
  {
    id: 501,
    title: "Ứng dụng AI trong dạy học",
    type_label: "Bài báo khoa học",
    updated_at: "2025-12-10T10:30:00.000Z",
    to: "/declarations/articles/501?mode=draft",
  },
  {
    id: 502,
    title: "Chuyển đổi số trong quản trị đại học",
    type_label: "Đề tài nghiên cứu",
    updated_at: "2025-12-18T08:10:00.000Z",
    to: "/declarations/projects/502?mode=draft",
  },
];
