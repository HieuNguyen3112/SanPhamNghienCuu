<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

class FacultyAuditLogIndexRequest extends FormRequest
{
    private const ACTION_GROUPS = [
        'auth',
        'lecturer',
        'research',
        'approval',
        'config',
        'security',
    ];

    private const SEVERITIES = ['normal', 'important', 'dangerous'];

    private const RESULT_STATUSES = ['success', 'failure'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'keyword' => is_string($this->keyword) ? trim($this->keyword) : $this->keyword,
            'q' => is_string($this->q) ? trim($this->q) : $this->q,
            'action_group' => is_string($this->action_group) ? trim($this->action_group) : $this->action_group,
            'action_code' => is_string($this->action_code) ? trim($this->action_code) : $this->action_code,
            'severity' => is_string($this->severity) ? trim($this->severity) : $this->severity,
            'level' => is_string($this->level) ? trim($this->level) : $this->level,
            'result' => is_string($this->result) ? trim($this->result) : $this->result,
            'scope' => is_string($this->scope) ? trim($this->scope) : $this->scope,
        ]);
    }

    public function rules(): array
    {
        return [
            'scope' => ['nullable', 'string', 'in:FACULTY,GLOBAL'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'actor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_group' => ['nullable', 'string', 'in:' . implode(',', self::ACTION_GROUPS)],
            'action_code' => ['nullable', 'string', 'max:100'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'level' => ['nullable', 'string', 'in:' . implode(',', self::SEVERITIES)],
            'severity' => ['nullable', 'string', 'in:' . implode(',', self::SEVERITIES)],
            'result' => ['nullable', 'string', 'in:' . implode(',', self::RESULT_STATUSES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'max:50'],
        ];
    }
}
