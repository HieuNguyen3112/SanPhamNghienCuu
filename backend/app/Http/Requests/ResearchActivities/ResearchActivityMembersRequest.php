<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;

class ResearchActivityMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $nullableFields = [
                'contribution_share',
                'hours_assigned',
            ];

            foreach ($nullableFields as $field) {
                if (array_key_exists($field, $item) && $item[$field] === '') {
                    $item[$field] = null;
                }
            }

            $items[$index] = $item;
        }

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*' => ['array'],
            'items.*.lecturer_id' => ['required', 'integer', 'distinct', 'exists:lecturers,id'],
            'items.*.member_role_id' => ['required', 'integer', 'exists:member_roles,id'],
            'items.*.contribution_share' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'items.*.hours_assigned' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
