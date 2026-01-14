<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;

class LecturerHoursCalculateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $fields = ['status', 'q', 'page', 'per_page'];
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
            'status' => ['nullable', 'string', 'in:all,not_submitted,pending,approved,rejected'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
