<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyOrgStructureDepartmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? trim($this->code) : $this->code,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
        ]);
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where(function ($q) {
                    $q->where('faculty_id', $this->input('faculty_id'));
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
