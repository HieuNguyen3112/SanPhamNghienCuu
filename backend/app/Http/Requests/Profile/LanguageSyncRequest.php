<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class LanguageSyncRequest extends FormRequest
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
                'id',
                'language',
                'level',
                'certificate_name',
                'certificate_level',
                'certificate_score',
                'certificate_issuer',
                'issue_date',
                'expire_date',
                'note',
                'attachment_name',
                'is_native',
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
            'items.*.id' => ['nullable', 'integer'],
            'items.*.language' => ['required', 'string', 'max:100'],
            'items.*.level' => ['required', 'string', 'max:100'],
            'items.*.is_native' => ['nullable', 'boolean'],
            'items.*.certificate_name' => ['nullable', 'string', 'max:150'],
            'items.*.certificate_level' => ['nullable', 'string', 'max:100'],
            'items.*.certificate_score' => ['nullable', 'string', 'max:50'],
            'items.*.certificate_issuer' => ['nullable', 'string', 'max:255'],
            'items.*.issue_date' => ['nullable', 'date'],
            'items.*.expire_date' => ['nullable', 'date'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'items.*.attachment_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
