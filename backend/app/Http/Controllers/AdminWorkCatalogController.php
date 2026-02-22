<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AdminWorkCatalogController extends Controller
{
    private const JOURNAL_CLASSIFICATIONS = ['ISI', 'SCOPUS', 'OTHER'];
    private const JOURNAL_RANKS = ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'OTHER'];
    private const CONFERENCE_LEVELS = ['FACULTY', 'UNIVERSITY', 'NATIONAL', 'INTERNATIONAL'];

    // ===== WORK TYPES =====
    public function listWorkTypes(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = DB::table('work_types')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $like = '%' . $keyword . '%';
                    $sub->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->orderByDesc('updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->workTypePayload($row);
        });
    }

    public function storeWorkType(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $now = now();
        $id = DB::table('work_types')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = DB::table('work_types')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->workTypePayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateWorkType(Request $request, int $id)
    {
        $existing = DB::table('work_types')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('work_types')
            ->where('id', $id)
            ->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('work_types')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->workTypePayload($row),
        ], Response::HTTP_OK);
    }

    public function updateWorkTypeStatus(Request $request, int $id)
    {
        $existing = DB::table('work_types')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('work_types')
            ->where('id', $id)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('work_types')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->workTypePayload($row),
        ], Response::HTTP_OK);
    }

    // ===== WORK LEVELS =====
    public function listWorkLevels(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = DB::table('work_levels')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $like = '%' . $keyword . '%';
                    $sub->where('name', 'like', $like)
                        ->orWhere('notes', 'like', $like);
                });
            })
            ->orderByDesc('updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->workLevelPayload($row);
        });
    }

    public function storeWorkLevel(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $now = now();
        $id = DB::table('work_levels')->insertGetId([
            'name' => $data['name'],
            'priority' => $data['priority'],
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = DB::table('work_levels')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->workLevelPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateWorkLevel(Request $request, int $id)
    {
        $existing = DB::table('work_levels')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('work_levels')
            ->where('id', $id)
            ->update([
                'name' => $data['name'],
                'priority' => $data['priority'],
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('work_levels')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->workLevelPayload($row),
        ], Response::HTTP_OK);
    }

    public function updateWorkLevelStatus(Request $request, int $id)
    {
        $existing = DB::table('work_levels')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('work_levels')
            ->where('id', $id)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('work_levels')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->workLevelPayload($row),
        ], Response::HTTP_OK);
    }

    // ===== JOURNALS =====
    public function listJournals(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $today = Carbon::now()->toDateString();

        $latestRanking = DB::table('journal_rankings')
            ->select('journal_id', DB::raw('MAX(effective_from) as max_effective_from'))
            ->where('effective_from', '<=', $today)
            ->groupBy('journal_id');

        $query = DB::table('journals as j')
            ->leftJoinSub($latestRanking, 'lr', 'lr.journal_id', '=', 'j.id')
            ->leftJoin('journal_rankings as jr', function ($join) {
                $join->on('jr.journal_id', '=', 'j.id')
                    ->on('jr.effective_from', '=', 'lr.max_effective_from');
            })
            ->select([
                'j.*',
                'jr.rank as current_rank',
                'jr.effective_from as current_rank_effective_from',
                // có thể thêm: 'jr.note as current_rank_note' nếu muốn
            ])
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('j.name', 'like', $like)
                        ->orWhere('j.issn', 'like', $like)
                        ->orWhere('j.source_name', 'like', $like);
                });
            })
            ->orderByDesc('j.updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->journalPayload($row);
        });
    }

    public function storeJournal(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'issn'              => ['nullable', 'string', 'max:50', 'unique:journals,issn'],
            'address'           => ['nullable', 'string', 'max:255'],
            'country'           => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:255'],
            'source_name'       => ['nullable', 'string', 'max:255'],
            'point_min'         => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:2'],
            'point_max'         => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:2', 'gte:point_min'],
            'classification'    => ['required', 'string', Rule::in(self::JOURNAL_CLASSIFICATIONS)],
            'research_hours'    => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active'         => ['required', 'boolean'],
        ]);

        $now = now();

        $id = DB::table('journals')->insertGetId([
            'name'           => $data['name'],
            'issn'           => $data['issn'] ?? null,
            'address'        => $data['address'] ?? null,
            'country'        => $data['country'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'source_name'    => $data['source_name'] ?? null,
            'point_min'      => $data['point_min'] ?? null,
            'point_max'      => $data['point_max'] ?? null,
            'classification' => $data['classification'],
            'research_hours' => $data['research_hours'] ?? 0,
            'is_active'      => (bool) $data['is_active'],
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $row = $this->journalQuery()->where('j.id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data'    => $this->journalPayload($row),
        ], Response::HTTP_CREATED);
    }
    public function updateJournal(Request $request, int $id)
    {
        $existing = DB::table('journals')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'issn'              => ['nullable', 'string', 'max:50', Rule::unique('journals', 'issn')->ignore($id)],
            'address'           => ['nullable', 'string', 'max:255'],
            'country'           => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:255'],
            'source_name'       => ['nullable', 'string', 'max:255'],
            'point_min'         => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:2'],
            'point_max'         => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:2', 'gte:point_min'],
            'classification'    => ['required', 'string', Rule::in(self::JOURNAL_CLASSIFICATIONS)],
            'research_hours'    => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active'         => ['required', 'boolean'],
        ]);

        DB::table('journals')
            ->where('id', $id)
            ->update([
                'name'           => $data['name'],
                'issn'           => $data['issn'] ?? null,
                'address'        => $data['address'] ?? null,
                'country'        => $data['country'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'source_name'    => $data['source_name'] ?? null,
                'point_min'      => $data['point_min'] ?? null,
                'point_max'      => $data['point_max'] ?? null,
                'classification' => $data['classification'],
                'research_hours' => $data['research_hours'] ?? 0,
                'is_active'      => (bool) $data['is_active'],
                'updated_at'     => now(),
            ]);

        $row = $this->journalQuery()->where('j.id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data'    => $this->journalPayload($row),
        ], Response::HTTP_OK);
    }

    public function updateJournalStatus(Request $request, int $id)
    {
        $existing = DB::table('journals')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('journals')
            ->where('id', $id)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = $this->journalQuery()->where('j.id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->journalPayload($row),
        ], Response::HTTP_OK);
    }

    public function storeJournalRanking(Request $request, int $journalId)
    {
        $journal = DB::table('journals')->where('id', $journalId)->first();
        if (! $journal) {
            return response()->json(['message' => 'Journal not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'rank'          => ['required', 'string', Rule::in(self::JOURNAL_RANKS ?? ['Q1', 'Q2', 'Q3', 'Q4', 'A', 'B', 'C'])],
            'effective_from' => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:255'],
        ]);

        $now = now();

        DB::table('journal_rankings')->insert([
            'journal_id'     => $journalId,
            'rank'           => $data['rank'],
            'effective_from' => $data['effective_from'],
            'note'           => $data['note'] ?? null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // Cập nhật timestamp của journal để dễ sort / nhận biết có thay đổi ranking
        DB::table('journals')
            ->where('id', $journalId)
            ->update(['updated_at' => $now]);

        // Trả về ranking vừa tạo + thông tin journal nếu cần
        $newRanking = DB::table('journal_rankings')
            ->where('journal_id', $journalId)
            ->where('effective_from', $data['effective_from'])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Ranking created',
            'data'    => $this->journalRankingPayload($newRanking),
        ], Response::HTTP_CREATED);
    }

    // ===== CONFERENCES =====
    public function listConferences(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = DB::table('conferences')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%');
            })
            ->orderByDesc('updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->conferencePayload($row);
        });
    }

    public function storeConference(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', Rule::in(self::CONFERENCE_LEVELS)],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $now = now();
        $id = DB::table('conferences')->insertGetId([
            'name' => $data['name'],
            'level' => $data['level'],
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = DB::table('conferences')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->conferencePayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateConference(Request $request, int $id)
    {
        $existing = DB::table('conferences')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', Rule::in(self::CONFERENCE_LEVELS)],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('conferences')
            ->where('id', $id)
            ->update([
                'name' => $data['name'],
                'level' => $data['level'],
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('conferences')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->conferencePayload($row),
        ], Response::HTTP_OK);
    }

    public function updateConferenceStatus(Request $request, int $id)
    {
        $existing = DB::table('conferences')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('conferences')
            ->where('id', $id)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('conferences')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->conferencePayload($row),
        ], Response::HTTP_OK);
    }

    // ===== RESEARCH FIELDS =====
    public function listResearchFields(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = DB::table('research_fields')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $like = '%' . $keyword . '%';
                    $sub->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                });
            })
            ->orderByDesc('updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->researchFieldPayload($row);
        });
    }

    public function storeResearchField(Request $request)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:research_fields,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $code = isset($data['code']) ? trim((string) $data['code']) : null;
        if ($code === '') {
            $code = null;
        }

        $now = now();
        $id = DB::table('research_fields')->insertGetId([
            'code' => $code,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = DB::table('research_fields')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'created',
            'data' => $this->researchFieldPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateResearchField(Request $request, int $id)
    {
        $existing = DB::table('research_fields')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('research_fields', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);

        $code = isset($data['code']) ? trim((string) $data['code']) : null;
        if ($code === '') {
            $code = null;
        }

        DB::table('research_fields')
            ->where('id', $id)
            ->update([
                'code' => $code,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('research_fields')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->researchFieldPayload($row),
        ], Response::HTTP_OK);
    }

    public function updateResearchFieldStatus(Request $request, int $id)
    {
        $existing = DB::table('research_fields')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('research_fields')
            ->where('id', $id)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('research_fields')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'updated',
            'data' => $this->researchFieldPayload($row),
        ], Response::HTTP_OK);
    }

    // ===== Helpers =====
    private function resolveListParams(Request $request): array
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? $validated['q'] ?? ''));
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($validated['per_page'] ?? 12)));

        return [$keyword, $page, $perPage];
    }

    private function paginateResponse($query, int $page, int $perPage, callable $mapFn)
    {
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $items = [];
        foreach ($paginator->items() as $row) {
            $items[] = $mapFn($row);
        }

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

    private function journalQuery()
    {
        $today = Carbon::now()->toDateString();
        $latestRanking = DB::table('journal_rankings')
            ->select('journal_id', DB::raw('MAX(effective_from) as effective_from'))
            ->where('effective_from', '<=', $today)
            ->groupBy('journal_id');

        return DB::table('journals as j')
            ->leftJoinSub($latestRanking, 'lr', 'lr.journal_id', '=', 'j.id')
            ->leftJoin('journal_rankings as jr', function ($join) {
                $join->on('jr.journal_id', '=', 'j.id')
                    ->on('jr.effective_from', '=', 'lr.effective_from');
            })
            ->select([
                'j.*',
                'jr.rank as current_rank',
                'jr.effective_from as current_rank_effective_from',
            ]);
    }

    private function workTypePayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'description' => $row->description,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
        ];
    }

    private function workLevelPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'priority' => (int) $row->priority,
            'notes' => $row->notes,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
        ];
    }

    private function journalPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'address' => $row->address,
            'issn' => $row->issn,
            'classification' => $row->classification,
            'country' => $row->country,
            'notes' => $row->notes,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
            'current_rank' => $row->current_rank,
            'current_rank_effective_from' => $row->current_rank_effective_from,
        ];
    }

    private function journalRankingPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'journal_id' => (int) $row->journal_id,
            'rank' => $row->rank,
            'effective_from' => $row->effective_from,
            'note' => $row->note,
            'created_at' => $row->created_at,
        ];
    }

    private function conferencePayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'level' => $row->level,
            'notes' => $row->notes,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
        ];
    }

    private function researchFieldPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'code' => $row->code,
            'name' => $row->name,
            'description' => $row->description,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
        ];
    }
}
