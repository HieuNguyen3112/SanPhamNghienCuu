<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LecturerHoursCalculateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $fields = ['status', 'q', 'academic_year_id', 'academic_year', 'page', 'per_page', 'include_all_years'];
        $normalized = [];

        foreach ($fields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $normalized[$field] = null;
            }
        }

        if ($normalized) {
            $this->merge($normalized);
        }

        if ($this->has('include_all_years')) {
            $raw = $this->input('include_all_years');
            $value = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $normalizedBool = $value !== null ? $value : in_array((string) $raw, ['1', 'on', 'yes'], true);
            $this->merge(['include_all_years' => $normalizedBool]);
        }

        $this->normalizeAcademicYearFilter();
        $this->normalizeStatusFilter();
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:all,not_submitted,pending,approved,rejected,hours_not_submitted,hours_pending_faculty,hours_approved,hours_rejected'],
            'q' => ['nullable', 'string', 'max:255'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'include_all_years' => ['nullable', 'boolean'],
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
            'not_submitted', 'hours_not_submitted', 'chua_gui_duyet_gio', 'chua_gui_gio', 'chua_gui_duyet', 'chua_gui' => 'hours_not_submitted',
            'pending', 'hours_pending_faculty', 'cho_khoa_duyet_gio', 'cho_khoa_duyet', 'cho_duyet_gio', 'cho_duyet' => 'hours_pending_faculty',
            'approved', 'hours_approved', 'da_duyet_gio', 'da_duyet' => 'hours_approved',
            'rejected', 'hours_rejected', 'khoa_tu_choi_gio', 'tu_choi_gio', 'tu_choi' => 'hours_rejected',
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
