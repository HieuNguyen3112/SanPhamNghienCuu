<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class SubmitResearchActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();

        if (array_key_exists('minor_change', $input)) {
            $value = $input['minor_change'];
            if ($value === '' || $value === null) {
                $input['minor_change'] = null;
            } elseif (is_string($value)) {
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                    $input['minor_change'] = true;
                } elseif (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                    $input['minor_change'] = false;
                }
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'minor_change' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $lecturerId = DB::table('lecturers')
                ->where('user_id', $user->id)
                ->value('id');

            if (! $lecturerId) {
                return;
            }

            $activityId = (int) $this->route('activity');
            if (! $activityId) {
                return;
            }

            $activity = DB::table('research_activities as ra')
                ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
                ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
                ->where('ra.id', $activityId)
                ->where('ra.owner_lecturer_id', $lecturerId)
                ->select([
                    'ra.academic_year_id',
                    'ra.type_id',
                    'ra.title',
                    'ak.code as kind_code',
                    'at.code as type_code',
                ])
                ->first();

            if (! $activity) {
                return;
            }

            if (! $activity->academic_year_id) {
                $validator->errors()->add('academic_year_id', 'Vui lòng chọn niên học.');
            }

            if (! trim((string) ($activity->title ?? ''))) {
                $validator->errors()->add('title', 'Vui lòng nhập tên bài báo.');
            }

            if ($activity->kind_code === 'paper') {
                $detail = DB::table('paper_details')
                    ->where('activity_id', $activityId)
                    ->first();

                $typeCode = Str::lower((string) ($activity->type_code ?? ''));
                $isConferenceTypeByCode = Str::contains($typeCode, [
                    'conference',
                    'report',
                    'bao_cao',
                    'hoi_nghi',
                    'proceeding',
                ]);
                $hasConferenceData = $detail
                    && (
                        trim((string) ($detail->conference_name ?? '')) !== ''
                        || trim((string) ($detail->conference_level ?? '')) !== ''
                        || trim((string) ($detail->conference_research_field ?? '')) !== ''
                        || trim((string) ($detail->conference_organization ?? '')) !== ''
                        || trim((string) ($detail->conference_isbn ?? '')) !== ''
                        || (bool) ($detail->conference_has_isbn ?? false)
                        || $detail->conference_point !== null
                    );
                $isConferenceReport = $isConferenceTypeByCode || $hasConferenceData;

                if (! $activity->type_id && ! $isConferenceReport) {
                    $validator->errors()->add('type_id', 'Vui lòng chọn loại bài báo.');
                }

                if ($isConferenceReport) {
                    if (! $detail || ! trim((string) ($detail->conference_name ?? ''))) {
                        $validator->errors()->add('conference_name', 'Vui lòng chọn hoặc nhập hội nghị khoa học.');
                    }

                    if (! $detail || ! trim((string) ($detail->conference_level ?? ''))) {
                        $validator->errors()->add('conference_level', 'Vui lòng chọn cấp hội nghị.');
                    }

                    if (! $detail || ! trim((string) ($detail->conference_research_field ?? ''))) {
                        $validator->errors()->add('conference_research_field', 'Vui lòng nhập lĩnh vực hội nghị.');
                    }

                    if (! $detail || ! trim((string) ($detail->conference_organization ?? ''))) {
                        $validator->errors()->add('conference_organization', 'Vui lòng nhập đơn vị tổ chức hội nghị.');
                    }

                    if (! $detail || $detail->conference_point === null) {
                        $validator->errors()->add('conference_point', 'Vui lòng nhập điểm quy đổi hội nghị.');
                    }

                    $hasIsbn = (bool) ($detail->conference_has_isbn ?? false);
                    if ($hasIsbn && ! trim((string) ($detail->conference_isbn ?? ''))) {
                        $validator->errors()->add('conference_isbn', 'Vui lòng nhập ISBN của hội nghị.');
                    }
                } else {
                    if (! $detail || ! trim((string) ($detail->journal_name ?? ''))) {
                        $validator->errors()->add('journal_name', 'Vui lòng chọn tạp chí/kỷ yếu.');
                    }
                }

                if (! $detail || ! $detail->year) {
                    $validator->errors()->add('year', 'Vui lòng nhập năm xuất bản.');
                }
            }

            $memberCount = DB::table('research_activity_members')
                ->where('activity_id', $activityId)
                ->count();
            if ($memberCount === 0) {
                $validator->errors()->add('members', 'Vui lòng thêm danh sách tác giả.');
            }
        });
    }
}
