<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\ParticipationNotificationIndexRequest;
use App\Http\Requests\Lecturer\ParticipationNotificationRejectRequest;
use App\Models\User;
use App\Notifications\ParticipationInvitationAcceptedNotification;
use App\Notifications\ParticipationInvitationRejectedNotification;
use App\Support\AuditLogger;
use App\Support\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LecturerParticipationNotificationController extends Controller
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_ACCEPTED = 'accepted';
    private const STATUS_REJECTED = 'rejected';

    private const ACT_PENDING_MEMBER_CONFIRM = 'pending_member_confirm';
    private const ACT_MEMBER_REJECTED = 'member_rejected';
    private const ACT_PENDING_FACULTY_REVIEW = 'pending_faculty_review';

    public function index(ParticipationNotificationIndexRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $filters = $this->normalizeFilters($request->validated());
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 8)));

        $query = $this->baseQuery($lecturer->id);

        if (! empty($filters['status']) && $filters['status'] !== 'ALL') {
            $query->where('ram.confirmation_status', $this->mapStatusToDb($filters['status']));
        }

        if (! empty($filters['q'])) {
            $keyword = '%' . trim($filters['q']) . '%';
            $query->where('ra.title', 'like', $keyword);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('ram.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('ram.created_at', '<=', $filters['to']);
        }

        $paginator = $query
            ->orderByDesc('ram.created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn($row) => $this->mapListRow($row))
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $requestId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $row = $this->baseQuery($lecturer->id)
            ->where('ram.id', $requestId)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'request not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $this->mapDetailRow($row, $lecturer->id),
        ], Response::HTTP_OK);
    }

    public function confirm(Request $request, int $requestId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $now = now();
        $result = DB::transaction(function () use ($request, $requestId, $now, $lecturer) {
            $member = DB::table('research_activity_members')
                ->where('id', $requestId)
                ->where('lecturer_id', $lecturer->id)
                ->lockForUpdate()
                ->first();

            if (! $member) {
                return ['error' => 'request not found', 'status' => Response::HTTP_NOT_FOUND];
            }

            if ($member->confirmation_status !== self::STATUS_PENDING) {
                return ['error' => 'request already handled', 'status' => Response::HTTP_CONFLICT];
            }

            $activityStatus = DB::table('research_activities as ra')
                ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                ->where('ra.id', $member->activity_id)
                ->value('ast.code');
            if ($activityStatus !== self::ACT_PENDING_MEMBER_CONFIRM) {
                return ['error' => 'activity is not waiting member confirmations', 'status' => Response::HTTP_CONFLICT];
            }

            DB::table('research_activity_members')
                ->where('id', $requestId)
                ->update([
                    'confirmation_status' => self::STATUS_ACCEPTED,
                    'responded_at' => $now,
                    'confirmation_note' => null,
                    'updated_at' => $now,
                ]);

            $this->notifyOwnerOnAccept((int) $member->activity_id, (string) ($lecturer->full_name ?? ''), (int) $requestId);
            $facultyNotification = $this->tryAutoSendToFaculty((int) $member->activity_id, $now, (int) $request->user()->id);

            AuditLogger::log($request, [
                'action_group' => 'approval',
                'action_code' => 'PARTICIPATION_INVITATION_ACCEPTED',
                'action_label' => 'Thanh vien xac nhan tham gia cong trinh',
                'severity' => 'normal',
                'result_status' => 'success',
                'target_type' => 'research_activity_member',
                'target_id' => $requestId,
                'request_http_status' => Response::HTTP_OK,
                'changes' => [
                    'activity_id' => (int) $member->activity_id,
                    'confirmation_status_to' => self::STATUS_ACCEPTED,
                ],
            ], $request->user());

            return [
                'ok' => true,
                'faculty_notification' => $facultyNotification,
            ];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        $facultyNotification = $result['faculty_notification'] ?? null;
        if (is_array($facultyNotification) && isset($facultyNotification['activity_id'])) {
            WorkflowNotification::notifyFacultyBoardByActivityId(
                (int) $facultyNotification['activity_id'],
                WorkflowNotification::makePayload(
                    'work_submitted_to_faculty',
                    'Có hồ sơ công trình mới cần duyệt',
                    (string) ($facultyNotification['message'] ?? 'Có công trình mới đang chờ khoa duyệt.'),
                    '/works/facapprovals?activity_id=' . (int) $facultyNotification['activity_id'],
                    [
                        'activity_id' => (int) $facultyNotification['activity_id'],
                        'lecturer_id' => (int) ($facultyNotification['owner_lecturer_id'] ?? 0),
                    ]
                ),
                (int) ($request->user()?->id ?? 0)
            );
        }

        return $this->show($request, $requestId);
    }

    public function reject(ParticipationNotificationRejectRequest $request, int $requestId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $reason = $request->validated()['reason'];
        $now = now();
        $result = DB::transaction(function () use ($request, $requestId, $now, $lecturer, $reason) {
            $member = DB::table('research_activity_members')
                ->where('id', $requestId)
                ->where('lecturer_id', $lecturer->id)
                ->lockForUpdate()
                ->first();

            if (! $member) {
                return ['error' => 'request not found', 'status' => Response::HTTP_NOT_FOUND];
            }

            if ($member->confirmation_status !== self::STATUS_PENDING) {
                return ['error' => 'request already handled', 'status' => Response::HTTP_CONFLICT];
            }

            $activityStatus = DB::table('research_activities as ra')
                ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                ->where('ra.id', $member->activity_id)
                ->value('ast.code');
            if ($activityStatus !== self::ACT_PENDING_MEMBER_CONFIRM) {
                return ['error' => 'activity is not waiting member confirmations', 'status' => Response::HTTP_CONFLICT];
            }

            DB::table('research_activity_members')
                ->where('id', $requestId)
                ->update([
                    'confirmation_status' => self::STATUS_REJECTED,
                    'responded_at' => $now,
                    'confirmation_note' => $reason,
                    'updated_at' => $now,
                ]);

            $this->markActivityMemberRejected(
                (int) $member->activity_id,
                $now,
                (int) $request->user()->id,
                'member_rejected_by_invitee'
            );
            $this->notifyOwnerOnReject((int) $member->activity_id, (string) ($lecturer->full_name ?? ''), (int) $requestId, $reason);

            AuditLogger::log($request, [
                'action_group' => 'approval',
                'action_code' => 'PARTICIPATION_INVITATION_REJECTED',
                'action_label' => 'Thanh vien tu choi tham gia cong trinh',
                'severity' => 'important',
                'result_status' => 'success',
                'target_type' => 'research_activity_member',
                'target_id' => $requestId,
                'request_http_status' => Response::HTTP_OK,
                'changes' => [
                    'activity_id' => (int) $member->activity_id,
                    'confirmation_status_to' => self::STATUS_REJECTED,
                    'reason' => $reason,
                ],
            ], $request->user());

            return ['ok' => true];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        return $this->show($request, $requestId);
    }

    private function tryAutoSendToFaculty(int $activityId, $now, int $actedByUserId): ?array
    {
        $pendingFacultyId = $this->getStatusId(self::ACT_PENDING_FACULTY_REVIEW);
        if (! $pendingFacultyId) {
            return null;
        }

        $activity = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->leftJoin('lecturers as owner', 'ra.owner_lecturer_id', '=', 'owner.id')
            ->where('ra.id', $activityId)
            ->lockForUpdate()
            ->select([
                'ra.id',
                'ra.status_id',
                'ra.title',
                'ra.owner_lecturer_id',
                'owner.full_name as owner_name',
                'ast.code as status_code',
            ])
            ->first();

        if (! $activity) {
            return null;
        }

        if ($activity->status_code !== self::ACT_PENDING_MEMBER_CONFIRM) {
            return null;
        }

        $hasPending = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->where('ram.activity_id', $activityId)
            ->whereColumn('ram.lecturer_id', '<>', 'ra.owner_lecturer_id')
            ->where('ram.confirmation_status', self::STATUS_PENDING)
            ->exists();

        $hasRejected = DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->where('ram.activity_id', $activityId)
            ->whereColumn('ram.lecturer_id', '<>', 'ra.owner_lecturer_id')
            ->where('ram.confirmation_status', self::STATUS_REJECTED)
            ->exists();

        if ($hasRejected) {
            $this->markActivityMemberRejected($activityId, $now, $actedByUserId, 'member_rejected');
            return null;
        }

        if ($hasPending) {
            return null;
        }

        DB::table('research_activities')
            ->where('id', $activityId)
            ->update([
                'status_id' => $pendingFacultyId,
                'submitted_at' => $now,
                'updated_at' => $now,
            ]);

        $this->resetFacultyApprovalToPending($activityId, $now);

        DB::table('activity_status_histories')->insert([
            'activity_id' => $activityId,
            'from_status_id' => $activity->status_id,
            'to_status_id' => $pendingFacultyId,
            'acted_by_user_id' => $actedByUserId,
            'acted_at' => $now,
            'note' => 'auto_sent_to_faculty_all_members_accepted',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ownerName = trim((string) ($activity->owner_name ?? 'Giảng viên'));
        $activityTitle = trim((string) ($activity->title ?? ''));

        return [
            'activity_id' => (int) $activityId,
            'owner_lecturer_id' => (int) ($activity->owner_lecturer_id ?? 0),
            'message' => $ownerName . ' đã gửi công trình "' .
                ($activityTitle !== '' ? $activityTitle : 'Không rõ tiêu đề') .
                '" lên khoa duyệt.',
        ];
    }

    private function markActivityMemberRejected(int $activityId, $now, int $actedByUserId, string $note = 'member_rejected'): void
    {
        $memberRejectedId = $this->getStatusId(self::ACT_MEMBER_REJECTED);
        if (! $memberRejectedId) {
            return;
        }

        $activity = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->where('ra.id', $activityId)
            ->lockForUpdate()
            ->select(['ra.id', 'ra.status_id', 'ast.code as status_code'])
            ->first();

        if (! $activity || $activity->status_code === self::ACT_MEMBER_REJECTED) {
            return;
        }

        DB::table('research_activities')
            ->where('id', $activityId)
            ->update([
                'status_id' => $memberRejectedId,
                'submitted_at' => null,
                'updated_at' => $now,
            ]);

        DB::table('activity_status_histories')->insert([
            'activity_id' => $activityId,
            'from_status_id' => $activity->status_id,
            'to_status_id' => $memberRejectedId,
            'acted_by_user_id' => $actedByUserId,
            'acted_at' => $now,
            'note' => $note,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function resetFacultyApprovalToPending(int $activityId, $now): void
    {
        $assistantStageId = DB::table('approval_stages')->where('code', 'assistant')->value('id');
        if (! $assistantStageId) {
            return;
        }

        DB::table('activity_approvals')
            ->where('activity_id', $activityId)
            ->where('stage_id', (int) $assistantStageId)
            ->update([
                'status' => 'pending',
                'decided_by_user_id' => null,
                'decided_at' => null,
                'note' => null,
                'updated_at' => $now,
            ]);
    }

    private function getStatusId(string $code): ?int
    {
        $id = DB::table('activity_statuses')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveLecturer(Request $request)
    {
        $user = $request->user();
        return $user?->lecturer;
    }

    private function normalizeFilters(array $validated): array
    {
        return [
            'status' => $validated['status'] ?? null,
            'q' => $validated['q'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'page' => $validated['page'] ?? null,
            'per_page' => $validated['per_page'] ?? null,
        ];
    }

    private function mapStatusToDb(string $status): string
    {
        return match ($status) {
            'ACCEPTED' => self::STATUS_ACCEPTED,
            'REJECTED' => self::STATUS_REJECTED,
            default => self::STATUS_PENDING,
        };
    }

    private function mapStatusToUi(?string $status): string
    {
        return match ($status) {
            self::STATUS_ACCEPTED => 'ACCEPTED',
            self::STATUS_REJECTED => 'REJECTED',
            default => 'PENDING',
        };
    }

    private function mapWorkType(?string $kindCode): string
    {
        return match ($kindCode) {
            'paper' => 'ARTICLE',
            'project' => 'PROJECT',
            'book' => 'BOOK',
            'conference' => 'CONFERENCE',
            default => 'ARTICLE',
        };
    }

    private function mapListRow(object $row): array
    {
        return [
            'id' => (int) $row->request_id,
            'work_title' => $row->title,
            'work_type' => $this->mapWorkType($row->kind_code),
            'your_role' => $row->member_role_name,
            'owner_name' => $row->owner_name,
            'requested_at' => $this->normalizeDateTime($row->requested_at),
            'status' => $this->mapStatusToUi($row->confirmation_status),
            'work_short_info' => $this->buildShortInfo($row),
            'note_from_owner' => $row->activity_note,
            'work_system_status' => $row->activity_status_name,
            'confirmation_log' => $this->buildConfirmationLog($row),
            'members' => [],
            'evidences' => [],
        ];
    }

    private function mapDetailRow(object $row, int $currentLecturerId): array
    {
        return [
            'id' => (int) $row->request_id,
            'work_title' => $row->title,
            'work_type' => $this->mapWorkType($row->kind_code),
            'your_role' => $row->member_role_name,
            'owner_name' => $row->owner_name,
            'requested_at' => $this->normalizeDateTime($row->requested_at),
            'status' => $this->mapStatusToUi($row->confirmation_status),
            'work_short_info' => $this->buildShortInfo($row),
            'note_from_owner' => $row->activity_note,
            'confirmation_log' => $this->buildConfirmationLog($row),
            'work_system_status' => $row->activity_status_name,
            'members' => $this->buildParticipants((int) $row->activity_id, $currentLecturerId),
            'evidences' => $this->buildEvidences($row),
        ];
    }

    private function buildConfirmationLog(object $row): ?array
    {
        $status = $this->mapStatusToUi($row->confirmation_status);
        if ($status === 'PENDING') {
            return null;
        }

        $respondedAt = $this->normalizeDateTime($row->responded_at);
        if (! $respondedAt) {
            return null;
        }

        $payload = [
            'status' => $status,
            'confirmed_at' => $respondedAt,
        ];

        if ($status === 'REJECTED' && $row->confirmation_note) {
            $payload['reason'] = $row->confirmation_note;
        }

        return $payload;
    }

    private function buildParticipants(int $activityId, int $currentLecturerId): array
    {
        return DB::table('research_activity_members as ram')
            ->join('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->join('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('ram.activity_id', $activityId)
            ->orderBy('ram.id')
            ->select([
                'l.id as lecturer_id',
                'l.full_name as lecturer_name',
                'mr.name as role_name',
                'f.name as faculty_name',
                'd.name as department_name',
                'ram.confirmation_status',
            ])
            ->get()
            ->map(function ($row) use ($currentLecturerId) {
                $unit = $row->department_name ?: $row->faculty_name;
                return [
                    'id' => (int) $row->lecturer_id,
                    'full_name' => $row->lecturer_name,
                    'unit' => $unit,
                    'role' => $row->role_name,
                    'status' => $this->mapStatusToUi($row->confirmation_status),
                    'is_current_user' => (int) $row->lecturer_id === (int) $currentLecturerId,
                ];
            })
            ->all();
    }

    private function buildEvidences(object $row): array
    {
        $items = DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $row->activity_id)
            ->orderByDesc('ef.uploaded_at')
            ->select([
                'ef.id',
                'ef.original_name',
                'eft.name as file_type_name',
            ])
            ->get();

        $evidences = [];
        foreach ($items as $item) {
            $previewUrl = route('research.activities.evidence.preview', [
                'activity' => (int) $row->activity_id,
                'evidence' => (int) $item->id,
            ], false);
            $downloadUrl = route('research.activities.evidence.download', [
                'activity' => (int) $row->activity_id,
                'evidence' => (int) $item->id,
            ], false);
            $evidences[] = [
                'id' => (int) $item->id,
                'type' => 'FILE',
                'label' => $item->file_type_name ?: ($item->original_name ?: 'Evidence file'),
                'url' => $previewUrl,
                'preview_url' => $previewUrl,
                'download_url' => $downloadUrl,
            ];
        }

        if (! empty($row->doi)) {
            $evidences[] = [
                'id' => -1,
                'type' => 'LINK',
                'label' => 'DOI',
                'url' => $this->normalizeDoiUrl($row->doi),
            ];
        }

        if (! empty($row->article_url)) {
            $evidences[] = [
                'id' => -2,
                'type' => 'LINK',
                'label' => 'Article URL',
                'url' => $row->article_url,
            ];
        }

        return $evidences;
    }

    private function notifyOwnerOnAccept(int $activityId, string $inviteeName, int $memberId): void
    {
        $activity = DB::table('research_activities as ra')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('users as u', 'l.user_id', '=', 'u.id')
            ->where('ra.id', $activityId)
            ->select(['ra.title', 'u.id as owner_user_id'])
            ->first();

        if (! $activity || ! $activity->owner_user_id) {
            return;
        }

        $owner = User::find($activity->owner_user_id);
        if (! $owner) {
            return;
        }

        $owner->notify(new ParticipationInvitationAcceptedNotification([
            'event_key' => 'participation_accepted',
            'title' => 'Giảng viên đã xác nhận tham gia',
            'message' => trim(($inviteeName ?: 'Một giảng viên') . ' đã xác nhận tham gia công trình ' . ($activity->title ?? '') . '.'),
            'activity_id' => $activityId,
            'invitation_id' => $memberId,
            'action_route' => '/declarations/participatier',
        ]));
    }

    private function notifyOwnerOnReject(int $activityId, string $inviteeName, int $memberId, string $reason): void
    {
        $activity = DB::table('research_activities as ra')
            ->join('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->leftJoin('users as u', 'l.user_id', '=', 'u.id')
            ->where('ra.id', $activityId)
            ->select(['ra.title', 'u.id as owner_user_id'])
            ->first();

        if (! $activity || ! $activity->owner_user_id) {
            return;
        }

        $owner = User::find($activity->owner_user_id);
        if (! $owner) {
            return;
        }

        $owner->notify(new ParticipationInvitationRejectedNotification([
            'event_key' => 'participation_rejected',
            'title' => 'Giảng viên đã từ chối tham gia',
            'message' => trim(($inviteeName ?: 'Một giảng viên') . ' đã từ chối tham gia công trình ' . ($activity->title ?? '') . '.'),
            'activity_id' => $activityId,
            'invitation_id' => $memberId,
            'reason' => $reason,
            'action_route' => '/declarations/participatier',
        ]));
    }

    private function baseQuery(int $lecturerId)
    {
        $yearExpr = $this->activityYearExpression();
        $paperArticleUrlExpr = $this->paperArticleUrlSelectExpression();

        return DB::table('research_activity_members as ram')
            ->join('research_activities as ra', 'ram.activity_id', '=', 'ra.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->join('lecturers as owner', 'ra.owner_lecturer_id', '=', 'owner.id')
            ->leftJoin('paper_details as pd', 'ra.id', '=', 'pd.activity_id')
            ->leftJoin('book_details as bd', 'ra.id', '=', 'bd.activity_id')
            ->leftJoin('project_details as prd', 'ra.id', '=', 'prd.activity_id')
            ->leftJoin('conference_details as cd', 'ra.id', '=', 'cd.activity_id')
            ->where('ram.lecturer_id', $lecturerId)
            ->where('ra.owner_lecturer_id', '<>', $lecturerId)
            ->select([
                'ram.id as request_id',
                'ram.activity_id',
                'ram.confirmation_status',
                'ram.responded_at',
                'ram.confirmation_note',
                'ram.created_at as requested_at',
                'mr.name as member_role_name',
                'mr.code as member_role_code',
                'ra.title',
                'ra.notes as activity_note',
                'owner.full_name as owner_name',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'ast.name as activity_status_name',
                'pd.journal_name',
                'pd.issn',
                'pd.doi',
                DB::raw($paperArticleUrlExpr . ' as article_url'),
                'pd.year as paper_year',
                'bd.publisher',
                'bd.isbn',
                'bd.year as book_year',
                'prd.project_code',
                'prd.decision_no',
                'prd.start_month',
                'cd.conference_name',
                'cd.location',
                'cd.held_on',
                DB::raw($yearExpr . ' as activity_year'),
            ]);
    }

    private function paperArticleUrlSelectExpression(): string
    {
        return Schema::hasColumn('paper_details', 'article_url') ? 'pd.article_url' : 'NULL';
    }

    private function activityYearExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "COALESCE(pd.year, bd.year, CAST(strftime('%Y', prd.start_month) AS INTEGER), CAST(strftime('%Y', cd.held_on) AS INTEGER), CAST(strftime('%Y', ra.start_date) AS INTEGER), CAST(strftime('%Y', ra.created_at) AS INTEGER))",
            'pgsql' => 'COALESCE(pd.year, bd.year, CAST(EXTRACT(YEAR FROM prd.start_month) AS INTEGER), CAST(EXTRACT(YEAR FROM cd.held_on) AS INTEGER), CAST(EXTRACT(YEAR FROM ra.start_date) AS INTEGER), CAST(EXTRACT(YEAR FROM ra.created_at) AS INTEGER))',
            default => 'COALESCE(pd.year, bd.year, YEAR(prd.start_month), YEAR(cd.held_on), YEAR(ra.start_date), YEAR(ra.created_at))',
        };
    }

    private function buildShortInfo(object $row): string
    {
        $parts = [];

        switch ($row->kind_code) {
            case 'paper':
                if ($row->journal_name) {
                    $parts[] = $row->journal_name;
                }
                if ($row->doi) {
                    $parts[] = 'DOI: ' . $row->doi;
                } elseif ($row->issn) {
                    $parts[] = 'ISSN: ' . $row->issn;
                }
                break;
            case 'book':
                if ($row->publisher) {
                    $parts[] = $row->publisher;
                }
                if ($row->isbn) {
                    $parts[] = 'ISBN: ' . $row->isbn;
                }
                break;
            case 'project':
                if ($row->project_code) {
                    $parts[] = 'CODE: ' . $row->project_code;
                } elseif ($row->decision_no) {
                    $parts[] = 'DECISION: ' . $row->decision_no;
                }
                break;
            case 'conference':
                if ($row->conference_name) {
                    $parts[] = $row->conference_name;
                }
                if ($row->location) {
                    $parts[] = $row->location;
                }
                break;
        }

        if (! empty($row->activity_year)) {
            $parts[] = (string) $row->activity_year;
        }

        return implode(' - ', array_filter($parts));
    }

    private function normalizeDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        return (string) $value;
    }

    private function normalizeDoiUrl(string $doi): string
    {
        $trimmed = trim($doi);
        if ($trimmed === '') {
            return '';
        }
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }
        return 'https://doi.org/' . $trimmed;
    }
}
