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
        $fields = ['status', 'q', 'academic_year_id', 'page', 'per_page', 'include_all_years'];
        $normalized = [];

        foreach ($fields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $normalized[$field] = null;
            }
        }

        if ($normalized) {
            $this->merge($normalized);
        }

        if ($this->has('include_all_years')) {
            $raw = $this->input('include_all_years');
            $value = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $normalizedBool = $value !== null ? $value : in_array((string) $raw, ['1', 'on', 'yes'], true);
            $this->merge(['include_all_years' => $normalizedBool]);
        }
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:all,not_submitted,pending,approved,rejected,hours_not_submitted,hours_pending_faculty,hours_approved,hours_rejected'],
            'q' => ['nullable', 'string', 'max:255'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'include_all_years' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
