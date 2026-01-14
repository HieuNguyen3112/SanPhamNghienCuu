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
        $this->merge([
            'keyword' => is_string($this->keyword) ? trim($this->keyword) : $this->keyword,
            'status' => is_string($this->status) ? trim($this->status) : $this->status,
        ]);
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'status' => ['nullable', 'string', 'in:all,pending,approved,rejected'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
