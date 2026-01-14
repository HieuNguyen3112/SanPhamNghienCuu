<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyResearchWorkSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        if (array_key_exists('lecturer_name', $input) && ! array_key_exists('q', $input)) {
            $input['q'] = $input['lecturer_name'];
        }

        if (array_key_exists('status_mode', $input) && ! array_key_exists('count_status', $input)) {
            $input['count_status'] = $input['status_mode'];
        }

        foreach (['q', 'count_status'] as $key) {
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
            'q' => ['nullable', 'string', 'max:255'],
            'count_status' => ['nullable', 'string', 'in:all,approved,pending,rejected'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
