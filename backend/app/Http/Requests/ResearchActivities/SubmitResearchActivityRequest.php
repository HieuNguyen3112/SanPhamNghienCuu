<?php

namespace App\Http\Requests\ResearchActivities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class SubmitResearchActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [];
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
                ->where('ra.id', $activityId)
                ->where('ra.owner_lecturer_id', $lecturerId)
                ->select([
                    'ra.academic_year_id',
                    'ra.type_id',
                    'ra.title',
                    'ak.code as kind_code',
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

            if ($activity->kind_code === 'paper' && ! $activity->type_id) {
                $validator->errors()->add('type_id', 'Vui lòng chọn loại bài báo.');
            }

            if ($activity->kind_code === 'paper') {
                $detail = DB::table('paper_details')
                    ->where('activity_id', $activityId)
                    ->first();

                if (! $detail || ! trim((string) ($detail->journal_name ?? ''))) {
                    $validator->errors()->add('journal_name', 'Vui lòng chọn tạp chí/kỷ yếu.');
                }

                if (! $detail || ! $detail->year) {
                    $validator->errors()->add('year', 'Vui lòng nhập năm xuất bản.');
                }
            }

            $memberCount = DB::table('research_activity_members')
                ->where('activity_id', $activityId)
                ->count();
            if ($memberCount === 0) {
                $validator->errors()->add('members', 'Vui lòng thêm danh sách người tham gia.');
            }
        });
    }
}
