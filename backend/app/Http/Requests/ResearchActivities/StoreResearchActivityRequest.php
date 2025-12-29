<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;

class StoreResearchActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'type_id',
            'abstract',
            'start_date',
            'end_date',
            'quantity',
            'notes',
        ];

        $input = $this->all();
        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'kind_id' => ['required', 'integer', 'exists:activity_kinds,id'],
            'type_id' => ['nullable', 'integer', 'exists:activity_types,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
