<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\ParticipationNotificationIndexRequest;
use App\Http\Requests\Lecturer\ParticipationNotificationRejectRequest;
use App\Models\User;
use App\Notifications\ParticipationInvitationAcceptedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LecturerParticipationNotificationController extends Controller
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_ACCEPTED = 'accepted';
    private const STATUS_REJECTED = 'rejected';

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

        $detail = $this->mapDetailRow($row, $lecturer->id);

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => $detail,
        ], Response::HTTP_OK);
    }

    public function confirm(Request $request, int $requestId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $member = DB::table('research_activity_members')
            ->where('id', $requestId)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $member) {
            return response()->json(['message' => 'request not found'], Response::HTTP_NOT_FOUND);
        }

        if ($member->confirmation_status !== self::STATUS_PENDING) {
            return response()->json(['message' => 'request already handled'], Response::HTTP_CONFLICT);
        }

        $now = now();
        DB::transaction(function () use ($requestId, $now, $member, $lecturer) {
            DB::table('research_activity_members')
                ->where('id', $requestId)
                ->update([
                    'confirmation_status' => self::STATUS_ACCEPTED,
                    'responded_at' => $now,
                    'confirmation_note' => null,
                    'updated_at' => $now,
                ]);

            $this->notifyOwnerOnAccept((int) $member->activity_id, (string) ($lecturer->full_name ?? ''), (int) $requestId);
        });

        return $this->show($request, $requestId);
    }

    public function reject(ParticipationNotificationRejectRequest $request, int $requestId)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $member = DB::table('research_activity_members')
            ->where('id', $requestId)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $member) {
            return response()->json(['message' => 'request not found'], Response::HTTP_NOT_FOUND);
        }

        if ($member->confirmation_status !== self::STATUS_PENDING) {
            return response()->json(['message' => 'request already handled'], Response::HTTP_CONFLICT);
        }

        $now = now();
        DB::table('research_activity_members')
            ->where('id', $requestId)
            ->update([
                'confirmation_status' => self::STATUS_REJECTED,
                'responded_at' => $now,
                'confirmation_note' => $request->validated()['reason'],
                'updated_at' => $now,
            ]);

        return $this->show($request, $requestId);
    }

    private function resolveLecturer(Request $request)
    {
        $user = $request->user();
        return $user?->lecturer;
    }

    private function normalizeFilters(array $validated): array
    {
        $filters = [
            'status' => $validated['status'] ?? null,
            'q' => $validated['q'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'page' => $validated['page'] ?? null,
            'per_page' => $validated['per_page'] ?? null,
        ];

        if ($filters['from'] && $filters['to']) {
            if ($filters['from'] > $filters['to']) {
                [$filters['from'], $filters['to']] = [$filters['to'], $filters['from']];
            }
        }

        return $filters;
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
            $evidences[] = [
                'id' => (int) $item->id,
                'type' => 'FILE',
                'label' => $item->file_type_name ?: ($item->original_name ?: 'Evidence file'),
                'url' => route('lecturer.works.attachments.download', ['attachment' => $item->id], false),
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
            'title' => 'Giảng viên đã xác nhận tham gia',
            'message' => trim(($inviteeName ?: 'Một giảng viên') . ' đã xác nhận tham gia công trình ' . ($activity->title ?? '') . '.'),
            'activity_id' => $activityId,
            'invitation_id' => $memberId,
            'action_route' => '/declarations/participatier',
        ]));
    }

    private function baseQuery(int $lecturerId)
    {
        $yearExpr = $this->activityYearExpression();

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
                'pd.article_url',
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

    private function activityYearExpression(): string
    {
        return 'COALESCE(pd.year, bd.year, YEAR(prd.start_month), YEAR(cd.held_on), YEAR(ra.start_date), YEAR(ra.created_at))';
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
            default:
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
