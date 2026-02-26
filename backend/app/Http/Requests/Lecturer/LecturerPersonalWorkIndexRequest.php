<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LecturerPersonalWorkIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'status',
            'q',
            'year',
            'academic_year',
            'academic_year_id',
            'kind_id',
            'type_id',
            'role_id',
            'sort',
            'page',
            'per_page',
        ];

        $normalized = [];
        foreach ($fields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $normalized[$field] = null;
            }
        }

        if ($normalized) {
            $this->merge($normalized);
        }

        $this->normalizeAcademicYearFilter();
        $this->normalizeStatusFilter();
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:all,pending,draft,pending_member_confirm,member_rejected,pending_faculty_review,submitted,approved,rejected'],
            'q' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:3000'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'kind_id' => ['nullable', 'integer', 'exists:activity_kinds,id'],
            'type_id' => ['nullable', 'integer', 'exists:activity_types,id'],
            'role_id' => ['nullable', 'integer', 'exists:member_roles,id'],
            'sort' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    private function normalizeAcademicYearFilter(): void
    {
        if ($this->filled('academic_year_id')) {
            $rawId = trim((string) $this->input('academic_year_id'));
            if ($rawId !== '' && ctype_digit($rawId)) {
                $this->merge(['academic_year_id' => (int) $rawId]);
            }
            return;
        }

        if (! $this->filled('academic_year')) {
            return;
        }

        $rawAcademicYear = trim((string) $this->input('academic_year'));
        if ($rawAcademicYear === '') {
            return;
        }

        if (ctype_digit($rawAcademicYear)) {
            $this->merge(['academic_year_id' => (int) $rawAcademicYear]);
            return;
        }

        $matchedId = DB::table('academic_years')
            ->where('code', $rawAcademicYear)
            ->value('id');

        if ($matchedId) {
            $this->merge(['academic_year_id' => (int) $matchedId]);
        }
    }

    private function normalizeStatusFilter(): void
    {
        if (! $this->filled('status')) {
            return;
        }

        $normalized = $this->normalizeText((string) $this->input('status'));
        $canonical = match ($normalized) {
            'all', 'tat_ca', 'tatca' => 'all',
            'pending' => 'pending',
            'draft', 'ban_nhap' => 'draft',
            'pending_member_confirm', 'cho_thanh_vien_xac_nhan' => 'pending_member_confirm',
            'member_rejected', 'thanh_vien_tu_choi' => 'member_rejected',
            'pending_faculty_review', 'cho_khoa_duyet' => 'pending_faculty_review',
            'submitted', 'da_gui_duyet', 'da_gui' => 'submitted',
            'approved', 'da_duyet', 'khoa_duyet' => 'approved',
            'rejected', 'tu_choi', 'khoa_tu_choi' => 'rejected',
            default => (string) $this->input('status'),
        };

        $this->merge(['status' => $canonical]);
    }

    private function normalizeText(string $value): string
    {
        $lower = mb_strtolower(trim($value), 'UTF-8');

        $ascii = Str::of($lower)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($ascii !== '') {
            return $ascii;
        }

        return (string) Str::of($lower)
            ->replaceMatches('/[^\\pL\\pN]+/u', '_')
            ->trim('_');
    }
}
