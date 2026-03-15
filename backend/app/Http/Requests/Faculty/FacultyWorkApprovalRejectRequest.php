<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FacultyWorkApprovalRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        if (array_key_exists('reason_detail', $input)) {
            $detail = is_string($input['reason_detail']) ? trim($input['reason_detail']) : $input['reason_detail'];
            $input['reason_detail'] = $detail === '' ? null : $detail;
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'reason_type' => [
                'required',
                'string',
                'in:MISSING_EVIDENCE,INACCURATE_INFORMATION,OUTSIDE_FACULTY_SCOPE,OTHER',
            ],
            'reason_detail' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('reason_detail', ['required'], function ($input) {
            return isset($input->reason_type) && $input->reason_type === 'OTHER';
        });
    }
}
