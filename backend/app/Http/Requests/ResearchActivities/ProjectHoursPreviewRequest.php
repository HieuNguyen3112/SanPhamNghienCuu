<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class ProjectHoursPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->all();

        foreach (['academic_year_id', 'type_id', 'quantity'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] === '') {
                $payload[$field] = null;
            }
        }

        $members = $payload['members'] ?? [];
        if (is_array($members)) {
            foreach ($members as $index => $member) {
                if (! is_array($member)) {
                    continue;
                }

                foreach (['lecturer_id', 'member_role_id'] as $field) {
                    if (array_key_exists($field, $member) && $member[$field] === '') {
                        $member[$field] = null;
                    }
                }

                $members[$index] = $member;
            }
        }

        $payload['members'] = $members;
        $this->replace($payload);
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'type_id' => ['nullable', 'integer', 'exists:activity_types,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'members' => ['nullable', 'array'],
            'members.*' => ['array'],
            'members.*.lecturer_id' => ['nullable', 'integer', 'exists:lecturers,id'],
            'members.*.member_role_id' => ['nullable', 'integer', 'exists:member_roles,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $members = $this->input('members', []);
            if (! is_array($members)) {
                return;
            }

            $principalRoleId = DB::table('member_roles')
                ->whereRaw('LOWER(code) = ?', ['principal'])
                ->value('id');

            if (! $principalRoleId) {
                return;
            }

            $principalCount = 0;
            foreach ($members as $member) {
                if (! is_array($member)) {
                    continue;
                }

                if ((int) ($member['member_role_id'] ?? 0) === (int) $principalRoleId) {
                    $principalCount++;
                }
            }

            if ($principalCount > 1) {
                $validator->errors()->add('members', 'Đề tài chỉ được có một Chủ nhiệm.');
            }
        });
    }
}

