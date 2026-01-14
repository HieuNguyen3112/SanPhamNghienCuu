<?php

namespace App\Http\Requests\Lecturer;

use Illuminate\Foundation\Http\FormRequest;

class LecturerHoursCalculateSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('activity_ids') && $this->input('activity_ids') === '') {
            $this->merge(['activity_ids' => []]);
        }
    }

    public function rules(): array
    {
        return [
            'activity_ids' => ['required', 'array', 'min:1'],
            'activity_ids.*' => ['integer'],
        ];
    }
}
