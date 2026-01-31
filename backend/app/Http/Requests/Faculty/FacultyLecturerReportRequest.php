<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyLecturerReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gender' => is_string($this->gender) ? trim($this->gender) : $this->gender,
            'sort' => is_string($this->sort) ? trim($this->sort) : $this->sort,
        ]);
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'academic_rank_id' => ['nullable', 'integer', 'exists:academic_ranks,id'],
            'gender' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
