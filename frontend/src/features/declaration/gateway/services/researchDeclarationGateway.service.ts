import http from "@/lib/http";
import type {
  DeclarationDraftRowDTO,
  DeclarationTypeCardDTO,
} from "../contracts/ResearchDeclarationGateway.contract";

const declarationTypeCards: DeclarationTypeCardDTO[] = [
  {
    type_key: "article",
    title: "Bài báo khoa học",
    description_lines: [
      "Khai báo bài báo trong tạp chí hoặc kỷ yếu.",
      "Hệ thống tự tính giờ theo quy định.",
    ],
    to: "/declarations/articles",
  },
  {
    type_key: "project",
    title: "Đề tài nghiên cứu",
    description_lines: [
      "Khai báo đề tài/dự án nghiên cứu.",
      "Áp dụng quy đổi giờ theo phân vai.",
    ],
    to: "/declarations/projects",
  },
  {
    type_key: "book",
    title: "Sách/Giáo trình",
    description_lines: [
      "Khai báo sách hoặc giáo trình.",
      "Nhập đầy đủ thông tin xuất bản.",
    ],
    to: "/declarations/books",
  },
  {
    type_key: "conference",
    title: "Hội nghị/Hội thảo",
    description_lines: [
      "Khai báo báo cáo hội nghị/hội thảo.",
      "Nhập thông tin hội nghị và vai trò.",
    ],
    to: "/declarations/others",
  },
];

export async function getDeclarationTypeCardsDTO(): Promise<
  DeclarationTypeCardDTO[]
> {
  return declarationTypeCards;
}

export async function getDraftDeclarationsDTO(): Promise<
  DeclarationDraftRowDTO[]
> {
  const { data } = await http.get<{
    data: { items: DeclarationDraftRowDTO[] };
  }>("/api/lecturer/declarations/drafts");
  return data.data.items ?? [];
}
