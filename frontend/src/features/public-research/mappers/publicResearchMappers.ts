import type { PublicResearchItemDto } from "../dto/publicResearchDtos";
import type { PublicResearchItem } from "@/features/public-research/models/publicResearchModels";

export function mapPublicResearchItemDtoToModel(
  dto: PublicResearchItemDto
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
