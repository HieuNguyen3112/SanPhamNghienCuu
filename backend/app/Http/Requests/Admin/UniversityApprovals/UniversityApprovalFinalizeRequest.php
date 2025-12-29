<?php

namespace App\Http\Requests\Admin\UniversityApprovals;

use Illuminate\Foundation\Http\FormRequest;
class UniversityApprovalFinalizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'members' => ['required', 'array', 'min:1'],
            'members.*.lecturer_id' => ['required', 'integer', 'exists:lecturers,id'],
            'members.*.official_hours' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
