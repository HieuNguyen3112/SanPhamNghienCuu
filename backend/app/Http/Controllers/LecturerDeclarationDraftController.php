<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lecturer\LecturerDeclarationDraftIndexRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LecturerDeclarationDraftController extends Controller
{
    public function index(LecturerDeclarationDraftIndexRequest $request)
    {
        $lecturer = $this->resolveLecturer($request);
        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $filters = $request->validated();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 8)));

        $query = DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->leftJoin('activity_types as at', 'ra.type_id', '=', 'at.id')
            ->where('ra.owner_lecturer_id', $lecturer->id)
            ->where('ast.code', 'draft')
            ->select([
                'ra.id',
                'ra.title',
                'ra.updated_at',
                'ak.code as kind_code',
                'ak.name as kind_name',
                'at.code as type_code',
                'at.name as type_name',
            ]);

        if (! empty($filters['q'])) {
            $keyword = '%' . trim($filters['q']) . '%';
            $query->where(function ($sub) use ($keyword) {
                $sub->where('ra.title', 'like', $keyword)
                    ->orWhere('ra.activity_code', 'like', $keyword);
            });
        }

        $query->orderByDesc('ra.updated_at')->orderByDesc('ra.id');
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(function ($row) {
                $route = $this->draftRouteByKind($row->kind_code, (int) $row->id);
                return [
                    'id' => (int) $row->id,
                    'title' => $row->title,
                    'type_label' => $this->buildTypeLabel(
                        $row->kind_code,
                        $row->kind_name,
                        $row->type_code,
                        $row->type_name
                    ),
                    'updated_at' => $this->normalizeDateTime($row->updated_at),
                    'to' => $route,
                ];
            })
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

    private function resolveLecturer(Request $request)
    {
        $user = $request->user();
        return $user?->lecturer;
    }

    private function draftRouteByKind(?string $kindCode, int $activityId): string
    {
        $base = match ($kindCode) {
            'paper' => '/declarations/articles',
            'project' => '/declarations/projects',
            'book' => '/declarations/books',
            'conference' => '/declarations/others',
            default => '/declarations/articles',
        };

        return $base . '?activity_id=' . $activityId;
    }

    private function buildTypeLabel(
        ?string $kindCode,
        ?string $kindName,
        ?string $typeCode,
        ?string $typeName
    ): string
    {
        $resolvedKindName = $this->mapKindName($kindCode, $kindName);
        $resolvedTypeName = $this->mapTypeName($typeCode, $typeName);

        if ($resolvedTypeName) {
            return $resolvedKindName ? ($resolvedKindName . ' - ' . $resolvedTypeName) : $resolvedTypeName;
        }

        return $resolvedKindName ?: '-';
    }

    private function mapKindName(?string $code, ?string $fallback): ?string
    {
        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'paper' => 'Bài báo khoa học',
            'book' => 'Sách, giáo trình',
            'project' => 'Đề tài KH&CN',
            'conference' => 'Hội nghị, hội thảo',
            default => null,
        };

        return $mapped ?? $fallback ?? $code;
    }

    private function mapTypeName(?string $code, ?string $fallback): ?string
    {
        if (! $code) {
            return $fallback;
        }

        $mapped = match (strtolower($code)) {
            'hdgsnn_900' => 'Bài báo HDGSNN 1-2 điểm (900 giờ)',
            'hdgsnn_600' => 'Bài báo HDGSNN >= 1 điểm (600 giờ)',
            'hdgsnn_300' => 'Bài báo có ISSN/ISBN (300 giờ)',
            'textbook' => 'Giáo trình',
            'reference' => 'Tài liệu tham khảo',
            'bo', 'ministry' => 'Đề tài cấp Bộ (2 năm)',
            'coso', 'university' => 'Đề tài cấp Trường (1 năm)',
            'report' => 'Báo cáo hội thảo',
            'attend' => 'Tham dự hội thảo',
            default => null,
        };

        return $mapped ?? $fallback ?? $code;
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
}
