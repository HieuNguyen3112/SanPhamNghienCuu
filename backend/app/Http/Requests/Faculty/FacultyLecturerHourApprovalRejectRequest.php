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
            'reason_code' => is_string($this->reason_code)
                ? strtoupper(trim($this->reason_code))
                : $this->reason_code,
            'reason_detail' => is_string($this->reason_detail) ? trim($this->reason_detail) : $this->reason_detail,
            'decision_mode' => is_string($this->decision_mode) ? trim($this->decision_mode) : $this->decision_mode,
            'activity_ids' => is_array($this->activity_ids) ? array_values($this->activity_ids) : $this->activity_ids,
        ]);
    }

    public function rules(): array
    {
        return [
            'reason_code' => [
                'required',
                'string',
                'in:INVALID_EVIDENCE,INVALID_HOURS,INVALID_ACTIVITY,NOT_ELIGIBLE,MISSING_EVIDENCE,HOURS_NOT_REASONABLE,WORK_NOT_ELIGIBLE,OTHER',
            ],
            'reason_detail' => ['nullable', 'string', 'max:500'],
            'decision_mode' => ['nullable', 'string', 'in:reject,revision'],
            'activity_ids' => ['nullable', 'array', 'min:1'],
            'activity_ids.*' => ['integer', 'distinct'],
        ];
    }
}
