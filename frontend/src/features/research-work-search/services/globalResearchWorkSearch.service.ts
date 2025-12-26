import type {
  GlobalResearchWorkSearchFilterDTO,
  ResearchWorkDetailDTO,
  ResearchWorkSummaryDTO,
} from "../contracts/globalResearchWorkSearch.contract";
import { normalizeText } from "../contracts/globalResearchWorkSearch.contract";
import {
  researchWorkDetailsMockDTO,
  researchWorkSummariesMockDTO,
} from "../mock-data/globalResearchWorkSearch.mock";

function sleep(ms: number) {
  return new Promise<void>((resolve) => window.setTimeout(resolve, ms));
}

function inYearRange(year: number, from: number | null, to: number | null) {
  if (from != null && year < from) return false;
  if (to != null && year > to) return false;
  return true;
}

export async function searchResearchWorksDTO(
  filter: GlobalResearchWorkSearchFilterDTO
): Promise<ResearchWorkSummaryDTO[]> {
  await sleep(350);

  const keyword = normalizeText(filter.keyword);
  const lecturerKw = normalizeText(filter.lecturer_keyword);

  const rows = researchWorkSummariesMockDTO.filter((r) => {
    // status
    if (filter.status_code !== "all" && r.status_code !== filter.status_code)
      return false;

    // type
    if (filter.type_key !== "all" && r.type_key !== filter.type_key)
      return false;

    // faculty (mock: compare by name since summary has faculty_name)
    if (filter.faculty_id != null) {
      // In real API you'd filter by faculty_id; mock uses name mapping in composable/page.
      // Service keeps it "no-op" to avoid inventing ids here.
    }

    // year
    if (!inYearRange(r.year, filter.year_from, filter.year_to)) return false;

    // keyword (title + subtitle)
    if (keyword) {
      const hay = normalizeText(`${r.title} ${r.subtitle}`);
      if (!hay.includes(keyword)) return false;
    }

    // lecturer keyword (primary lecturer only in mock)
    if (lecturerKw) {
      const hayLect = normalizeText(
        `${r.primary_lecturer_name} ${r.primary_lecturer_code}`
      );
      if (!hayLect.includes(lecturerKw)) return false;
    }

    // role_key / management_level are not embedded in summary mock -> keep as API-side derived later
    // In real backend, these would be applied in query joins.

    return true;
  });

  // default sort: year desc, then title asc
  return rows.sort((a, b) => b.year - a.year || a.title.localeCompare(b.title));
}

export async function getResearchWorkDetailDTO(
  workId: number
): Promise<ResearchWorkDetailDTO> {
  await sleep(250);

  const detail = researchWorkDetailsMockDTO[workId];
  if (!detail) {
    // fallback minimal detail from summary (mock)
    const summary = researchWorkSummariesMockDTO.find(
      (s) => s.work_id === workId
    );
    if (!summary) throw new Error("Không tìm thấy công trình.");
    return {
      work_id: summary.work_id,
      title: summary.title,
      type_key: summary.type_key,
      status_code: summary.status_code,
      year: summary.year,
      abstract: null,
      info_rows: [
        { label: "Ghi chú", value: "Chưa có dữ liệu chi tiết (mock)." },
      ],
      participants: [
        {
          name: summary.primary_lecturer_name,
          faculty_name: summary.faculty_name,
          role_label: "Giảng viên chính",
        },
      ],
      files: summary.has_public_pdf
        ? [
            {
              file_id: 999,
              kind: "pdf",
              label: "Tệp PDF (mock)",
              url: "/mock-files/public.pdf",
            },
          ]
        : [],
    };
  }

  return detail;
}
