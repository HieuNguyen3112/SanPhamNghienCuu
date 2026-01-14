<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyWorkApprovalListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        if (array_key_exists('status', $input)) {
            $status = is_string($input['status']) ? trim($input['status']) : $input['status'];
            $status = $status === '' ? null : $status;
            $status = $status === 'ALL_APPROVAL_STATUSES' ? null : $status;

            $statusMap = [
                'PENDING_FACULTY_APPROVAL' => 'pending',
                'APPROVED_BY_FACULTY_FORWARDED_TO_UNIVERSITY' => 'approved',
                'REJECTED_BY_FACULTY' => 'rejected',
            ];

            if (is_string($status) && array_key_exists($status, $statusMap)) {
                $status = $statusMap[$status];
            }

            if (is_string($status) && strtolower($status) === 'all') {
                $status = null;
            }

            $input['status'] = $status;
        }

        foreach (['q', 'kind_code'] as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $value = is_string($input[$key]) ? trim($input[$key]) : $input[$key];
            $input[$key] = $value === '' ? null : $value;
        }

        foreach (['academic_year_id', 'page', 'per_page'] as $key) {
            if (array_key_exists($key, $input) && $input[$key] === '') {
                $input[$key] = null;
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'kind_code' => ['nullable', 'string', 'exists:activity_kinds,code'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected,all'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
