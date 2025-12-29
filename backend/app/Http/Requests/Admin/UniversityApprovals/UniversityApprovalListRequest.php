<?php

namespace App\Http\Requests\Admin\UniversityApprovals;

use Illuminate\Foundation\Http\FormRequest;

class UniversityApprovalListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'academic_year_code' => ['nullable', 'string', 'max:20', 'exists:academic_years,code'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'kind_code' => ['nullable', 'string', 'exists:activity_kinds,code'],
            'status' => [
                'nullable',
                'string',
                'in:all,pending,approved,rejected,PENDING_UNIVERSITY_APPROVAL,APPROVED_BY_UNIVERSITY_FINALIZED_HOURS,REJECTED_BY_UNIVERSITY_RETURNED_TO_FACULTY',
            ],
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }
}
