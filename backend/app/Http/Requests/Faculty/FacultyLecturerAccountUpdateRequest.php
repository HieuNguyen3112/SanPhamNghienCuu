<?php

namespace App\Http\Requests\Faculty;

use App\Http\Requests\Faculty\Concerns\ValidatesFacultyScopedUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FacultyLecturerAccountUpdateRequest extends FormRequest
{
    use ValidatesFacultyScopedUnit;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => is_string($this->full_name) ? trim($this->full_name) : $this->full_name,
            'email' => is_string($this->email) ? trim($this->email) : $this->email,
            'position_title' => is_string($this->position_title) ? trim($this->position_title) : $this->position_title,
            'unit_id' => isset($this->unit_id) ? (int) $this->unit_id : $this->unit_id,
        ]);
    }

    public function rules(): array
    {
        $lecturer = $this->route('lecturer');
        $userId = $lecturer?->user_id ?? 0;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'unit_id' => ['required', 'integer', 'exists:departments,id'],
            'position_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->addFacultyScopedUnitValidation($validator);
    }
}
