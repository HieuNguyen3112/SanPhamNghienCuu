<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyLecturerHourApprovalRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason_code' => is_string($this->reason_code) ? trim($this->reason_code) : $this->reason_code,
            'reason_detail' => is_string($this->reason_detail) ? trim($this->reason_detail) : $this->reason_detail,
            'activity_ids' => is_array($this->activity_ids) ? array_values($this->activity_ids) : $this->activity_ids,
        ]);
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['required', 'string', 'in:hours_not_reasonable,work_not_eligible,missing_evidence,other'],
            'reason_detail' => ['nullable', 'string', 'max:500'],
            'activity_ids' => ['nullable', 'array', 'min:1'],
            'activity_ids.*' => ['integer', 'distinct'],
        ];
    }
}
