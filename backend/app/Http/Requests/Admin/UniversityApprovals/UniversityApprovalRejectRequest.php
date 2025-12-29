<?php

namespace App\Http\Requests\Admin\UniversityApprovals;

use Illuminate\Foundation\Http\FormRequest;

class UniversityApprovalRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_type' => ['required', 'string', 'max:100'],
            'reason_detail' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('reason_detail', ['required', 'min:5'], function ($input) {
            return isset($input->reason_type) && $input->reason_type === 'OTHER';
        });
    }
}
