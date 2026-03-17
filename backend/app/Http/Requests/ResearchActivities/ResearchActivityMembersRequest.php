<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResearchActivityMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $isExternal = filter_var($item['is_external'] ?? false, FILTER_VALIDATE_BOOL);
            $item['is_external'] = $isExternal;
            if ($isExternal) {
                $item['lecturer_id'] = null;
            }

            $nullableFields = [
                'lecturer_id',
                'contribution_share',
                'hours_assigned',
                'external_full_name',
                'external_department_name',
            ];

            foreach ($nullableFields as $field) {
                if (array_key_exists($field, $item) && $item[$field] === '') {
                    $item[$field] = null;
                }
            }

            $items[$index] = $item;
        }

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*' => ['array'],
            'items.*.lecturer_id' => ['nullable', 'integer', 'exists:lecturers,id'],
            'items.*.member_role_id' => ['required', 'integer', 'exists:member_roles,id'],
            'items.*.is_external' => ['nullable', 'boolean'],
            'items.*.external_full_name' => ['nullable', 'string', 'max:255'],
            'items.*.external_department_name' => ['nullable', 'string', 'max:255'],
            'items.*.contribution_share' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'items.*.hours_assigned' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);
            if (! is_array($items)) {
                return;
            }

            $internalLecturerIds = [];

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $isExternal = filter_var($item['is_external'] ?? false, FILTER_VALIDATE_BOOL);
                $lecturerId = $item['lecturer_id'] ?? null;

                if ($isExternal) {
                    $externalName = trim((string) ($item['external_full_name'] ?? ''));
                    if ($externalName === '') {
                        $validator->errors()->add("items.$index.external_full_name", 'Vui lòng nhập họ tên thành viên ngoài trường.');
                    }
                    continue;
                }

                if (! is_int($lecturerId) && ! ctype_digit((string) $lecturerId)) {
                    $validator->errors()->add("items.$index.lecturer_id", 'Vui lòng chọn giảng viên tham gia.');
                    continue;
                }

                $normalizedLecturerId = (int) $lecturerId;
                if (in_array($normalizedLecturerId, $internalLecturerIds, true)) {
                    $validator->errors()->add("items.$index.lecturer_id", 'Giảng viên bị trùng trong danh sách thành viên.');
                    continue;
                }

                $internalLecturerIds[] = $normalizedLecturerId;
            }
        });
    }
}
