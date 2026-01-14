<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;

class LecturerResearchWorkSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (['q', 'lecturer_q', 'status', 'author_role', 'management_level'] as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $raw = is_string($input[$key]) ? trim($input[$key]) : $input[$key];
            if ($raw === '' || strtolower((string) $raw) === 'all') {
                $input[$key] = null;
                continue;
            }

            $input[$key] = $raw;
        }

        foreach (['faculty_id', 'department_id', 'work_type_id', 'year_from', 'year_to', 'page', 'per_page'] as $key) {
            if (array_key_exists($key, $input) && $input[$key] === '') {
                $input[$key] = null;
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'lecturer_q' => ['nullable', 'string', 'max:255'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'work_type_id' => ['nullable', 'integer', 'exists:activity_kinds,id'],
            'author_role' => ['nullable', 'string', 'max:50', 'exists:member_roles,code'],
            'year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'status' => ['nullable', 'string', 'max:30', 'exists:activity_statuses,code'],
            'management_level' => ['nullable', 'string', 'max:50', 'exists:activity_types,code'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
