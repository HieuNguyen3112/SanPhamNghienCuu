<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;

class LecturerHoursWarningSeenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('seen') && $this->input('seen') === '') {
            $this->merge(['seen' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'seen' => ['nullable', 'boolean'],
        ];
    }
}
