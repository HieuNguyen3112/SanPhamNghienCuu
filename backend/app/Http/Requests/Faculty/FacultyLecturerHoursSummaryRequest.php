<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyLecturerHoursSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => is_string($this->q) ? trim($this->q) : $this->q,
            'kpi_status' => is_string($this->kpi_status) ? trim($this->kpi_status) : $this->kpi_status,
        ]);
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'kpi_status' => ['nullable', 'string', 'in:all,hit,miss,met,missing'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
