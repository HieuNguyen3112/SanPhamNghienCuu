<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LecturerResearchWorkSearchController extends AdminResearchWorkSearchController
{
    protected function normalizeFilters(array $validated): array
    {
        $filters = parent::normalizeFilters($validated);
        $filters['status'] = 'approved';
        return $filters;
    }

    protected function applyRoleScope($query, Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        $approvedStatusId = $this->approvedStatusId();
        if (! $approvedStatusId) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where('ra.status_id', $approvedStatusId);
    }

    protected function buildFiles(object $row): array
    {
        $files = parent::buildFiles($row);

        foreach ($files as $index => $file) {
            if (($file['kind'] ?? null) !== 'file') {
                continue;
            }

            $files[$index]['url'] = route(
                'lecturer.works.attachments.download',
                ['attachment' => $file['file_id']],
                false
            );
        }

        return $files;
    }

    protected function approvedStatusId(): ?int
    {
        static $approvedId = null;
        if ($approvedId !== null) {
            return $approvedId;
        }

        $value = DB::table('activity_statuses')->where('code', 'approved')->value('id');
        $approvedId = $value ? (int) $value : null;

        return $approvedId;
    }
}
