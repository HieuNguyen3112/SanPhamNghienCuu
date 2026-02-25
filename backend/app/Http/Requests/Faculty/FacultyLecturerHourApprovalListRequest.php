<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyLecturerHourApprovalListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $includeAllYears = $this->include_all_years;
        if ($includeAllYears !== null) {
            $parsed = filter_var($includeAllYears, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $includeAllYears = $parsed !== null
                ? $parsed
                : in_array((string) $includeAllYears, ['1', 'on', 'yes'], true);
        }

        $this->merge([
            'keyword' => is_string($this->keyword) ? trim($this->keyword) : $this->keyword,
            'status' => is_string($this->status) ? trim($this->status) : $this->status,
            'academic_year_id' => $this->academic_year_id === '' ? null : $this->academic_year_id,
            'include_all_years' => $includeAllYears,
        ]);
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'include_all_years' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:all,pending,approved,rejected'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
