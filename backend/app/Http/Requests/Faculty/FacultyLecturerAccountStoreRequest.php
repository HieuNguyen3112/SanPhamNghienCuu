<?php

namespace App\Http\Requests\Faculty;

use App\Http\Requests\Faculty\Concerns\ValidatesFacultyScopedUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FacultyLecturerAccountStoreRequest extends FormRequest
{
    use ValidatesFacultyScopedUnit;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lecturer_code' => is_string($this->lecturer_code) ? trim($this->lecturer_code) : $this->lecturer_code,
            'full_name' => is_string($this->full_name) ? trim($this->full_name) : $this->full_name,
            'email' => is_string($this->email) ? trim($this->email) : $this->email,
            'phone_number' => is_string($this->phone_number) ? trim($this->phone_number) : $this->phone_number,
            'academic_title' => is_string($this->academic_title) ? trim($this->academic_title) : $this->academic_title,
            'status' => is_string($this->status) ? strtoupper(trim($this->status)) : $this->status,
            'unit_id' => isset($this->unit_id) ? (int) $this->unit_id : $this->unit_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'lecturer_code' => ['required', 'string', 'max:50', Rule::unique('lecturers', 'code')],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\-\s]{8,30}$/'],
            'academic_title' => ['nullable', 'string', 'max:255'],
            'degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'academic_rank_id' => ['nullable', 'integer', 'exists:academic_ranks,id'],
            'unit_id' => ['required', 'integer', 'exists:departments,id'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->addFacultyScopedUnitValidation($validator);
    }
}
