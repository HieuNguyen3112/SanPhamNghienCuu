<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyLecturerAccountStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => is_string($this->reason) ? trim($this->reason) : $this->reason,
        ]);
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
