<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class LecturerPersonalHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $fields = ['academic_year_id', 'academic_year', 'mode', 'page', 'per_page'];
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
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'mode' => ['nullable', 'string', 'in:year,overall'],
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
}
