<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyLecturerAccountRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $roleKeys = $this->role_keys;
        if (! is_array($roleKeys)) {
            $roleKeys = [];
        }
        $this->merge([
            'role_keys' => $roleKeys,
        ]);
    }

    public function rules(): array
    {
        return [
            'role_keys' => ['required', 'array', 'min:1'],
            'role_keys.*' => ['string', 'max:50'],
        ];
    }
}
