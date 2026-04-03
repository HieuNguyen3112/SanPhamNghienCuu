import type { PublicResearchItemDto } from "../dto/publicResearchDtos";
import type { PublicResearchItem } from "@/features/public-research/models/publicResearchModels";
import type { PublicResearchDetailDto } from "../dto/publicResearchDtos";
import type { PublicResearchDetail } from "../models/publicResearchModels";

export function mapPublicResearchItemDtoToModel(
  dto: PublicResearchItemDto,
): PublicResearchItem {
  return {
    id: dto.id,
    title: dto.title,
    abstract: dto.abstract,

    lecturerId: dto.lecturer_id,
    lecturerCode: dto.lecturer_code,
    lecturerName: dto.lecturer_name,

    facultyId: dto.faculty_id,
    facultyName: dto.faculty_name,

    workType: dto.work_type, // dto đã fix không undefined

    academicYearId: dto.academic_year_id,
    academicYearCode: dto.academic_year_code,

    // ✅ public chỉ có APPROVED
    approvalStatus: "APPROVED",

    // ✅ normalize optional fields để không bao giờ undefined
    pdfUrl: dto.pdf_url ?? null,
    coverUrl: dto.cover_url ?? null,
    keywords: Array.isArray(dto.keywords) ? dto.keywords : [],
  };
}

export function mapPublicResearchDetailDtoToModel(
  dto: PublicResearchDetailDto,
): PublicResearchDetail {
  const base = mapPublicResearchItemDtoToModel(dto);

  return {
    ...base,
    activityCode: dto.activity_code ?? "",
    participants: (dto.participants ?? []).map((p) => ({
      lecturerId: p.lecturer_id ?? null,
      lecturerCode: p.lecturer_code ?? null,
      lecturerName: p.lecturer_name,
      facultyName: p.faculty_name,
      roleName: p.role_name,
    })),
    evidenceFiles: Array.isArray(dto.evidence_files)
      ? dto.evidence_files.map((file) => ({
          label: file.label,
          url: file.url,
          isPdf: Boolean(file.is_pdf),
        }))
      : [],
    displayMeta: {
      article: {
        journalName: dto.display_meta?.article?.journal_name ?? null,
        issn: dto.display_meta?.article?.issn ?? null,
        journalScope: dto.display_meta?.article?.journal_scope ?? null,
        journalType: dto.display_meta?.article?.journal_type ?? null,
        journalSourceName:
          dto.display_meta?.article?.journal_source_name ?? null,
        researchField: dto.display_meta?.article?.research_field ?? null,
        year: dto.display_meta?.article?.year ?? null,
        volume: dto.display_meta?.article?.volume ?? null,
        issue: dto.display_meta?.article?.issue ?? null,
        pageStart: dto.display_meta?.article?.page_start ?? null,
        pageEnd: dto.display_meta?.article?.page_end ?? null,
        doi: dto.display_meta?.article?.doi ?? null,
        articleUrl: dto.display_meta?.article?.article_url ?? null,
      },
      project: {
        projectCode: dto.display_meta?.project?.project_code ?? null,
        managementLevel: dto.display_meta?.project?.management_level ?? null,
        projectCategory: dto.display_meta?.project?.project_category ?? null,
        researchField: dto.display_meta?.project?.research_field ?? null,
        objectives: dto.display_meta?.project?.objectives ?? null,
        contentSummary: dto.display_meta?.project?.content_summary ?? null,
        startMonth: dto.display_meta?.project?.start_month ?? null,
        endMonth: dto.display_meta?.project?.end_month ?? null,
        decisionNo: dto.display_meta?.project?.decision_no ?? null,
        decisionDate: dto.display_meta?.project?.decision_date ?? null,
      },
      book: {
        publisher: dto.display_meta?.book?.publisher ?? null,
        isbn: dto.display_meta?.book?.isbn ?? null,
        year: dto.display_meta?.book?.year ?? null,
        bookType: dto.display_meta?.book?.book_type ?? null,
        researchField: dto.display_meta?.book?.research_field ?? null,
        approvalDecisionNo:
          dto.display_meta?.book?.approval_decision_no ?? null,
        approvalDecisionDate:
          dto.display_meta?.book?.approval_decision_date ?? null,
      },
      conference: {
        conferenceName: dto.display_meta?.conference?.conference_name ?? null,
        heldOn: dto.display_meta?.conference?.held_on ?? null,
        location: dto.display_meta?.conference?.location ?? null,
      },
    },
  };
}
