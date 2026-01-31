<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResearchActivityRequest extends FormRequest
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
            'kind_id' => ['sometimes', 'integer', 'exists:activity_kinds,id'],
            'type_id' => ['nullable', 'integer', 'exists:activity_types,id'],
            'academic_year_id' => ['sometimes', 'integer', 'exists:academic_years,id'],
            'title' => ['sometimes', 'string', 'max:500'],
            'abstract' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kind_id' => 'loại công trình',
            'type_id' => 'loại chi tiết',
            'academic_year_id' => 'niên học',
            'title' => 'tên công trình',
            'abstract' => 'tóm tắt',
            'start_date' => 'ngày bắt đầu',
            'end_date' => 'ngày kết thúc',
            'quantity' => 'số lượng',
            'notes' => 'ghi chú',
        ];
    }
}
