<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AdminWorkCatalogController extends Controller
{
    private const JOURNAL_CLASSIFICATIONS = ['POINT_GE_2', 'POINT_GE_1', 'ISSN_ISBN', 'OTHER'];
    private const LEGACY_JOURNAL_CLASSIFICATION_MAP = [
        'HDGSNN_GE_2' => 'POINT_GE_2',
        'HDGSNN_1_2' => 'POINT_GE_2',
        'HDGSNN_GE_1' => 'POINT_GE_1',
        'ISI' => 'POINT_GE_2',
        'SCOPUS' => 'POINT_GE_1',
        'ISSN' => 'ISSN_ISBN',
        'ISBN' => 'ISSN_ISBN',
    ];
    private const CONFERENCE_LEVELS = ['NATIONAL', 'INTERNATIONAL'];
    private ?array $paperRuleHoursByTypeCode = null;

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
        $this->logCatalogAction($request, 'WORK_TYPE_CREATED', 'Truong tao loai cong trinh', 'work_type', $id, $row?->name, [
            'after' => $this->workTypePayload($row),
        ], Response::HTTP_CREATED);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mới thành công.',
            'data' => $this->workTypePayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateWorkType(Request $request, int $id)
    {
        $existing = DB::table('work_types')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'WORK_TYPE_UPDATED', 'Truong cap nhat loai cong trinh', 'work_type', $id, $row?->name, [
            'before' => [
                'name' => $existing->name,
                'description' => $existing->description,
                'is_active' => (bool) $existing->is_active,
            ],
            'after' => $this->workTypePayload($row),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->workTypePayload($row),
        ], Response::HTTP_OK);
    }

    public function updateWorkTypeStatus(Request $request, int $id)
    {
        $existing = DB::table('work_types')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'WORK_TYPE_STATUS_UPDATED', 'Truong cap nhat trang thai loai cong trinh', 'work_type', $id, $row?->name, [
            'before' => ['is_active' => (bool) $existing->is_active],
            'after' => ['is_active' => (bool) ($row->is_active ?? $data['is_active'])],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
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
        $this->logCatalogAction($request, 'WORK_LEVEL_CREATED', 'Truong tao cap cong trinh', 'work_level', $id, $row?->name, [
            'after' => $this->workLevelPayload($row),
        ], Response::HTTP_CREATED);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mới thành công.',
            'data' => $this->workLevelPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateWorkLevel(Request $request, int $id)
    {
        $existing = DB::table('work_levels')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'WORK_LEVEL_UPDATED', 'Truong cap nhat cap cong trinh', 'work_level', $id, $row?->name, [
            'before' => [
                'name' => $existing->name,
                'priority' => $existing->priority,
                'notes' => $existing->notes,
                'is_active' => (bool) $existing->is_active,
            ],
            'after' => $this->workLevelPayload($row),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->workLevelPayload($row),
        ], Response::HTTP_OK);
    }

    public function updateWorkLevelStatus(Request $request, int $id)
    {
        $existing = DB::table('work_levels')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'WORK_LEVEL_STATUS_UPDATED', 'Truong cap nhat trang thai cap cong trinh', 'work_level', $id, $row?->name, [
            'before' => ['is_active' => (bool) $existing->is_active],
            'after' => ['is_active' => (bool) ($row->is_active ?? $data['is_active'])],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->workLevelPayload($row),
        ], Response::HTTP_OK);
    }

    // ===== JOURNALS =====
    public function listJournals(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = DB::table('journals as j')
            ->select(['j.*'])
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('j.name', 'like', $like)
                        ->orWhere('j.issn', 'like', $like)
                        ->orWhere('j.source_name', 'like', $like)
                        ->orWhere('j.publisher', 'like', $like);
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
            'journal_type'      => ['nullable', 'string', 'max:100'],
            'research_field'    => ['nullable', 'string', 'max:255'],
            'website'           => ['nullable', 'string', 'max:255'],
            'address'           => ['nullable', 'string', 'max:255'],
            'country'           => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:255'],
            'source_name'       => ['nullable', 'string', 'max:255'],
            'publisher'         => ['nullable', 'string', 'max:255'],
            'point'             => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:0,2'],
            'classification'    => ['nullable', 'string', 'max:30'],
            'research_hours'    => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active'         => ['required', 'boolean'],
        ]);

        $derived = $this->deriveJournalClassificationAndHours($data);

        $now = now();

        $id = DB::table('journals')->insertGetId([
            'name'           => $data['name'],
            'issn'           => $data['issn'] ?? null,
            'journal_type'   => $data['journal_type'] ?? null,
            'research_field' => $data['research_field'] ?? null,
            'website'        => $data['website'] ?? null,
            'address'        => $data['address'] ?? null,
            'country'        => $data['country'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'source_name'    => $data['source_name'] ?? null,
            'publisher'      => $data['publisher'] ?? null,
            'point'          => $data['point'] ?? null,
            'classification' => $derived['classification'],
            'research_hours' => $derived['research_hours'],
            'is_active'      => (bool) $data['is_active'],
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $row = $this->journalQuery()->where('j.id', $id)->first();
        $this->logCatalogAction($request, 'JOURNAL_CREATED', 'Truong tao tap chi', 'journal', $id, $row?->name, [
            'after' => $this->journalPayload($row),
        ], Response::HTTP_CREATED);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mới thành công.',
            'data'    => $this->journalPayload($row),
        ], Response::HTTP_CREATED);
    }
    public function updateJournal(Request $request, int $id)
    {
        $existing = DB::table('journals')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy tạp chí.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'issn'              => ['nullable', 'string', 'max:50', Rule::unique('journals', 'issn')->ignore($id)],
            'journal_type'      => ['nullable', 'string', 'max:100'],
            'research_field'    => ['nullable', 'string', 'max:255'],
            'website'           => ['nullable', 'string', 'max:255'],
            'address'           => ['nullable', 'string', 'max:255'],
            'country'           => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:255'],
            'source_name'       => ['nullable', 'string', 'max:255'],
            'publisher'         => ['nullable', 'string', 'max:255'],
            'point'             => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:0,2'],
            'classification'    => ['nullable', 'string', 'max:30'],
            'research_hours'    => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active'         => ['required', 'boolean'],
        ]);

        $derived = $this->deriveJournalClassificationAndHours($data);

        DB::table('journals')
            ->where('id', $id)
            ->update([
                'name'           => $data['name'],
                'issn'           => $data['issn'] ?? null,
                'journal_type'   => $data['journal_type'] ?? null,
                'research_field' => $data['research_field'] ?? null,
                'website'        => $data['website'] ?? null,
                'address'        => $data['address'] ?? null,
                'country'        => $data['country'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'source_name'    => $data['source_name'] ?? null,
                'publisher'      => $data['publisher'] ?? null,
                'point'          => $data['point'] ?? null,
                'classification' => $derived['classification'],
                'research_hours' => $derived['research_hours'],
                'is_active'      => (bool) $data['is_active'],
                'updated_at'     => now(),
            ]);

        $row = $this->journalQuery()->where('j.id', $id)->first();
        $this->logCatalogAction($request, 'JOURNAL_UPDATED', 'Truong cap nhat tap chi', 'journal', $id, $row?->name, [
            'before' => [
                'name' => $existing->name,
                'issn' => $existing->issn,
                'classification' => $existing->classification,
                'research_hours' => $existing->research_hours,
                'is_active' => (bool) $existing->is_active,
            ],
            'after' => $this->journalPayload($row),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data'    => $this->journalPayload($row),
        ], Response::HTTP_OK);
    }

    public function updateJournalStatus(Request $request, int $id)
    {
        $existing = DB::table('journals')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'JOURNAL_STATUS_UPDATED', 'Truong cap nhat trang thai tap chi', 'journal', $id, $row?->name, [
            'before' => ['is_active' => (bool) $existing->is_active],
            'after' => ['is_active' => (bool) ($row->is_active ?? $data['is_active'])],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->journalPayload($row),
        ], Response::HTTP_OK);
    }

    // ===== CONFERENCES =====
    public function listConferences(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = DB::table('conferences')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('organization', 'like', $like)
                        ->orWhere('research_field', 'like', $like)
                        ->orWhere('isbn', 'like', $like);
                });
            })
            ->orderByDesc('updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->conferencePayload($row);
        });
    }

    public function storeConference(Request $request)
    {
        $isbnRequired = Rule::requiredIf(fn() => $request->boolean('has_isbn'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', Rule::in(self::CONFERENCE_LEVELS)],
            'research_field' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'organization' => ['nullable', 'string', 'max:255'],
            'has_proceedings' => ['nullable', 'boolean'],
            'has_isbn' => ['nullable', 'boolean'],
            'isbn' => [$isbnRequired, 'nullable', 'string', 'max:50'],
            'point' => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $normalized = $this->normalizeConferencePayload($data);

        $now = now();
        $id = DB::table('conferences')->insertGetId([
            'name' => $normalized['name'],
            'level' => $data['level'],
            'research_field' => $normalized['research_field'],
            'year' => $normalized['year'],
            'organization' => $normalized['organization'],
            'has_proceedings' => $normalized['has_proceedings'],
            'has_isbn' => $normalized['has_isbn'],
            'isbn' => $normalized['isbn'],
            'point' => $normalized['point'],
            'notes' => $normalized['notes'],
            'is_active' => (bool) $data['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = DB::table('conferences')->where('id', $id)->first();
        $this->logCatalogAction($request, 'CONFERENCE_CREATED', 'Truong tao hoi nghi', 'conference', $id, $row?->name, [
            'after' => $this->conferencePayload($row),
        ], Response::HTTP_CREATED);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mới thành công.',
            'data' => $this->conferencePayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateConference(Request $request, int $id)
    {
        $existing = DB::table('conferences')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
        }

        $isbnRequired = Rule::requiredIf(fn() => $request->boolean('has_isbn'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', Rule::in(self::CONFERENCE_LEVELS)],
            'research_field' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'organization' => ['nullable', 'string', 'max:255'],
            'has_proceedings' => ['nullable', 'boolean'],
            'has_isbn' => ['nullable', 'boolean'],
            'isbn' => [$isbnRequired, 'nullable', 'string', 'max:50'],
            'point' => ['nullable', 'numeric', 'min:0', 'max:99.99', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $normalized = $this->normalizeConferencePayload($data);

        DB::table('conferences')
            ->where('id', $id)
            ->update([
                'name' => $normalized['name'],
                'level' => $data['level'],
                'research_field' => $normalized['research_field'],
                'year' => $normalized['year'],
                'organization' => $normalized['organization'],
                'has_proceedings' => $normalized['has_proceedings'],
                'has_isbn' => $normalized['has_isbn'],
                'isbn' => $normalized['isbn'],
                'point' => $normalized['point'],
                'notes' => $normalized['notes'],
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('conferences')->where('id', $id)->first();
        $this->logCatalogAction($request, 'CONFERENCE_UPDATED', 'Truong cap nhat hoi nghi', 'conference', $id, $row?->name, [
            'before' => [
                'name' => $existing->name,
                'level' => $existing->level,
                'isbn' => $existing->isbn,
                'is_active' => (bool) $existing->is_active,
            ],
            'after' => $this->conferencePayload($row),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->conferencePayload($row),
        ], Response::HTTP_OK);
    }

    public function updateConferenceStatus(Request $request, int $id)
    {
        $existing = DB::table('conferences')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'CONFERENCE_STATUS_UPDATED', 'Truong cap nhat trang thai hoi nghi', 'conference', $id, $row?->name, [
            'before' => ['is_active' => (bool) $existing->is_active],
            'after' => ['is_active' => (bool) ($row->is_active ?? $data['is_active'])],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->conferencePayload($row),
        ], Response::HTTP_OK);
    }

    public function listJournalSuggestions(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = $this->suggestionQueryByType('journal')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q->where('s.source_name', 'like', $like);
            });

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->workCatalogSuggestionPayload($row);
        });
    }

    public function approveJournalSuggestion(Request $request, int $id)
    {
        $suggestion = $this->findPendingSuggestion($id, 'journal');
        if (! $suggestion) {
            return response()->json(['message' => 'Không tìm thấy đề xuất tạp chí đang chờ duyệt.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = $this->decodeSuggestionPayload($suggestion->payload ?? null);
        $name = trim((string) ($payload['name'] ?? $suggestion->source_name ?? ''));
        if ($name === '') {
            return response()->json(['message' => 'Đề xuất không có tên tạp chí hợp lệ.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $issn = $this->normalizeNullableTrimmedString($payload['issn'] ?? null);
        if ($issn !== null) {
            $issn = strtoupper(preg_replace('/\s+/', '', $issn));
        }

        $journalType = $this->normalizeNullableTrimmedString($payload['journal_type'] ?? null);
        $researchField = $this->normalizeNullableTrimmedString($payload['research_field'] ?? null);
        $website = $this->normalizeNullableTrimmedString($payload['website'] ?? null);
        $address = $this->normalizeNullableTrimmedString($payload['address'] ?? null);
        $country = $this->normalizeNullableTrimmedString($payload['country'] ?? null);
        $notes = $this->normalizeNullableTrimmedString($payload['notes'] ?? null);
        $sourceName = $this->normalizeNullableTrimmedString($payload['source_name'] ?? null);
        $publisher = $this->normalizeNullableTrimmedString($payload['publisher'] ?? null);
        $point = $this->toNullableFloat($payload['point'] ?? null);
        if ($point !== null) {
            $point = round($point, 2);
        }

        $derived = $this->deriveJournalClassificationAndHours([
            'issn' => $issn,
            'point' => $point,
        ]);

        $journalId = DB::transaction(function () use ($suggestion, $validated, $request, $name, $issn, $journalType, $researchField, $website, $address, $country, $notes, $sourceName, $publisher, $point, $derived) {
            $existingQuery = DB::table('journals');
            if ($issn !== null) {
                $existingQuery->where('issn', $issn);
            } else {
                $existingQuery->whereRaw('LOWER(name) = ?', [strtolower($name)]);
            }

            $existingJournalId = $existingQuery->value('id');

            if ($existingJournalId) {
                $journalId = (int) $existingJournalId;
            } else {
                $now = now();
                $journalId = (int) DB::table('journals')->insertGetId([
                    'name' => $name,
                    'issn' => $issn,
                    'journal_type' => $journalType,
                    'research_field' => $researchField,
                    'website' => $website,
                    'address' => $address,
                    'country' => $country,
                    'notes' => $notes,
                    'source_name' => $sourceName,
                    'publisher' => $publisher,
                    'point' => $point,
                    'classification' => $derived['classification'],
                    'research_hours' => $derived['research_hours'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('work_catalog_suggestions')
                ->where('id', (int) $suggestion->id)
                ->update([
                    'status' => 'approved',
                    'resolved_catalog_id' => $journalId,
                    'reviewed_by_user_id' => $request->user()?->id,
                    'reviewed_at' => now(),
                    'review_note' => $validated['review_note'] ?? null,
                    'updated_at' => now(),
                ]);

            DB::table('paper_details')
                ->where('activity_id', (int) $suggestion->activity_id)
                ->update([
                    'journal_catalog_id' => $journalId,
                    'updated_at' => now(),
                ]);

            return $journalId;
        });

        $updatedSuggestion = DB::table('work_catalog_suggestions as s')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.activity_id',
                's.suggestion_type',
                's.source_name',
                's.status',
                's.submitted_by_lecturer_id',
                's.submitted_by_user_id',
                's.reviewed_by_user_id',
                's.reviewed_at',
                's.review_note',
                's.resolved_catalog_id',
                's.payload',
                's.created_at',
                's.updated_at',
            ])
            ->first();

        $journal = $this->journalQuery()->where('j.id', $journalId)->first();
        $this->logCatalogAction($request, 'JOURNAL_SUGGESTION_APPROVED', 'Truong duyet de xuat tap chi', 'work_catalog_suggestion', $id, $updatedSuggestion?->source_name, [
            'suggestion_type' => 'journal',
            'review_note' => $validated['review_note'] ?? null,
            'resolved_catalog_id' => $journalId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt đề xuất tạp chí và cập nhật danh mục.',
            'data' => [
                'suggestion' => $updatedSuggestion ? $this->workCatalogSuggestionPayload($updatedSuggestion) : null,
                'catalog' => $journal ? $this->journalPayload($journal) : null,
            ],
        ], Response::HTTP_OK);
    }

    public function rejectJournalSuggestion(Request $request, int $id)
    {
        $suggestion = $this->findPendingSuggestion($id, 'journal');
        if (! $suggestion) {
            return response()->json(['message' => 'Không tìm thấy đề xuất tạp chí đang chờ duyệt.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('work_catalog_suggestions')
            ->where('id', (int) $suggestion->id)
            ->update([
                'status' => 'rejected',
                'resolved_catalog_id' => null,
                'reviewed_by_user_id' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
                'updated_at' => now(),
            ]);

        $updatedSuggestion = DB::table('work_catalog_suggestions as s')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.activity_id',
                's.suggestion_type',
                's.source_name',
                's.status',
                's.submitted_by_lecturer_id',
                's.submitted_by_user_id',
                's.reviewed_by_user_id',
                's.reviewed_at',
                's.review_note',
                's.resolved_catalog_id',
                's.payload',
                's.created_at',
                's.updated_at',
            ])
            ->first();
        $this->logCatalogAction($request, 'JOURNAL_SUGGESTION_REJECTED', 'Truong tu choi de xuat tap chi', 'work_catalog_suggestion', $id, $updatedSuggestion?->source_name, [
            'suggestion_type' => 'journal',
            'review_note' => $validated['review_note'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối đề xuất tạp chí.',
            'data' => [
                'suggestion' => $updatedSuggestion ? $this->workCatalogSuggestionPayload($updatedSuggestion) : null,
                'catalog' => null,
            ],
        ], Response::HTTP_OK);
    }

    public function listConferenceSuggestions(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = $this->suggestionQueryByType('conference')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q->where('s.source_name', 'like', $like);
            });

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->workCatalogSuggestionPayload($row);
        });
    }

    public function approveConferenceSuggestion(Request $request, int $id)
    {
        $suggestion = $this->findPendingSuggestion($id, 'conference');
        if (! $suggestion) {
            return response()->json(['message' => 'Không tìm thấy đề xuất hội nghị đang chờ duyệt.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = $this->decodeSuggestionPayload($suggestion->payload ?? null);
        $name = trim((string) ($payload['name'] ?? $suggestion->source_name ?? ''));
        if ($name === '') {
            return response()->json(['message' => 'Đề xuất không có tên hội nghị hợp lệ.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $normalized = $this->normalizeConferencePayload([
            'name' => $name,
            'research_field' => $payload['research_field'] ?? null,
            'year' => $payload['year'] ?? null,
            'organization' => $payload['organization'] ?? null,
            'has_proceedings' => (bool) ($payload['has_proceedings'] ?? false),
            'has_isbn' => (bool) ($payload['has_isbn'] ?? false),
            'isbn' => $payload['isbn'] ?? null,
            'point' => $payload['point'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $levelRaw = strtoupper(trim((string) ($payload['level'] ?? 'NATIONAL')));
        $level = in_array($levelRaw, self::CONFERENCE_LEVELS, true) ? $levelRaw : 'NATIONAL';

        $conferenceId = DB::transaction(function () use ($suggestion, $validated, $request, $normalized, $level) {
            $existingQuery = DB::table('conferences')
                ->whereRaw('LOWER(name) = ?', [strtolower($normalized['name'])])
                ->where('level', $level);

            if ($normalized['organization'] !== null) {
                $existingQuery->whereRaw("LOWER(COALESCE(organization, '')) = ?", [strtolower($normalized['organization'])]);
            }

            $existingConferenceId = $existingQuery->value('id');

            if ($existingConferenceId) {
                $conferenceId = (int) $existingConferenceId;
            } else {
                $now = now();
                $conferenceId = (int) DB::table('conferences')->insertGetId([
                    'name' => $normalized['name'],
                    'level' => $level,
                    'research_field' => $normalized['research_field'],
                    'year' => $normalized['year'],
                    'organization' => $normalized['organization'],
                    'has_proceedings' => $normalized['has_proceedings'],
                    'has_isbn' => $normalized['has_isbn'],
                    'isbn' => $normalized['isbn'],
                    'point' => $normalized['point'],
                    'notes' => $normalized['notes'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('work_catalog_suggestions')
                ->where('id', (int) $suggestion->id)
                ->update([
                    'status' => 'approved',
                    'resolved_catalog_id' => $conferenceId,
                    'reviewed_by_user_id' => $request->user()?->id,
                    'reviewed_at' => now(),
                    'review_note' => $validated['review_note'] ?? null,
                    'updated_at' => now(),
                ]);

            DB::table('paper_details')
                ->where('activity_id', (int) $suggestion->activity_id)
                ->update([
                    'conference_catalog_id' => $conferenceId,
                    'updated_at' => now(),
                ]);

            return $conferenceId;
        });

        $updatedSuggestion = DB::table('work_catalog_suggestions as s')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.activity_id',
                's.suggestion_type',
                's.source_name',
                's.status',
                's.submitted_by_lecturer_id',
                's.submitted_by_user_id',
                's.reviewed_by_user_id',
                's.reviewed_at',
                's.review_note',
                's.resolved_catalog_id',
                's.payload',
                's.created_at',
                's.updated_at',
            ])
            ->first();

        $conference = DB::table('conferences')->where('id', $conferenceId)->first();
        $this->logCatalogAction($request, 'CONFERENCE_SUGGESTION_APPROVED', 'Truong duyet de xuat hoi nghi', 'work_catalog_suggestion', $id, $updatedSuggestion?->source_name, [
            'suggestion_type' => 'conference',
            'review_note' => $validated['review_note'] ?? null,
            'resolved_catalog_id' => $conferenceId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt đề xuất hội nghị và cập nhật danh mục.',
            'data' => [
                'suggestion' => $updatedSuggestion ? $this->workCatalogSuggestionPayload($updatedSuggestion) : null,
                'catalog' => $conference ? $this->conferencePayload($conference) : null,
            ],
        ], Response::HTTP_OK);
    }

    public function rejectConferenceSuggestion(Request $request, int $id)
    {
        $suggestion = $this->findPendingSuggestion($id, 'conference');
        if (! $suggestion) {
            return response()->json(['message' => 'Không tìm thấy đề xuất hội nghị đang chờ duyệt.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('work_catalog_suggestions')
            ->where('id', (int) $suggestion->id)
            ->update([
                'status' => 'rejected',
                'resolved_catalog_id' => null,
                'reviewed_by_user_id' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
                'updated_at' => now(),
            ]);

        $updatedSuggestion = DB::table('work_catalog_suggestions as s')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.activity_id',
                's.suggestion_type',
                's.source_name',
                's.status',
                's.submitted_by_lecturer_id',
                's.submitted_by_user_id',
                's.reviewed_by_user_id',
                's.reviewed_at',
                's.review_note',
                's.resolved_catalog_id',
                's.payload',
                's.created_at',
                's.updated_at',
            ])
            ->first();
        $this->logCatalogAction($request, 'CONFERENCE_SUGGESTION_REJECTED', 'Truong tu choi de xuat hoi nghi', 'work_catalog_suggestion', $id, $updatedSuggestion?->source_name, [
            'suggestion_type' => 'conference',
            'review_note' => $validated['review_note'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối đề xuất hội nghị.',
            'data' => [
                'suggestion' => $updatedSuggestion ? $this->workCatalogSuggestionPayload($updatedSuggestion) : null,
                'catalog' => null,
            ],
        ], Response::HTTP_OK);
    }

    public function listPublisherSuggestions(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = $this->suggestionQueryByType('publisher')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $like = '%' . $keyword . '%';
                $q->where('s.source_name', 'like', $like);
            });

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->workCatalogSuggestionPayload($row);
        });
    }

    public function approvePublisherSuggestion(Request $request, int $id)
    {
        $suggestion = $this->findPendingSuggestion($id, 'publisher');
        if (! $suggestion) {
            return response()->json(['message' => 'Không tìm thấy đề xuất nhà xuất bản đang chờ duyệt.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = $this->decodeSuggestionPayload($suggestion->payload ?? null);
        $name = trim((string) ($payload['name'] ?? $suggestion->source_name ?? ''));
        if ($name === '') {
            return response()->json(['message' => 'Đề xuất không có tên nhà xuất bản hợp lệ.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $code = $this->normalizeNullableTrimmedString($payload['code'] ?? null);
        $address = $this->normalizeNullableTrimmedString($payload['address'] ?? null);
        $phone = $this->normalizeNullableTrimmedString($payload['phone'] ?? null);
        $email = $this->normalizeNullableTrimmedString($payload['email'] ?? null);
        $website = $this->normalizeNullableTrimmedString($payload['website'] ?? null);

        if ($code !== null) {
            $code = strtoupper($code);
            if (mb_strlen($code) > 50) {
                $code = mb_substr($code, 0, 50);
            }
        }

        if ($address !== null && mb_strlen($address) > 255) {
            $address = mb_substr($address, 0, 255);
        }
        if ($phone !== null && mb_strlen($phone) > 50) {
            $phone = mb_substr($phone, 0, 50);
        }
        if ($email !== null && mb_strlen($email) > 100) {
            $email = mb_substr($email, 0, 100);
        }
        if ($website !== null && mb_strlen($website) > 255) {
            $website = mb_substr($website, 0, 255);
        }

        $publisherId = DB::transaction(function () use ($suggestion, $validated, $request, $name, $code, $address, $phone, $email, $website) {
            $existingQuery = DB::table('publishers');

            if ($code !== null) {
                $existingQuery->where('code', $code);
            } else {
                $existingQuery->whereRaw('LOWER(name) = ?', [Str::lower($name)]);
            }

            $existingPublisherId = $existingQuery->value('id');

            if ($existingPublisherId) {
                $publisherId = (int) $existingPublisherId;
            } else {
                $now = now();
                $finalCode = $code ?? $this->generateUniquePublisherCode($name);

                $publisherId = (int) DB::table('publishers')->insertGetId([
                    'name' => $name,
                    'code' => $finalCode,
                    'address' => $address,
                    'phone' => $phone,
                    'email' => $email,
                    'website' => $website,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('work_catalog_suggestions')
                ->where('id', (int) $suggestion->id)
                ->update([
                    'status' => 'approved',
                    'resolved_catalog_id' => $publisherId,
                    'reviewed_by_user_id' => $request->user()?->id,
                    'reviewed_at' => now(),
                    'review_note' => $validated['review_note'] ?? null,
                    'updated_at' => now(),
                ]);

            DB::table('book_details')
                ->where('activity_id', (int) $suggestion->activity_id)
                ->update([
                    'publisher' => $name,
                    'updated_at' => now(),
                ]);

            return $publisherId;
        });

        $updatedSuggestion = DB::table('work_catalog_suggestions as s')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.activity_id',
                's.suggestion_type',
                's.source_name',
                's.status',
                's.submitted_by_lecturer_id',
                's.submitted_by_user_id',
                's.reviewed_by_user_id',
                's.reviewed_at',
                's.review_note',
                's.resolved_catalog_id',
                's.payload',
                's.created_at',
                's.updated_at',
            ])
            ->first();

        $publisher = DB::table('publishers')->where('id', $publisherId)->first();
        $this->logCatalogAction($request, 'PUBLISHER_SUGGESTION_APPROVED', 'Truong duyet de xuat nha xuat ban', 'work_catalog_suggestion', $id, $updatedSuggestion?->source_name, [
            'suggestion_type' => 'publisher',
            'review_note' => $validated['review_note'] ?? null,
            'resolved_catalog_id' => $publisherId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt đề xuất nhà xuất bản và cập nhật danh mục.',
            'data' => [
                'suggestion' => $updatedSuggestion ? $this->workCatalogSuggestionPayload($updatedSuggestion) : null,
                'catalog' => $publisher ? $this->publisherPayload($publisher) : null,
            ],
        ], Response::HTTP_OK);
    }

    public function rejectPublisherSuggestion(Request $request, int $id)
    {
        $suggestion = $this->findPendingSuggestion($id, 'publisher');
        if (! $suggestion) {
            return response()->json(['message' => 'Không tìm thấy đề xuất nhà xuất bản đang chờ duyệt.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('work_catalog_suggestions')
            ->where('id', (int) $suggestion->id)
            ->update([
                'status' => 'rejected',
                'resolved_catalog_id' => null,
                'reviewed_by_user_id' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
                'updated_at' => now(),
            ]);

        $updatedSuggestion = DB::table('work_catalog_suggestions as s')
            ->where('s.id', $id)
            ->select([
                's.id',
                's.activity_id',
                's.suggestion_type',
                's.source_name',
                's.status',
                's.submitted_by_lecturer_id',
                's.submitted_by_user_id',
                's.reviewed_by_user_id',
                's.reviewed_at',
                's.review_note',
                's.resolved_catalog_id',
                's.payload',
                's.created_at',
                's.updated_at',
            ])
            ->first();
        $this->logCatalogAction($request, 'PUBLISHER_SUGGESTION_REJECTED', 'Truong tu choi de xuat nha xuat ban', 'work_catalog_suggestion', $id, $updatedSuggestion?->source_name, [
            'suggestion_type' => 'publisher',
            'review_note' => $validated['review_note'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối đề xuất nhà xuất bản.',
            'data' => [
                'suggestion' => $updatedSuggestion ? $this->workCatalogSuggestionPayload($updatedSuggestion) : null,
                'catalog' => null,
            ],
        ], Response::HTTP_OK);
    }

    // ===== PUBLISHERS =====
    public function listPublishers(Request $request)
    {
        [$keyword, $page, $perPage] = $this->resolveListParams($request);

        $query = DB::table('publishers')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $like = '%' . $keyword . '%';
                    $sub->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('website', 'like', $like);
                });
            })
            ->orderByDesc('updated_at');

        return $this->paginateResponse($query, $page, $perPage, function ($row) {
            return $this->publisherPayload($row);
        });
    }

    public function storePublisher(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:publishers,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $now = now();
        $id = DB::table('publishers')->insertGetId([
            'name' => trim((string) $data['name']),
            'code' => strtoupper(trim((string) $data['code'])),
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = DB::table('publishers')->where('id', $id)->first();
        $this->logCatalogAction($request, 'PUBLISHER_CREATED', 'Truong tao nha xuat ban', 'publisher', $id, $row?->name, [
            'after' => $this->publisherPayload($row),
        ], Response::HTTP_CREATED);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mới thành công.',
            'data' => $this->publisherPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updatePublisher(Request $request, int $id)
    {
        $existing = DB::table('publishers')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy nhà xuất bản.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('publishers', 'code')->ignore($id)],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('publishers')
            ->where('id', $id)
            ->update([
                'name' => trim((string) $data['name']),
                'code' => strtoupper(trim((string) $data['code'])),
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('publishers')->where('id', $id)->first();
        $this->logCatalogAction($request, 'PUBLISHER_UPDATED', 'Truong cap nhat nha xuat ban', 'publisher', $id, $row?->name, [
            'before' => [
                'name' => $existing->name,
                'code' => $existing->code,
                'email' => $existing->email,
                'is_active' => (bool) $existing->is_active,
            ],
            'after' => $this->publisherPayload($row),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->publisherPayload($row),
        ], Response::HTTP_OK);
    }

    public function updatePublisherStatus(Request $request, int $id)
    {
        $existing = DB::table('publishers')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::table('publishers')
            ->where('id', $id)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

        $row = DB::table('publishers')->where('id', $id)->first();
        $this->logCatalogAction($request, 'PUBLISHER_STATUS_UPDATED', 'Truong cap nhat trang thai nha xuat ban', 'publisher', $id, $row?->name, [
            'before' => ['is_active' => (bool) $existing->is_active],
            'after' => ['is_active' => (bool) ($row->is_active ?? $data['is_active'])],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->publisherPayload($row),
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
        $this->logCatalogAction($request, 'RESEARCH_FIELD_CREATED', 'Truong tao linh vuc nghien cuu', 'research_field', $id, $row?->name, [
            'after' => $this->researchFieldPayload($row),
        ], Response::HTTP_CREATED);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mới thành công.',
            'data' => $this->researchFieldPayload($row),
        ], Response::HTTP_CREATED);
    }

    public function updateResearchField(Request $request, int $id)
    {
        $existing = DB::table('research_fields')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'RESEARCH_FIELD_UPDATED', 'Truong cap nhat linh vuc nghien cuu', 'research_field', $id, $row?->name, [
            'before' => [
                'code' => $existing->code,
                'name' => $existing->name,
                'description' => $existing->description,
                'is_active' => (bool) $existing->is_active,
            ],
            'after' => $this->researchFieldPayload($row),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->researchFieldPayload($row),
        ], Response::HTTP_OK);
    }

    public function updateResearchFieldStatus(Request $request, int $id)
    {
        $existing = DB::table('research_fields')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Không tìm thấy bản ghi.'], Response::HTTP_NOT_FOUND);
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
        $this->logCatalogAction($request, 'RESEARCH_FIELD_STATUS_UPDATED', 'Truong cap nhat trang thai linh vuc nghien cuu', 'research_field', $id, $row?->name, [
            'before' => ['is_active' => (bool) $existing->is_active],
            'after' => ['is_active' => (bool) ($row->is_active ?? $data['is_active'])],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.',
            'data' => $this->researchFieldPayload($row),
        ], Response::HTTP_OK);
    }

    private function logCatalogAction(
        Request $request,
        string $actionCode,
        string $actionLabel,
        string $targetType,
        $targetId,
        ?string $targetDisplay,
        array $changes = [],
        int $status = Response::HTTP_OK
    ): void {
        AuditLogger::log($request, [
            'action_group' => 'config',
            'action_code' => $actionCode,
            'action_label' => $actionLabel,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_display' => $targetDisplay,
            'request_http_status' => $status,
            'changes' => $changes,
        ], $request->user());
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
            'message' => 'Thành công.',
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
        return DB::table('journals as j')
            ->select(['j.*']);
    }

    private function deriveJournalClassificationAndHours(array $data): array
    {
        $point = $this->toNullableFloat($data['point'] ?? null);
        $issn = trim((string) ($data['issn'] ?? ''));
        $providedClassification = $this->normalizeJournalClassification($data['classification'] ?? null);
        $providedHours = isset($data['research_hours']) ? (int) $data['research_hours'] : null;

        $classification = 'OTHER';

        if ($point !== null && $point > 1) {
            $classification = 'POINT_GE_2';
        } elseif ($point !== null && $point > 0 && $point <= 1) {
            $classification = 'POINT_GE_1';
        } elseif ($issn !== '') {
            $classification = 'ISSN_ISBN';
        }

        if ($providedClassification !== null) {
            $classification = $providedClassification;
        }

        $hours = $this->defaultJournalHoursByClassification($classification);

        if ($providedHours !== null) {
            $hours = max(0, $providedHours);
        } else {
            $resolvedHours = $this->resolveJournalHoursFromRules($classification);
            if ($resolvedHours !== null) {
                $hours = $resolvedHours;
            }
        }

        return [
            'classification' => $classification,
            'research_hours' => $hours,
        ];
    }

    private function defaultJournalHoursByClassification(string $classification): int
    {
        return match ($classification) {
            'POINT_GE_2' => 900,
            'POINT_GE_1' => 600,
            'ISSN_ISBN' => 300,
            default => 0,
        };
    }

    private function resolveJournalHoursFromRules(string $classification): ?int
    {
        $typeCodesByClassification = [
            'POINT_GE_2' => ['hdgsnn_900'],
            'POINT_GE_1' => ['hdgsnn_600'],
            'ISSN_ISBN' => ['hdgsnn_300'],
        ];

        $targetTypeCodes = $typeCodesByClassification[$classification] ?? null;
        if (! is_array($targetTypeCodes) || $targetTypeCodes === []) {
            return null;
        }

        $rulesByTypeCode = $this->loadPaperRuleHoursByTypeCode();
        foreach ($targetTypeCodes as $typeCode) {
            if (isset($rulesByTypeCode[$typeCode])) {
                return max(0, (int) round((float) $rulesByTypeCode[$typeCode]));
            }
        }

        return null;
    }

    private function loadPaperRuleHoursByTypeCode(): array
    {
        if (is_array($this->paperRuleHoursByTypeCode)) {
            return $this->paperRuleHoursByTypeCode;
        }

        $today = Carbon::now()->toDateString();

        $rows = DB::table('hour_rules as hr')
            ->join('activity_kinds as ak', 'ak.id', '=', 'hr.kind_id')
            ->join('activity_types as at', 'at.id', '=', 'hr.type_id')
            ->whereRaw('LOWER(ak.code) = ?', ['paper'])
            ->where('hr.is_active', 1)
            ->whereNotNull('hr.type_id')
            ->whereDate('hr.effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('hr.effective_to')
                    ->orWhereDate('hr.effective_to', '>=', $today);
            })
            ->orderByRaw('LOWER(at.code) asc')
            ->orderByDesc('hr.effective_from')
            ->orderByDesc('hr.version')
            ->orderByDesc('hr.id')
            ->get([
                DB::raw('LOWER(at.code) as type_code'),
                'hr.hours_total_per_activity',
                'hr.hours_per_occurrence',
            ]);

        $byTypeCode = [];
        foreach ($rows as $row) {
            $typeCode = (string) ($row->type_code ?? '');
            if ($typeCode === '' || isset($byTypeCode[$typeCode])) {
                continue;
            }

            $hours = $row->hours_total_per_activity ?? $row->hours_per_occurrence;
            if ($hours === null) {
                continue;
            }

            $byTypeCode[$typeCode] = (float) $hours;
        }

        $this->paperRuleHoursByTypeCode = $byTypeCode;

        return $this->paperRuleHoursByTypeCode;
    }

    private function normalizeJournalClassification(?string $classification): ?string
    {
        if ($classification === null) {
            return null;
        }

        $normalized = strtoupper(trim($classification));
        if ($normalized === '') {
            return null;
        }

        if (isset(self::LEGACY_JOURNAL_CLASSIFICATION_MAP[$normalized])) {
            return self::LEGACY_JOURNAL_CLASSIFICATION_MAP[$normalized];
        }

        return in_array($normalized, self::JOURNAL_CLASSIFICATIONS, true) ? $normalized : null;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
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
        $derived = $this->deriveJournalClassificationAndHours([
            'issn' => $row->issn,
            'point' => $row->point,
            'classification' => $row->classification,
            'research_hours' => $row->research_hours,
        ]);

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'journal_type' => $row->journal_type,
            'research_field' => $row->research_field,
            'website' => $row->website,
            'address' => $row->address,
            'issn' => $row->issn,
            'source_name' => $row->source_name,
            'publisher' => $row->publisher,
            'point' => $row->point !== null ? (float) $row->point : null,
            'classification' => $derived['classification'],
            'research_hours' => $derived['research_hours'],
            'country' => $row->country,
            'notes' => $row->notes,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
            'current_rank' => null,
            'current_rank_effective_from' => null,
        ];
    }

    private function conferencePayload($row): array
    {
        $researchHours = $this->deriveConferenceResearchHoursFromPoint($row->point ?? null);

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'level' => $row->level,
            'research_field' => $row->research_field,
            'year' => $row->year !== null ? (int) $row->year : null,
            'organization' => $row->organization,
            'has_proceedings' => (bool) ($row->has_proceedings ?? false),
            'has_isbn' => (bool) ($row->has_isbn ?? false),
            'isbn' => $row->isbn,
            'point' => $row->point !== null ? (float) $row->point : null,
            'research_hours' => $researchHours,
            'notes' => $row->notes,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
        ];
    }

    private function deriveConferenceResearchHoursFromPoint(mixed $point): ?int
    {
        $value = $this->toNullableFloat($point);
        if ($value === null) {
            return null;
        }

        if ($value > 1) {
            return 900;
        }

        if ($value > 0) {
            return 600;
        }

        return 0;
    }

    private function normalizeConferencePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $researchField = $this->normalizeNullableTrimmedString($data['research_field'] ?? null);
        $organization = $this->normalizeNullableTrimmedString($data['organization'] ?? null);
        $notes = $this->normalizeNullableTrimmedString($data['notes'] ?? null);

        $hasProceedings = (bool) ($data['has_proceedings'] ?? false);
        $hasIsbn = (bool) ($data['has_isbn'] ?? false);

        $isbn = null;
        if ($hasIsbn) {
            $rawIsbn = preg_replace('/\s+/', '', (string) ($data['isbn'] ?? ''));
            $rawIsbn = strtoupper(trim((string) $rawIsbn));
            $isbn = $rawIsbn !== '' ? $rawIsbn : null;
        }

        $point = $this->toNullableFloat($data['point'] ?? null);
        if ($point !== null) {
            $point = round($point, 2);
        }

        return [
            'name' => $name,
            'research_field' => $researchField,
            'year' => isset($data['year']) && $data['year'] !== '' ? (int) $data['year'] : null,
            'organization' => $organization,
            'has_proceedings' => $hasProceedings,
            'has_isbn' => $hasIsbn,
            'isbn' => $isbn,
            'point' => $point,
            'notes' => $notes,
        ];
    }

    private function normalizeNullableTrimmedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function generateUniquePublisherCode(string $name): string
    {
        $slug = strtoupper(Str::slug($name, '-'));
        if ($slug === '') {
            $slug = 'PUBLISHER';
        }

        $base = 'NXB-' . $slug;
        if (mb_strlen($base) > 50) {
            $base = mb_substr($base, 0, 50);
        }

        $candidate = $base;
        $suffix = 1;
        while (DB::table('publishers')->where('code', $candidate)->exists()) {
            $suffixText = '-' . $suffix;
            $maxBaseLength = 50 - mb_strlen($suffixText);
            $trimmedBase = mb_substr($base, 0, max(1, $maxBaseLength));
            $candidate = $trimmedBase . $suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private function suggestionQueryByType(string $type)
    {
        return DB::table('work_catalog_suggestions as s')
            ->where('s.suggestion_type', $type)
            ->where('s.status', 'pending')
            ->orderByDesc('s.created_at')
            ->select([
                's.id',
                's.activity_id',
                's.suggestion_type',
                's.source_name',
                's.status',
                's.submitted_by_lecturer_id',
                's.submitted_by_user_id',
                's.reviewed_by_user_id',
                's.reviewed_at',
                's.review_note',
                's.resolved_catalog_id',
                's.payload',
                's.created_at',
                's.updated_at',
            ]);
    }

    private function findPendingSuggestion(int $id, string $type)
    {
        return DB::table('work_catalog_suggestions')
            ->where('id', $id)
            ->where('suggestion_type', $type)
            ->where('status', 'pending')
            ->first();
    }

    private function decodeSuggestionPayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function workCatalogSuggestionPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'activity_id' => (int) $row->activity_id,
            'suggestion_type' => (string) $row->suggestion_type,
            'source_name' => (string) $row->source_name,
            'status' => (string) $row->status,
            'submitted_by_lecturer_id' => $row->submitted_by_lecturer_id !== null ? (int) $row->submitted_by_lecturer_id : null,
            'submitted_by_user_id' => $row->submitted_by_user_id !== null ? (int) $row->submitted_by_user_id : null,
            'reviewed_by_user_id' => $row->reviewed_by_user_id !== null ? (int) $row->reviewed_by_user_id : null,
            'reviewed_at' => $row->reviewed_at,
            'review_note' => $row->review_note,
            'resolved_catalog_id' => $row->resolved_catalog_id !== null ? (int) $row->resolved_catalog_id : null,
            'payload' => $this->decodeSuggestionPayload($row->payload ?? null),
            'created_at' => $row->created_at,
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

    private function publisherPayload($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'address' => $row->address,
            'phone' => $row->phone,
            'email' => $row->email,
            'website' => $row->website,
            'is_active' => (bool) $row->is_active,
            'updated_at' => $row->updated_at,
        ];
    }
}
