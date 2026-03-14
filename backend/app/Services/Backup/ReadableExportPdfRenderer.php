<?php

namespace App\Services\Backup;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReadableExportPdfRenderer
{
    public function renderWorkSummary(array $payload): string
    {
        return $this->render('exports.backup.work_summary', [
            'summary' => $this->buildWorkSummaryViewModel($payload),
        ]);
    }

    public function renderLecturerSummary(array $payload): string
    {
        return $this->render('exports.backup.lecturer_summary', [
            'summary' => $this->buildLecturerSummaryViewModel($payload),
        ]);
    }

    private function render(string $view, array $data): string
    {
        $output = Pdf::loadView($view, $data)
            ->setPaper('A4', 'portrait')
            ->output();

        if (! is_string($output) || $output === '') {
            throw new BackupRuntimeException('Khong the sinh tep PDF cho readable export.');
        }

        return $output;
    }

    private function buildWorkSummaryViewModel(array $payload): array
    {
        $activity = is_array($payload['activity'] ?? null) ? $payload['activity'] : [];
        $owner = is_array($payload['owner'] ?? null) ? $payload['owner'] : [];
        $participants = array_values(array_map(
            fn (array $participant): array => [
                'display_name' => $this->buildPersonLabel($participant),
                'role' => $this->resolveRoleLabel($participant),
                'unit' => $this->buildUnitLabel($participant),
                'hours_assigned' => $this->formatHours($participant['hours_assigned'] ?? null),
                'contribution_share' => $this->formatPercent($participant['contribution_share'] ?? null),
            ],
            array_values(array_filter(
                $payload['participants'] ?? [],
                static fn ($participant): bool => is_array($participant)
            ))
        ));
        $evidenceFiles = array_values(array_map(
            fn (array $file): array => [
                'display_name' => trim((string) ($file['stored_filename'] ?? $file['original_name'] ?? '')),
                'file_type' => $this->resolveFileTypeLabel($file['file_type'] ?? null),
                'uploaded_by' => $this->buildPersonLabel(is_array($file['uploaded_by'] ?? null) ? $file['uploaded_by'] : []),
                'uploaded_at' => $this->formatDateTime($file['uploaded_at'] ?? null),
                'size' => $this->formatBytes($file['size_bytes'] ?? null),
                'status' => ($file['export_status'] ?? null) === 'copied' ? 'Da dua vao thu muc export' : 'Chi co metadata',
            ],
            array_values(array_filter(
                $payload['evidence_files'] ?? [],
                static fn ($file): bool => is_array($file)
            ))
        ));
        $stats = is_array($payload['stats'] ?? null) ? $payload['stats'] : [];

        return [
            'generated_at' => $this->formatDateTime(data_get($payload, 'snapshot.generated_at')),
            'activity_code' => trim((string) ($activity['activity_code'] ?? '')),
            'title' => trim((string) ($activity['title'] ?? '')),
            'kind_name' => trim((string) data_get($activity, 'kind.name', '')),
            'type_name' => trim((string) data_get($activity, 'type.name', '')),
            'status_name' => trim((string) data_get($activity, 'status.name', '')),
            'academic_year_code' => trim((string) data_get($activity, 'academic_year.code', '')),
            'start_date' => $this->formatDate($activity['start_date'] ?? null),
            'end_date' => $this->formatDate($activity['end_date'] ?? null),
            'submitted_at' => $this->formatDateTime($activity['submitted_at'] ?? null),
            'approved_at' => $this->formatDateTime($activity['approved_at'] ?? null),
            'research_hours' => $this->formatHours($activity['total_hours_calc'] ?? null),
            'owner_display_name' => $this->buildPersonLabel($owner),
            'owner_unit' => $this->buildUnitLabel($owner),
            'participant_count' => (int) ($stats['participant_count'] ?? count($participants)),
            'evidence_file_count' => (int) ($stats['evidence_file_count'] ?? count($evidenceFiles)),
            'copied_evidence_count' => (int) ($stats['copied_evidence_count'] ?? 0),
            'metadata_only_evidence_count' => (int) ($stats['metadata_only_evidence_count'] ?? 0),
            'participants' => $participants,
            'evidence_files' => $evidenceFiles,
        ];
    }

    private function buildLecturerSummaryViewModel(array $payload): array
    {
        $lecturer = is_array($payload['lecturer'] ?? null) ? $payload['lecturer'] : [];
        $works = array_values(array_filter(
            $payload['works'] ?? [],
            static fn ($work): bool => is_array($work)
        ));
        $roles = [];
        $hours = 0.0;
        $hasHours = false;

        $workRows = array_map(function (array $work) use (&$roles, &$hours, &$hasHours): array {
            $participation = is_array($work['participation'] ?? null) ? $work['participation'] : [];
            $roleLabel = $this->resolveRoleLabel($participation);
            if ($roleLabel !== '') {
                $roles[$roleLabel] = true;
            }

            $hoursAssigned = $participation['hours_assigned'] ?? null;
            if (is_numeric($hoursAssigned)) {
                $hours += (float) $hoursAssigned;
                $hasHours = true;
            }

            return [
                'title' => trim((string) ($work['title'] ?? '')),
                'activity_code' => trim((string) ($work['activity_code'] ?? '')),
                'role' => $roleLabel !== '' ? $roleLabel : 'Thanh vien',
                'evidence_count' => (int) ($work['uploaded_evidence_count'] ?? 0),
                'reference_path' => trim((string) ($work['cong_trinh_relative_path'] ?? $work['thong_tin_cong_trinh'] ?? '')),
            ];
        }, $works);

        return [
            'generated_at' => $this->formatDateTime(data_get($payload, 'snapshot.generated_at')),
            'lecturer_display_name' => $this->buildPersonLabel($lecturer),
            'unit' => $this->buildUnitLabel($lecturer),
            'email' => trim((string) ($lecturer['email'] ?? '')),
            'works_count' => (int) data_get($payload, 'snapshot.works_count', count($workRows)),
            'uploaded_evidence_count' => (int) data_get($payload, 'snapshot.uploaded_evidence_count', 0),
            'roles' => array_keys($roles),
            'known_research_hours' => $hasHours ? $this->formatHours($hours) : null,
            'works' => $workRows,
        ];
    }

    private function buildPersonLabel(array $payload): string
    {
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $code = trim((string) ($payload['code'] ?? ''));

        if ($fullName !== '' && $code !== '') {
            return $fullName . ' (' . $code . ')';
        }

        return $fullName !== '' ? $fullName : ($code !== '' ? $code : 'Khong ro');
    }

    private function buildUnitLabel(array $payload): string
    {
        $faculty = trim((string) data_get($payload, 'faculty.name', ''));
        $department = trim((string) data_get($payload, 'department.name', ''));
        $parts = array_values(array_filter([$faculty, $department], static fn (string $value): bool => $value !== ''));

        return $parts === [] ? 'Khong ro' : implode(' / ', array_unique($parts));
    }

    private function resolveRoleLabel(array $payload): string
    {
        if ((bool) ($payload['is_owner'] ?? false)) {
            return 'Chu nhiem';
        }

        $memberRoleName = trim((string) ($payload['member_role_name'] ?? ''));

        return $memberRoleName !== '' ? $memberRoleName : '';
    }

    private function resolveFileTypeLabel($value): string
    {
        if (is_array($value)) {
            $name = trim((string) ($value['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }

            $code = trim((string) ($value['code'] ?? ''));
            if ($code !== '') {
                return $code;
            }

            $id = $value['id'] ?? null;
            if (is_scalar($id)) {
                $normalized = trim((string) $id);
                if ($normalized !== '') {
                    return $normalized;
                }
            }

            return 'Khong ro';
        }

        if ($value === null) {
            return 'Khong ro';
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : 'Khong ro';
    }

    private function formatDate($value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return trim($value);
        }
    }

    private function formatDateTime($value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return trim($value);
        }
    }

    private function formatHours($value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;
        $decimals = fmod($number, 1.0) === 0.0 ? 0 : 2;

        return number_format($number, $decimals, ',', '.') . ' gio';
    }

    private function formatPercent($value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, ',', '.') . '%';
    }

    private function formatBytes($value): string
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return 'Khong ro';
        }

        $bytes = (float) $value;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        $decimals = $unitIndex === 0 ? 0 : 2;

        return number_format($bytes, $decimals, ',', '.') . ' ' . $units[$unitIndex];
    }
}
