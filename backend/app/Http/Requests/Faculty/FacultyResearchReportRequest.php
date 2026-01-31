<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyResearchReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => is_string($this->q) ? trim($this->q) : $this->q,
            'research_type' => is_string($this->research_type) ? trim($this->research_type) : $this->research_type,
            'sort' => is_string($this->sort) ? trim($this->sort) : $this->sort,
        ]);
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'research_type' => ['nullable', 'string', 'max:50'],
            'lecturer_id' => ['nullable', 'integer', 'exists:lecturers,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
