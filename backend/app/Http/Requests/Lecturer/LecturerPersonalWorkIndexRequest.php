<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;

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
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:all,draft,submitted,approved,rejected'],
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
}
