<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ResearchWorkDetailSchemaBuilder
{
    public function build(int $activityId, ?string $kindCode = null): array
    {
        if ($activityId <= 0) {
            return [
                'kind_code' => $kindCode !== null ? strtolower(trim($kindCode)) : null,
                'sections' => [],
            ];
        }

        $resolvedKindCode = $this->resolveKindCode($activityId, $kindCode);
        $sections = match ($resolvedKindCode) {
            'paper' => $this->buildPaperSections($activityId),
            'project' => $this->buildProjectSections($activityId),
            'conference' => $this->buildConferenceSections($activityId),
            'book' => $this->buildBookSections($activityId),
            default => [],
        };

        return [
            'kind_code' => $resolvedKindCode,
            'sections' => $sections,
        ];
    }

    private function resolveKindCode(int $activityId, ?string $kindCode): ?string
    {
        $normalized = $kindCode !== null ? strtolower(trim($kindCode)) : '';
        if ($normalized !== '') {
            return $normalized;
        }

        $value = DB::table('research_activities as ra')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->where('ra.id', $activityId)
            ->value('ak.code');

        if (! $value) {
            return null;
        }

        return strtolower(trim((string) $value));
    }

    private function buildPaperSections(int $activityId): array
    {
        $paper = DB::table('paper_details')
            ->where('activity_id', $activityId)
            ->select([
                'journal_name',
                'issn',
                'doi',
                'volume',
                'issue',
                'year',
                'page_start',
                'page_end',
                'article_url',
                'keywords',
                'research_field',
                'publication_status',
                'journal_scope',
                'journal_source_name',
                'journal_publisher',
                'journal_website',
                'work_score',
                'conference_name',
                'conference_level',
                'conference_research_field',
                'conference_organization',
                'conference_has_isbn',
                'conference_isbn',
                'conference_point',
            ])
            ->first();

        if (! $paper) {
            return [];
        }

        $sections = [];

        $pageRange = null;
        if ($paper->page_start !== null || $paper->page_end !== null) {
            $start = $paper->page_start !== null ? (string) $paper->page_start : '?';
            $end = $paper->page_end !== null ? (string) $paper->page_end : '?';
            $pageRange = $start . ' - ' . $end;
        }

        $this->addSection(
            $sections,
            'paper_journal',
            'Thông tin bài báo',
            [
                $this->field('journal_name', 'Tên tạp chí', $paper->journal_name),
                $this->field('issn', 'ISSN', $paper->issn),
                $this->field('doi', 'DOI', $paper->doi),
                $this->field('volume', 'Tập', $paper->volume),
                $this->field('issue', 'Số', $paper->issue),
                $this->field('year', 'Năm xuất bản', $paper->year),
                $this->field('page_range', 'Trang', $pageRange),
                $this->field('article_url', 'Đường dẫn bài báo', $paper->article_url),
                $this->field('keywords', 'Từ khóa', $paper->keywords),
                $this->field('research_field', 'Lĩnh vực nghiên cứu', $paper->research_field),
                $this->field('publication_status', 'Trạng thái công bố', $paper->publication_status),
            ]
        );

        $this->addSection(
            $sections,
            'paper_journal_ranking',
            'Thông tin xếp hạng tạp chí',
            [
                $this->field('journal_scope', 'Phạm vi', $paper->journal_scope),
                $this->field('journal_source_name', 'Nguồn xếp loại', $paper->journal_source_name),
                $this->field('journal_publisher', 'Nhà xuất bản', $paper->journal_publisher),
                $this->field('journal_website', 'Website', $paper->journal_website),
                $this->field('work_score', 'Điểm công trình', $paper->work_score),
            ]
        );

        $this->addSection(
            $sections,
            'paper_conference_snapshot',
            'Thông tin hội thảo (nếu có)',
            [
                $this->field('conference_name', 'Tên hội thảo', $paper->conference_name),
                $this->field('conference_level', 'Cấp hội thảo', $paper->conference_level),
                $this->field('conference_research_field', 'Lĩnh vực hội thảo', $paper->conference_research_field),
                $this->field('conference_organization', 'Đơn vị tổ chức', $paper->conference_organization),
                $this->field('conference_has_isbn', 'Có ISBN', $this->boolLike($paper->conference_has_isbn)),
                $this->field('conference_isbn', 'ISBN hội thảo', $paper->conference_isbn),
                $this->field('conference_point', 'Điểm hội thảo', $paper->conference_point),
            ]
        );

        return $sections;
    }

    private function buildProjectSections(int $activityId): array
    {
        $project = DB::table('project_details')
            ->where('activity_id', $activityId)
            ->select([
                'project_code',
                'project_category',
                'research_field',
                'funding',
                'start_month',
                'end_month',
                'decision_no',
                'decision_date',
                'application_address',
                'implementing_unit',
                'project_status',
                'objectives',
                'content_summary',
                'main_results',
            ])
            ->first();

        if (! $project) {
            return [];
        }

        $sections = [];

        $this->addSection(
            $sections,
            'project_overview',
            'Thông tin đề tài',
            [
                $this->field('project_code', 'Mã đề tài', $project->project_code),
                $this->field('project_category', 'Nhóm đề tài', $project->project_category),
                $this->field('research_field', 'Lĩnh vực nghiên cứu', $project->research_field),
                $this->field('funding', 'Kinh phí', $project->funding),
                $this->field('project_status', 'Trạng thái đề tài', $this->projectStatusLabel($project->project_status)),
                $this->field('implementing_unit', 'Đơn vị chủ trì', $project->implementing_unit),
                $this->field('application_address', 'Địa chỉ ứng dụng', $project->application_address),
            ]
        );

        $this->addSection(
            $sections,
            'project_schedule',
            'Tiến độ và quyết định',
            [
                $this->field('start_month', 'Thời gian bắt đầu', $project->start_month),
                $this->field('end_month', 'Thời gian kết thúc', $project->end_month),
                $this->field('decision_no', 'Số quyết định', $project->decision_no),
                $this->field('decision_date', 'Ngày quyết định', $project->decision_date),
            ]
        );

        $this->addSection(
            $sections,
            'project_content',
            'Nội dung đề tài',
            [
                $this->field('objectives', 'Mục tiêu', $project->objectives),
                $this->field('content_summary', 'Tóm tắt nội dung', $project->content_summary),
                $this->field('main_results', 'Kết quả chính', $project->main_results),
            ]
        );

        return $sections;
    }

    private function buildConferenceSections(int $activityId): array
    {
        $conference = DB::table('conference_details')
            ->where('activity_id', $activityId)
            ->select([
                'conference_name',
                'location',
                'held_on',
            ])
            ->first();

        $paperSnapshot = DB::table('paper_details')
            ->where('activity_id', $activityId)
            ->select([
                'conference_name',
                'conference_level',
                'conference_research_field',
                'conference_organization',
                'conference_has_isbn',
                'conference_isbn',
                'conference_point',
            ])
            ->first();

        if (! $conference && ! $paperSnapshot) {
            return [];
        }

        $sections = [];

        $this->addSection(
            $sections,
            'conference_main',
            'Thông tin hội thảo',
            [
                $this->field('conference_name', 'Tên hội thảo', $conference->conference_name ?? $paperSnapshot->conference_name ?? null),
                $this->field('location', 'Địa điểm', $conference->location ?? null),
                $this->field('held_on', 'Thời gian tổ chức', $conference->held_on ?? null),
                $this->field('conference_level', 'Cấp hội thảo', $paperSnapshot->conference_level ?? null),
                $this->field('conference_research_field', 'Lĩnh vực hội thảo', $paperSnapshot->conference_research_field ?? null),
                $this->field('conference_organization', 'Đơn vị tổ chức', $paperSnapshot->conference_organization ?? null),
                $this->field('conference_has_isbn', 'Có ISBN', $this->boolLike($paperSnapshot->conference_has_isbn ?? null)),
                $this->field('conference_isbn', 'ISBN hội thảo', $paperSnapshot->conference_isbn ?? null),
                $this->field('conference_point', 'Điểm hội thảo', $paperSnapshot->conference_point ?? null),
            ]
        );

        return $sections;
    }

    private function buildBookSections(int $activityId): array
    {
        $book = DB::table('book_details')
            ->where('activity_id', $activityId)
            ->select([
                'publisher',
                'publisher_address',
                'publisher_phone',
                'publisher_email',
                'publisher_website',
                'isbn',
                'pages',
                'year',
                'approval_decision_no',
                'approval_decision_date',
            ])
            ->first();

        if (! $book) {
            return [];
        }

        $sections = [];

        $this->addSection(
            $sections,
            'book_publisher',
            'Thông tin nhà xuất bản',
            [
                $this->field('publisher', 'Nhà xuất bản', $book->publisher),
                $this->field('publisher_address', 'Địa chỉ', $book->publisher_address),
                $this->field('publisher_phone', 'Điện thoại', $book->publisher_phone),
                $this->field('publisher_email', 'Email', $book->publisher_email),
                $this->field('publisher_website', 'Website', $book->publisher_website),
            ]
        );

        $this->addSection(
            $sections,
            'book_publication',
            'Thông tin xuất bản',
            [
                $this->field('isbn', 'ISBN', $book->isbn),
                $this->field('pages', 'Số trang', $book->pages),
                $this->field('year', 'Năm xuất bản', $book->year),
                $this->field('approval_decision_no', 'Số quyết định duyệt', $book->approval_decision_no),
                $this->field('approval_decision_date', 'Ngày quyết định duyệt', $book->approval_decision_date),
            ]
        );

        return $sections;
    }

    private function addSection(array &$sections, string $code, string $title, array $fields): void
    {
        $normalizedFields = array_values(array_filter($fields));
        if ($normalizedFields === []) {
            return;
        }

        $sections[] = [
            'code' => $code,
            'title' => $title,
            'fields' => $normalizedFields,
        ];
    }

    private function field(string $key, string $label, mixed $value): ?array
    {
        $normalized = $this->normalizeValue($value);
        if ($normalized === null) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $normalized,
        ];
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }

        return $value;
    }

    private function boolLike(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === '') {
                return null;
            }

            if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
                return false;
            }
        }

        return null;
    }

    private function projectStatusLabel(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        $labels = [
            'planning' => 'Chuẩn bị',
            'ongoing' => 'Đang thực hiện',
            'completed' => 'Đã hoàn thành',
            'accepted' => 'Đã nghiệm thu/công nhận',
        ];

        return $labels[$normalized] ?? $value;
    }
}
