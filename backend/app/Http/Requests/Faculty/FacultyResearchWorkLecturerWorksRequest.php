<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyResearchWorkLecturerWorksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        foreach (['q', 'status', 'sort'] as $key) {
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

        foreach (['academic_year_id', 'page', 'per_page'] as $key) {
            if (array_key_exists($key, $input) && $input[$key] === '') {
                $input[$key] = null;
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['nullable', 'string', 'in:all,approved,pending,need_revision,rejected'],
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
