<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyLecturerAccountListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'keyword' => is_string($this->keyword) ? trim($this->keyword) : $this->keyword,
            'status' => is_string($this->status) ? trim($this->status) : $this->status,
            'role_keys' => is_array($this->role_keys) ? $this->role_keys : [],
        ]);
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', 'string', 'max:20'],
            'role_keys' => ['nullable', 'array'],
            'role_keys.*' => ['string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'max:50'],
        ];
    }
}
