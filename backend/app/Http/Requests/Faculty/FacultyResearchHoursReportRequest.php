<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyResearchHoursReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $status = is_string($this->status) ? strtolower(trim($this->status)) : $this->status;

        $this->merge([
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['nullable', 'string', 'in:all,met,not_met'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
