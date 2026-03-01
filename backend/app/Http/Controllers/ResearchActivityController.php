<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResearchActivities\ResearchActivityDetailRequest;
use App\Http\Requests\ResearchActivities\ProjectHoursPreviewRequest;
use App\Http\Requests\ResearchActivities\ResearchActivityMembersRequest;
use App\Http\Requests\ResearchActivities\SubmitResearchActivityRequest;
use App\Http\Requests\ResearchActivities\StoreResearchActivityRequest;
use App\Http\Requests\ResearchActivities\UpdateResearchActivityRequest;
use App\Notifications\ParticipationInvitationNotification;
use App\Services\Hours\ProjectHoursCalculator;
use App\Support\AuditLogger;
use App\Support\WorkflowNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResearchActivityController extends Controller
{
    // Activity statuses (activity_statuses.code)
    private const STATUS_DRAFT = 'draft';
    private const STATUS_PENDING_MEMBER_CONFIRM = 'pending_member_confirm';
    private const STATUS_MEMBER_REJECTED = 'member_rejected';
    private const STATUS_PENDING_FACULTY_REVIEW = 'pending_faculty_review';
    private const STATUS_APPROVED = 'approved';
    private const STATUS_REJECTED = 'rejected';
    private ProjectHoursCalculator $projectHoursCalculator;

    public function __construct(ProjectHoursCalculator $projectHoursCalculator)
    {
        $this->projectHoursCalculator = $projectHoursCalculator;
    }

    public function store(StoreResearchActivityRequest $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();

        if (! $this->activityTypeMatchesKind($data['type_id'] ?? null, $data['kind_id'])) {
            return response()->json(['message' => 'type_id does not match kind_id'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $draftStatusId = $this->getStatusId(self::STATUS_DRAFT);
        if (! $draftStatusId) {
            return response()->json(['message' => 'draft status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => $this->generateActivityCode(),
            'owner_lecturer_id' => $lecturer->id,
            'kind_id' => $data['kind_id'],
            'type_id' => $data['type_id'] ?? null,
            'academic_year_id' => $data['academic_year_id'],
            'status_id' => $draftStatusId,
            'title' => $data['title'],
            'abstract' => $data['abstract'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'submitted_at' => null,
            'approved_at' => null,
            'total_hours_calc' => null,
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'research activity created',
            'data' => $this->serializeActivity($activityId),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateResearchActivityRequest $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isEditableStatus($current->status_code)) {
            return response()->json(['message' => 'activity is not editable'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        if (array_key_exists('kind_id', $data) && (int) $data['kind_id'] !== (int) $current->kind_id) {
            return response()->json(['message' => 'kind_id cannot be changed'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (array_key_exists('type_id', $data) && ! $this->activityTypeMatchesKind($data['type_id'], $current->kind_id)) {
            return response()->json(['message' => 'type_id does not match kind_id'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $updates = array_intersect_key($data, array_flip([
            'type_id',
            'academic_year_id',
            'title',
            'abstract',
            'start_date',
            'end_date',
            'quantity',
            'notes',
        ]));

        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('research_activities')->where('id', $activity)->update($updates);
        }

        return response()->json([
            'message' => 'research activity updated',
            'data' => $this->serializeActivity($activity),
        ], Response::HTTP_OK);
    }

    public function upsertDetail(ResearchActivityDetailRequest $request, int $activity, string $detail)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isEditableStatus($current->status_code)) {
            return response()->json(['message' => 'activity is not editable'], Response::HTTP_FORBIDDEN);
        }

        $detailMap = $this->detailKindMap();
        $expectedKind = $detailMap[$detail] ?? null;
        if (! $expectedKind) {
            return response()->json(['message' => 'invalid detail kind'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($current->kind_code !== $expectedKind) {
            return response()->json(['message' => 'detail kind does not match activity kind'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validated();
        unset($data['detail']);

        $table = $detail;
        $now = now();
        $exists = DB::table($table)->where('activity_id', $activity)->exists();
        $payload = array_merge($data, [
            'activity_id' => $activity,
            'updated_at' => $now,
        ]);

        if (! $exists) {
            $payload['created_at'] = $now;
            DB::table($table)->insert($payload);
        } else {
            DB::table($table)->where('activity_id', $activity)->update($payload);
        }

        return response()->json([
            'message' => 'activity details updated',
            'data' => $this->serializeDetail($table, $activity),
        ], Response::HTTP_OK);
    }

    public function syncMembers(ResearchActivityMembersRequest $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isEditableStatus($current->status_code)) {
            return response()->json(['message' => 'activity is not editable'], Response::HTTP_FORBIDDEN);
        }

        $items = $request->validated()['items'] ?? [];
        $now = now();
        $ownerLecturerId = (int) $current->owner_lecturer_id;

        $synced = DB::transaction(function () use ($activity, $items, $now, $ownerLecturerId) {
            $itemsToSync = $items;
            $ownerInPayload = false;
            foreach ($itemsToSync as $memberItem) {
                if ((int) ($memberItem['lecturer_id'] ?? 0) === $ownerLecturerId) {
                    $ownerInPayload = true;
                    break;
                }
            }

            if (! $ownerInPayload) {
                $ownerRoleId = DB::table('research_activity_members')
                    ->where('activity_id', $activity)
                    ->where('lecturer_id', $ownerLecturerId)
                    ->value('member_role_id');

                if (! $ownerRoleId) {
                    $preferredCodes = ['principal', 'corresponding_author', 'chief_editor', 'member'];
                    $roleIdsByCode = DB::table('member_roles')
                        ->whereIn('code', $preferredCodes)
                        ->pluck('id', 'code');

                    foreach ($preferredCodes as $code) {
                        if (isset($roleIdsByCode[$code])) {
                            $ownerRoleId = (int) $roleIdsByCode[$code];
                            break;
                        }
                    }
                }

                if (! $ownerRoleId) {
                    $ownerRoleId = DB::table('member_roles')->value('id');
                }

                if ($ownerRoleId) {
                    $itemsToSync[] = [
                        'lecturer_id' => $ownerLecturerId,
                        'member_role_id' => (int) $ownerRoleId,
                        'contribution_share' => null,
                        'hours_assigned' => null,
                    ];
                }
            }

            $handled = [];
            foreach ($itemsToSync as $item) {
                $payload = [
                    'activity_id' => $activity,
                    'lecturer_id' => $item['lecturer_id'],
                    'member_role_id' => $item['member_role_id'],
                    'contribution_share' => $item['contribution_share'] ?? null,
                    'hours_assigned' => $item['hours_assigned'] ?? null,
                    'updated_at' => $now,
                ];

                $existing = DB::table('research_activity_members')
                    ->where('activity_id', $activity)
                    ->where('lecturer_id', $item['lecturer_id'])
                    ->first();

                $isOwner = (int) $item['lecturer_id'] === $ownerLecturerId;
                $statusPayload = [];

                if ($isOwner) {
                    $statusPayload = [
                        'confirmation_status' => 'accepted',
                        'responded_at' => $now,
                        'confirmation_note' => null,
                    ];
                } elseif (! $existing) {
                    $statusPayload = [
                        'confirmation_status' => 'pending',
                        'responded_at' => null,
                        'confirmation_note' => null,
                    ];
                } else {
                    $changed =
                        (int) $existing->member_role_id !== (int) $item['member_role_id']
                        || (string) ($existing->contribution_share ?? '') !== (string) ($item['contribution_share'] ?? '')
                        || (string) ($existing->hours_assigned ?? '') !== (string) ($item['hours_assigned'] ?? '');

                    if ($changed) {
                        $statusPayload = [
                            'confirmation_status' => 'pending',
                            'responded_at' => null,
                            'confirmation_note' => null,
                        ];
                    } elseif ($existing->confirmation_status === null || $existing->confirmation_status === '') {
                        $statusPayload = [
                            'confirmation_status' => 'pending',
                            'responded_at' => null,
                            'confirmation_note' => null,
                        ];
                    }
                }

                if (! $existing) {
                    $payload['created_at'] = $now;
                    DB::table('research_activity_members')->insert(array_merge($payload, $statusPayload));
                } else {
                    DB::table('research_activity_members')
                        ->where('activity_id', $activity)
                        ->where('lecturer_id', $item['lecturer_id'])
                        ->update(array_merge($payload, $statusPayload));
                }

                $handled[] = $item['lecturer_id'];
            }

            DB::table('research_activity_members')
                ->where('activity_id', $activity)
                ->whereNotIn('lecturer_id', $handled)
                ->delete();

            return DB::table('research_activity_members')
                ->where('activity_id', $activity)
                ->get()
                ->map(fn($row) => (array) $row)
                ->all();
        });

        return response()->json([
            'message' => 'members synced',
            'data' => $synced,
        ], Response::HTTP_OK);
    }

    public function reinviteMember(Request $request, int $activity, int $member)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isEditableStatus($current->status_code)) {
            return response()->json([
                'message' => 'activity is not in a reinvitable state',
                'code' => 'ACTIVITY_NOT_REINVITABLE',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pendingMemberConfirmId = $this->getStatusId(self::STATUS_PENDING_MEMBER_CONFIRM);
        if (! $pendingMemberConfirmId) {
            return response()->json([
                'message' => 'pending_member_confirm status not configured',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        $payload = DB::transaction(function () use ($activity, $member, $current, $pendingMemberConfirmId, $now, $user) {
            $lockedActivity = DB::table('research_activities as ra')
                ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                ->where('ra.id', $activity)
                ->lockForUpdate()
                ->select([
                    'ra.id',
                    'ra.status_id',
                    'ra.title',
                    'ra.owner_lecturer_id',
                    'ast.code as status_code',
                ])
                ->first();

            if (! $lockedActivity || (int) $lockedActivity->owner_lecturer_id !== (int) $current->owner_lecturer_id) {
                return [
                    'error' => 'activity not found',
                    'code' => 'ACTIVITY_NOT_FOUND',
                    'status' => Response::HTTP_NOT_FOUND,
                ];
            }

            if (! $this->isEditableStatus($lockedActivity->status_code)) {
                return [
                    'error' => 'activity is not in a reinvitable state',
                    'code' => 'ACTIVITY_NOT_REINVITABLE',
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ];
            }

            $memberRow = DB::table('research_activity_members as ram')
                ->leftJoin('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
                ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
                ->where('ram.id', $member)
                ->where('ram.activity_id', $activity)
                ->lockForUpdate()
                ->select([
                    'ram.id',
                    'ram.activity_id',
                    'ram.lecturer_id',
                    'ram.member_role_id',
                    'ram.confirmation_status',
                    'ram.confirmation_note',
                    'ram.responded_at',
                    'l.code as lecturer_code',
                    'l.full_name as lecturer_full_name',
                    'l.user_id as lecturer_user_id',
                    'mr.code as member_role_code',
                    'mr.name as member_role_name',
                ])
                ->first();

            if (! $memberRow) {
                return [
                    'error' => 'member not found in activity',
                    'code' => 'MEMBER_NOT_FOUND',
                    'status' => Response::HTTP_NOT_FOUND,
                ];
            }

            if ((int) $memberRow->lecturer_id === (int) $lockedActivity->owner_lecturer_id) {
                return [
                    'error' => 'owner participation cannot be reinvited',
                    'code' => 'OWNER_MEMBER_CANNOT_REINVITE',
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ];
            }

            if ($memberRow->confirmation_status !== 'rejected') {
                return [
                    'error' => 'only rejected member can be reinvited',
                    'code' => 'MEMBER_NOT_REJECTED',
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ];
            }

            DB::table('research_activity_members')
                ->where('id', $memberRow->id)
                ->update([
                    'confirmation_status' => 'pending',
                    'responded_at' => null,
                    'confirmation_note' => null,
                    'updated_at' => $now,
                ]);

            if ($lockedActivity->status_code !== self::STATUS_PENDING_MEMBER_CONFIRM) {
                DB::table('research_activities')
                    ->where('id', $activity)
                    ->update([
                        'status_id' => $pendingMemberConfirmId,
                        'submitted_at' => null,
                        'approved_at' => null,
                        'updated_at' => $now,
                    ]);

                DB::table('activity_status_histories')->insert([
                    'activity_id' => $activity,
                    'from_status_id' => $lockedActivity->status_id,
                    'to_status_id' => $pendingMemberConfirmId,
                    'acted_by_user_id' => $user->id,
                    'acted_at' => $now,
                    'note' => 'member_reinvited:' . $memberRow->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return [
                'activity' => $lockedActivity,
                'member' => (array) $memberRow,
            ];
        });

        if (isset($payload['error'])) {
            return response()->json([
                'message' => $payload['error'],
                'code' => $payload['code'] ?? 'REINVITE_FAILED',
            ], (int) ($payload['status'] ?? Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $this->sendParticipationInvitationNotification(
            (int) $activity,
            $payload['member']
        );

        AuditLogger::log($request, [
            'action_group' => 'approval',
            'action_code' => 'WORK_MEMBER_REINVITED',
            'action_label' => 'Giang vien gui lai yeu cau xac nhan thanh vien',
            'severity' => 'normal',
            'result_status' => 'success',
            'target_type' => 'research_activity_member',
            'target_id' => (int) $member,
            'request_http_status' => Response::HTTP_OK,
            'changes' => [
                'activity_id' => (int) $activity,
                'confirmation_status_from' => 'rejected',
                'confirmation_status_to' => 'pending',
            ],
        ], $user);

        return response()->json([
            'message' => 'member invitation resent',
            'data' => array_merge($this->serializeActivity($activity), [
                'status_code' => self::STATUS_PENDING_MEMBER_CONFIRM,
            ]),
            'member' => [
                'id' => (int) $payload['member']['id'],
                'lecturer_id' => (int) $payload['member']['lecturer_id'],
                'lecturer_code' => $payload['member']['lecturer_code'],
                'lecturer_full_name' => $payload['member']['lecturer_full_name'],
                'member_role_code' => $payload['member']['member_role_code'],
                'member_role_name' => $payload['member']['member_role_name'],
                'confirmation_status' => 'pending',
            ],
            'workflow' => [
                'status_code' => self::STATUS_PENDING_MEMBER_CONFIRM,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * FLOW:
     * - GV bấm "Yêu cầu duyệt"
     * - Nếu có member (khác owner) pending -> set pending_member_confirm + gửi notify cho pending
     * - Nếu không có pending (chỉ owner) -> set pending_faculty_review luôn
     * - Nếu đang member_rejected/rejected -> cho submit lại (sau khi xử lý)
     * - Nếu có member rejected -> chặn submit, trả list
     */
    public function submit(SubmitResearchActivityRequest $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        // Cho phép resubmit nếu bị member reject hoặc khoa reject
        if (! in_array($current->status_code, [self::STATUS_DRAFT, self::STATUS_MEMBER_REJECTED, self::STATUS_REJECTED], true)) {
            return response()->json([
                'message' => 'only draft/member_rejected/rejected activities can be submitted',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pendingMemberConfirmId = $this->getStatusId(self::STATUS_PENDING_MEMBER_CONFIRM);
        $memberRejectedId = $this->getStatusId(self::STATUS_MEMBER_REJECTED);
        $pendingFacultyReviewId = $this->getStatusId(self::STATUS_PENDING_FACULTY_REVIEW);

        if (! $pendingMemberConfirmId || ! $memberRejectedId || ! $pendingFacultyReviewId) {
            return response()->json([
                'message' => 'required statuses not configured (pending_member_confirm/member_rejected/pending_faculty_review)',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();

        $ownerLecturerId = (int) $current->owner_lecturer_id;
        $activityTitle = (string) ($current->title ?? '');
        $ownerName = DB::table('lecturers')->where('id', $ownerLecturerId)->value('full_name') ?? '';
        $roleNames = DB::table('member_roles')->pluck('name', 'id')->all();

        // Nếu có rejected member -> chặn submit và trả thông tin
        $rejectedMembers = DB::table('research_activity_members as ram')
            ->leftJoin('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activity)
            ->where('ram.lecturer_id', '!=', $ownerLecturerId)
            ->where('ram.confirmation_status', 'rejected')
            ->select([
                'ram.id',
                'ram.lecturer_id',
                'l.full_name as lecturer_full_name',
                'l.code as lecturer_code',
                'mr.name as member_role_name',
                'ram.confirmation_note',
                'ram.responded_at',
            ])
            ->get()
            ->map(fn($r) => (array) $r)
            ->all();

        if (count($rejectedMembers) > 0) {
            // đảm bảo status đang ở member_rejected để UI hiểu
            DB::transaction(function () use ($activity, $memberRejectedId, $now, $user) {
                $locked = DB::table('research_activities as ra')
                    ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                    ->where('ra.id', $activity)
                    ->lockForUpdate()
                    ->select(['ra.status_id', 'ast.code as status_code'])
                    ->first();

                if (! $locked) {
                    abort(Response::HTTP_NOT_FOUND, 'activity not found');
                }

                if ($locked->status_code !== self::STATUS_MEMBER_REJECTED) {
                    DB::table('research_activities')->where('id', $activity)->update([
                        'status_id' => $memberRejectedId,
                        'submitted_at' => null,
                        'updated_at' => $now,
                    ]);

                    DB::table('activity_status_histories')->insert([
                        'activity_id' => $activity,
                        'from_status_id' => $locked->status_id,
                        'to_status_id' => $memberRejectedId,
                        'acted_by_user_id' => $user->id,
                        'acted_at' => $now,
                        'note' => 'member_rejected_block_submit',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

            return response()->json([
                'message' => 'Cannot request approval: some members rejected participation. Please remove or re-invite them.',
                'code' => 'MEMBERS_REJECTED',
                'rejected_members' => $rejectedMembers,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Pending members để gửi notify
        $pendingRows = DB::table('research_activity_members as ram')
            ->leftJoin('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activity)
            ->where('ram.lecturer_id', '!=', $ownerLecturerId)
            ->where('ram.confirmation_status', 'pending')
            ->select([
                'ram.id',
                'ram.lecturer_id',
                'ram.member_role_id',
                'l.code as lecturer_code',
                'l.full_name as lecturer_full_name',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
            ])
            ->get()
            ->all();

        $hasPending = count($pendingRows) > 0;

        DB::transaction(function () use (
            $activity,
            $pendingMemberConfirmId,
            $pendingFacultyReviewId,
            $now,
            $user,
            $hasPending
        ) {
            $locked = DB::table('research_activities as ra')
                ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
                ->where('ra.id', $activity)
                ->lockForUpdate()
                ->select(['ra.status_id', 'ast.code as status_code'])
                ->first();

            if (! $locked) {
                abort(Response::HTTP_NOT_FOUND, 'activity not found');
            }

            if (! in_array($locked->status_code, [self::STATUS_DRAFT, self::STATUS_MEMBER_REJECTED, self::STATUS_REJECTED], true)) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'only draft/member_rejected/rejected activities can be submitted');
            }

            $toStatusId = $hasPending ? $pendingMemberConfirmId : $pendingFacultyReviewId;
            $note = $hasPending ? 'requested_approval_waiting_members' : 'auto_sent_to_faculty_no_pending';

            DB::table('research_activities')->where('id', $activity)->update([
                'status_id' => $toStatusId,
                'submitted_at' => $hasPending ? null : $now, // lên khoa thì set submitted_at
                'approved_at' => null,
                'updated_at' => $now,
            ]);

            // Nếu lên khoa luôn -> reset approval stage khoa = pending
            if (! $hasPending) {
                $this->resetFacultyApprovalToPending($activity, $now);
            }

            DB::table('activity_status_histories')->insert([
                'activity_id' => $activity,
                'from_status_id' => $locked->status_id,
                'to_status_id' => $toStatusId,
                'acted_by_user_id' => $user->id,
                'acted_at' => $now,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        // Gửi notify cho pending members (đúng nghiệp vụ)
        foreach ($pendingRows as $row) {
            $inviteeUserId = DB::table('lecturers')->where('id', $row->lecturer_id)->value('user_id');
            if (! $inviteeUserId) {
                continue;
            }
            $invitee = User::find($inviteeUserId);
            if (! $invitee) {
                continue;
            }
            $roleName = $roleNames[$row->member_role_id] ?? null;

            $invitee->notify(new ParticipationInvitationNotification([
                'event_key' => 'participation_invitation',
                'title' => 'Lời mời tham gia công trình',
                'message' => trim('Bạn được mời tham gia công trình ' . $activityTitle . ($ownerName ? (' bởi ' . $ownerName) : '') . '.'),
                'activity_id' => (int) $activity,
                'invitation_id' => (int) $row->id,
                'role_name' => $roleName,
                'action_route' => '/declarations/participatier',
            ]));
        }
        if (! $hasPending) {
            WorkflowNotification::notifyFacultyBoardByActivityId(
                (int) $activity,
                WorkflowNotification::makePayload(
                    'work_submitted_to_faculty',
                    'Có hồ sơ công trình mới cần duyệt',
                    trim(($ownerName ?: 'Giảng viên') . ' đã gửi công trình "' . ($activityTitle ?: 'Không rõ tiêu đề') . '" lên khoa duyệt.'),
                    '/works/facapprovals?activity_id=' . (int) $activity,
                    [
                        'activity_id' => (int) $activity,
                        'lecturer_id' => (int) $ownerLecturerId,
                    ]
                ),
                (int) ($user->id ?? 0)
            );
        }
        // Audit log (đúng nghĩa submit công trình)
        AuditLogger::log($request, [
            'action_group' => 'approval',
            'action_code' => 'WORK_REQUESTED_APPROVAL',
            'action_label' => 'Giảng viên gửi yêu cầu duyệt công trình',
            'severity' => 'important',
            'result_status' => 'success',
            'target_type' => 'research_activity',
            'target_id' => $activity,
            'target_display' => $current->title ? 'Công trình: ' . $current->title : null,
            'request_http_status' => Response::HTTP_OK,
            'changes' => [
                'activity_status_to' => $hasPending ? self::STATUS_PENDING_MEMBER_CONFIRM : self::STATUS_PENDING_FACULTY_REVIEW,
            ],
        ], $user);

        $statusCode = $hasPending
            ? self::STATUS_PENDING_MEMBER_CONFIRM
            : self::STATUS_PENDING_FACULTY_REVIEW;

        $pendingMembers = collect($pendingRows)->map(function ($row) {
            return [
                'invitation_id' => (int) $row->id,
                'lecturer_id' => (int) $row->lecturer_id,
                'lecturer_code' => $row->lecturer_code,
                'lecturer_full_name' => $row->lecturer_full_name,
                'member_role_code' => $row->member_role_code,
                'member_role_name' => $row->member_role_name,
            ];
        })->values()->all();

        return response()->json([
            'message' => $hasPending
                ? 'request recorded; waiting for member confirmations'
                : 'activity sent to faculty review',
            'data' => array_merge($this->serializeActivity($activity), [
                'status_code' => $statusCode,
            ]),
            'workflow' => [
                'status_code' => $statusCode,
                'pending_members' => $pendingMembers,
                'can_faculty_review' => ! $hasPending,
            ],
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        $detailKind = $this->detailTableByKind()[$current->kind_code] ?? null;
        $detail = $detailKind
            ? DB::table($detailKind)->where('activity_id', $activity)->first()
            : null;

        $ownerFacultyId = DB::table('lecturers as owner_l')
            ->leftJoin('departments as owner_d', 'owner_l.department_id', '=', 'owner_d.id')
            ->where('owner_l.id', (int) $current->owner_lecturer_id)
            ->value('owner_d.faculty_id');
        $ownerFacultyId = $ownerFacultyId !== null ? (int) $ownerFacultyId : null;

        $members = DB::table('research_activity_members as ram')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->leftJoin('lecturers as l', 'ram.lecturer_id', '=', 'l.id')
            ->leftJoin('departments as d', 'l.department_id', '=', 'd.id')
            ->leftJoin('faculties as f', 'd.faculty_id', '=', 'f.id')
            ->where('ram.activity_id', $activity)
            ->select([
                'ram.lecturer_id',
                'ram.member_role_id',
                'ram.contribution_share',
                'ram.hours_assigned',
                'ram.confirmation_status',
                'ram.confirmation_note',
                'ram.responded_at',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
                'd.id as department_id',
                'd.name as department_name',
                'f.id as member_faculty_id',
                'f.name as faculty_name',
            ])
            ->get()
            ->map(function ($row) use ($ownerFacultyId) {
                $memberFacultyId = $row->member_faculty_id !== null ? (int) $row->member_faculty_id : null;
                return array_merge((array) $row, [
                    'owner_faculty_id' => $ownerFacultyId,
                    'is_outside_faculty' => $ownerFacultyId !== null
                        && $memberFacultyId !== null
                        && $ownerFacultyId !== $memberFacultyId,
                ]);
            })
            ->values()
            ->all();

        $evidenceFiles = $this->fetchEvidenceFiles($activity);

        $activityPayload = $this->serializeActivityRow($current);
        $activityPayload['status_code'] = $current->status_code;
        $activityPayload['kind_code'] = $current->kind_code;

        return response()->json([
            'data' => [
                'activity' => $activityPayload,
                'detail_kind' => $detailKind,
                'detail' => $detail ? (array) $detail : null,
                'members' => $members,
                'evidence_files' => $evidenceFiles,
            ],
        ], Response::HTTP_OK);
    }

    public function listEvidenceFiles(Request $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->fetchEvidenceFiles($activity),
        ], Response::HTTP_OK);
    }

    public function previewProjectHours(ProjectHoursPreviewRequest $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = $request->validated();
        $kindId = DB::table('activity_kinds')->where('code', 'project')->value('id');
        if (! $kindId) {
            return response()->json([
                'message' => 'project kind is not configured',
                'code' => 'PROJECT_KIND_NOT_CONFIGURED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $typeId = isset($payload['type_id']) && $payload['type_id'] !== null
            ? (int) $payload['type_id']
            : null;
        $quantity = max(1, (int) ($payload['quantity'] ?? 1));

        if ($typeId === null) {
            return response()->json([
                'message' => 'type_id is required for project hour preview',
                'code' => 'PROJECT_TYPE_REQUIRED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $typeMeta = null;
        if (! $this->activityTypeMatchesKind($typeId, (int) $kindId)) {
            return response()->json([
                'message' => 'type_id does not match project kind',
                'code' => 'TYPE_KIND_MISMATCH',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $typeMeta = DB::table('activity_types')
            ->where('id', $typeId)
            ->select(['id', 'code', 'name'])
            ->first();

        $roleRows = DB::table('member_roles')
            ->select(['id', 'code', 'name'])
            ->get();
        $roleById = $roleRows->keyBy('id');
        $roleByCode = $roleRows->keyBy('code');
        $principalRoleId = optional($roleByCode->get('principal'))->id;

        $members = collect($payload['members'] ?? [])
            ->filter(fn ($member) => is_array($member))
            ->map(function ($member) {
                $lecturerId = isset($member['lecturer_id']) && $member['lecturer_id'] !== null
                    ? (int) $member['lecturer_id']
                    : null;
                $memberRoleId = isset($member['member_role_id']) && $member['member_role_id'] !== null
                    ? (int) $member['member_role_id']
                    : null;
                return [
                    'lecturer_id' => $lecturerId,
                    'member_role_id' => $memberRoleId,
                ];
            })
            ->filter(fn ($member) => $member['lecturer_id'] !== null && $member['member_role_id'] !== null)
            ->unique('lecturer_id')
            ->values();

        if (! $members->contains(fn ($member) => (int) $member['lecturer_id'] === (int) $lecturer->id)) {
            $fallbackRoleId = $principalRoleId
                ? (int) $principalRoleId
                : ($roleRows->first()?->id ? (int) $roleRows->first()->id : null);
            if ($fallbackRoleId === null) {
                return response()->json([
                    'message' => 'member roles are not configured',
                    'code' => 'MEMBER_ROLES_NOT_CONFIGURED',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $members->prepend([
                'lecturer_id' => (int) $lecturer->id,
                'member_role_id' => $fallbackRoleId,
            ]);
        }

        $members = $members->values();
        if ($members->isEmpty()) {
            return response()->json([
                'message' => 'at least one internal member is required',
                'code' => 'MEMBERS_REQUIRED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $normalizedMembers = [];
        $lecturerIds = [];
        foreach ($members as $member) {
            $memberRole = $roleById->get((int) $member['member_role_id']);
            if (! $memberRole) {
                continue;
            }

            $lecturerId = (int) $member['lecturer_id'];
            $lecturerIds[] = $lecturerId;
            $normalizedMembers[] = [
                'lecturer_id' => $lecturerId,
                'member_role_code' => (string) $memberRole->code,
                'member_role_name' => (string) $memberRole->name,
            ];
        }

        if (empty($normalizedMembers)) {
            return response()->json([
                'message' => 'cannot resolve member roles for preview',
                'code' => 'INVALID_MEMBER_ROLE',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $typeCode = $typeMeta?->code ? strtolower((string) $typeMeta->code) : null;
        if (! $this->projectHoursCalculator->supports($typeCode)) {
            return response()->json([
                'message' => 'project level is not supported for automatic hour split',
                'code' => 'PROJECT_LEVEL_UNSUPPORTED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lecturerNames = DB::table('lecturers')
            ->whereIn('id', array_values(array_unique($lecturerIds)))
            ->pluck('full_name', 'id');
        foreach ($normalizedMembers as $index => $member) {
            $lecturerId = (int) $member['lecturer_id'];
            $normalizedMembers[$index]['lecturer_full_name'] = (string) ($lecturerNames[$lecturerId] ?? ('GV #' . $lecturerId));
        }

        // Root-cause note:
        // previous preview relied on hour_rules and inherited stale project config (equal_all_members, 120h),
        // causing formula rows to show 0 while distribution showed small equal shares.
        // We now use canonical fixed project rules (bo/coso) from ProjectHoursCalculator.
        $calculated = $this->projectHoursCalculator->calculate(
            $typeCode,
            $quantity,
            $normalizedMembers,
            (int) $lecturer->id
        );
        if (! $calculated) {
            return response()->json([
                'message' => 'cannot compute project hours preview',
                'code' => 'PROJECT_HOURS_PREVIEW_FAILED',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $currentLecturerHours = collect($calculated['members'])
            ->firstWhere('lecturer_id', (int) $lecturer->id)['hours_assigned'] ?? null;

        return response()->json([
            'data' => [
                'kind_code' => 'project',
                'type_id' => $typeMeta?->id ? (int) $typeMeta->id : null,
                'type_code' => $typeCode,
                'type_name' => $typeMeta?->name,
                'rule_summary' => $calculated['rule_summary'],
                'distribution_strategy' => 'principal_fraction_others_equal',
                'level_label' => $calculated['level_label'],
                'quantity' => $quantity,
                'formula' => [
                    'leader_hours' => $calculated['leader_hours'],
                    'member_pool_hours' => $calculated['member_pool_hours'],
                    'member_pool_count' => $calculated['member_pool_count'],
                    'member_pool_each' => $calculated['member_pool_each'],
                    'rule_total_hours' => round(
                        (float) $calculated['leader_hours'] + (float) $calculated['member_pool_hours'],
                        2
                    ),
                    'total_hours_allocated' => $calculated['total_hours_allocated'],
                    'progress_multiplier_applied' => (bool) $calculated['progress_multiplier_applied'],
                    'progress_supported' => (bool) $calculated['progress_supported'],
                    'progress_note' => $calculated['progress_note'],
                ],
                'formula_rows' => $calculated['formula_rows'],
                'current_lecturer_hours' => $currentLecturerHours !== null ? (float) $currentLecturerHours : null,
                'members' => $calculated['members'],
            ],
        ], Response::HTTP_OK);
    }

    private function sendParticipationInvitationNotification(int $activityId, array $member): void
    {
        $inviteeUserId = isset($member['lecturer_user_id']) ? (int) $member['lecturer_user_id'] : 0;
        if ($inviteeUserId <= 0) {
            return;
        }

        $invitee = User::find($inviteeUserId);
        if (! $invitee) {
            return;
        }

        $activity = DB::table('research_activities as ra')
            ->leftJoin('lecturers as l', 'ra.owner_lecturer_id', '=', 'l.id')
            ->where('ra.id', $activityId)
            ->select([
                'ra.title',
                'l.full_name as owner_name',
            ])
            ->first();

        if (! $activity) {
            return;
        }

        $invitee->notify(new ParticipationInvitationNotification([
                'event_key' => 'participation_invitation',
            'title' => 'Loi moi tham gia cong trinh',
            'message' => trim('Ban duoc moi tham gia cong trinh ' . ($activity->title ?? '') . (($activity->owner_name ?? '') ? (' boi ' . $activity->owner_name) : '') . '.'),
            'activity_id' => $activityId,
            'invitation_id' => isset($member['id']) ? (int) $member['id'] : null,
            'role_name' => $member['member_role_name'] ?? null,
            'action_route' => '/declarations/participatier',
        ]));
    }

    private function resetFacultyApprovalToPending(int $activityId, $now): void
    {
        $assistantStageId = DB::table('approval_stages')->where('code', 'assistant')->value('id');
        if (! $assistantStageId) {
            return;
        }

        $assistantStageId = (int) $assistantStageId;
        $exists = DB::table('activity_approvals')
            ->where('activity_id', $activityId)
            ->where('stage_id', $assistantStageId)
            ->exists();

        if ($exists) {
            DB::table('activity_approvals')
                ->where('activity_id', $activityId)
                ->where('stage_id', $assistantStageId)
                ->update([
                    'status' => 'pending',
                    'decided_by_user_id' => null,
                    'decided_at' => null,
                    'note' => null,
                    'updated_at' => $now,
                ]);
            return;
        }

        DB::table('activity_approvals')->insert([
            'activity_id' => $activityId,
            'stage_id' => $assistantStageId,
            'status' => 'pending',
            'decided_by_user_id' => null,
            'decided_at' => null,
            'note' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function serializeActivity(int $activityId): array
    {
        $row = DB::table('research_activities')->where('id', $activityId)->first();
        if (! $row) {
            return [];
        }
        return $this->serializeActivityRow($row);
    }

    private function serializeDetail(string $table, int $activityId): array
    {
        $row = DB::table($table)->where('activity_id', $activityId)->first();
        return $row ? (array) $row : [];
    }

    private function getActivityWithMeta(int $activityId, int $lecturerId): ?object
    {
        return DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->where('ra.id', $activityId)
            ->where('ra.owner_lecturer_id', $lecturerId)
            ->select([
                'ra.*',
                'ast.code as status_code',
                'ak.code as kind_code',
            ])
            ->first();
    }

    private function getStatusId(string $code): ?int
    {
        $id = DB::table('activity_statuses')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function generateActivityCode(): string
    {
        return 'ACT-' . Str::upper(Str::uuid()->toString());
    }

    private function activityTypeMatchesKind(?int $typeId, int $kindId): bool
    {
        if (! $typeId) {
            return true;
        }

        return DB::table('activity_types')
            ->where('id', $typeId)
            ->where('kind_id', $kindId)
            ->exists();
    }

    private function isEditableStatus(?string $statusCode): bool
    {
        // Cho phép sửa khi draft / member_rejected / rejected_by_faculty
        return in_array($statusCode, [self::STATUS_DRAFT, self::STATUS_MEMBER_REJECTED, self::STATUS_REJECTED], true);
    }

    private function detailKindMap(): array
    {
        return [
            'paper_details' => 'paper',
            'book_details' => 'book',
            'project_details' => 'project',
            'conference_details' => 'conference',
        ];
    }

    private function detailTableByKind(): array
    {
        return [
            'paper' => 'paper_details',
            'book' => 'book_details',
            'project' => 'project_details',
            'conference' => 'conference_details',
        ];
    }

    private function serializeActivityRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'activity_code' => $row->activity_code,
            'owner_lecturer_id' => $row->owner_lecturer_id !== null ? (int) $row->owner_lecturer_id : null,
            'kind_id' => $row->kind_id !== null ? (int) $row->kind_id : null,
            'type_id' => $row->type_id !== null ? (int) $row->type_id : null,
            'academic_year_id' => $row->academic_year_id !== null ? (int) $row->academic_year_id : null,
            'status_id' => $row->status_id !== null ? (int) $row->status_id : null,
            'title' => $row->title,
            'abstract' => $row->abstract,
            'start_date' => $row->start_date,
            'end_date' => $row->end_date,
            'quantity' => $row->quantity,
            'submitted_at' => $row->submitted_at,
            'approved_at' => $row->approved_at,
            'total_hours_calc' => $row->total_hours_calc,
            'notes' => $row->notes,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function fetchEvidenceFiles(int $activityId): array
    {
        return DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $activityId)
            ->select([
                'ef.id',
                'ef.activity_id',
                'ef.file_type_id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.sha256',
                'ef.uploaded_by_user_id',
                'ef.uploaded_at',
                'ef.created_at',
                'ef.updated_at',
                'eft.name as file_type_name',
            ])
            ->orderByDesc('ef.uploaded_at')
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                $data['url'] = null;
                return $data;
            })
            ->all();
    }
}
