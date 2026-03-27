<?php

namespace App\Support;

use Illuminate\Support\Str;

class LecturerHoursSummaryReportBuilder
{
    public static function build(array $lecturerRows, iterable $activityRows, bool $deriveTotalFromGroups = false): array
    {
        $rowsByLecturer = [];

        foreach (array_values($lecturerRows) as $index => $row) {
            $lecturerId = (int) ($row['lecturer_id'] ?? 0);
            if ($lecturerId <= 0) {
                continue;
            }

            $rowsByLecturer[$lecturerId] = self::seedRow($row, $index + 1);
        }

        foreach ($activityRows as $detail) {
            $lecturerId = (int) ($detail['lecturer_id'] ?? 0);
            if ($lecturerId <= 0 || ! isset($rowsByLecturer[$lecturerId])) {
                continue;
            }

            self::applyDetail($rowsByLecturer[$lecturerId], $detail);
        }

        return array_values(array_map(
            static fn (array $row) => self::finalizeRow($row, $deriveTotalFromGroups),
            $rowsByLecturer
        ));
    }

    private static function seedRow(array $row, int $ordinal): array
    {
        return [
            'tt' => $ordinal,
            'lecturer_id' => (int) ($row['lecturer_id'] ?? 0),
            'lecturer_code' => (string) ($row['lecturer_code'] ?? ''),
            'lecturer_full_name' => (string) ($row['lecturer_full_name'] ?? ''),
            'faculty_name' => (string) ($row['faculty_name'] ?? ''),
            'department_name' => (string) ($row['department_name'] ?? ''),
            'academic_year_code' => (string) ($row['academic_year_code'] ?? ''),
            'hours_total' => (float) ($row['hours_total'] ?? 0),
            'groups' => [
                'national_projects' => [
                    'principal_hours' => 0.0,
                    'principal_activity_ids' => [],
                    'participant_hours' => 0.0,
                    'participant_activity_ids' => [],
                    'activity_ids' => [],
                ],
                'school_projects' => [
                    'principal_hours' => 0.0,
                    'principal_activity_ids' => [],
                    'participant_hours' => 0.0,
                    'participant_activity_ids' => [],
                    'activity_ids' => [],
                ],
                'papers' => [
                    'point_1_2' => [
                        'hours_total' => 0.0,
                        'activity_ids' => [],
                    ],
                    'point_le_1' => [
                        'hours_total' => 0.0,
                        'activity_ids' => [],
                    ],
                    'other' => [
                        'hours_total' => 0.0,
                        'activity_ids' => [],
                    ],
                ],
                'textbooks' => [
                    'principal_hours' => 0.0,
                    'principal_activity_ids' => [],
                    'participant_hours' => 0.0,
                    'participant_activity_ids' => [],
                    'hours_total' => 0.0,
                ],
                'scholarly_books' => [
                    'principal_hours' => 0.0,
                    'principal_activity_ids' => [],
                    'participant_hours' => 0.0,
                    'participant_activity_ids' => [],
                    'hours_total' => 0.0,
                ],
                'conferences' => [
                    'report_ids' => [],
                    'report_hours' => 0.0,
                    'attend_ids' => [],
                    'attend_hours' => 0.0,
                    'hours_total' => 0.0,
                ],
            ],
        ];
    }

    private static function applyDetail(array &$row, array $detail): void
    {
        $group = self::resolveGroup(
            (string) ($detail['kind_code'] ?? ''),
            (string) ($detail['type_code'] ?? '')
        );

        if ($group === null) {
            return;
        }

        $activityId = (int) ($detail['activity_id'] ?? 0);
        $hoursAssigned = (float) ($detail['hours_assigned'] ?? 0);
        $isPrincipal = self::isPrincipalRole($detail['member_role_code'] ?? null);

        switch ($group) {
            case 'national_projects':
            case 'school_projects':
                if ($isPrincipal) {
                    $row['groups'][$group]['principal_hours'] += $hoursAssigned;
                    if ($activityId > 0) {
                        $row['groups'][$group]['principal_activity_ids'][$activityId] = true;
                    }
                } else {
                    $row['groups'][$group]['participant_hours'] += $hoursAssigned;
                    if ($activityId > 0) {
                        $row['groups'][$group]['participant_activity_ids'][$activityId] = true;
                    }
                }
                if ($activityId > 0) {
                    $row['groups'][$group]['activity_ids'][$activityId] = true;
                }
                break;

            case 'papers':
                $paperBucket = self::resolvePaperBucket((string) ($detail['type_code'] ?? ''));
                $row['groups'][$group][$paperBucket]['hours_total'] += $hoursAssigned;
                if ($activityId > 0) {
                    $row['groups'][$group][$paperBucket]['activity_ids'][$activityId] = true;
                }
                break;

            case 'textbooks':
            case 'scholarly_books':
                if ($isPrincipal) {
                    $row['groups'][$group]['principal_hours'] += $hoursAssigned;
                    if ($activityId > 0) {
                        $row['groups'][$group]['principal_activity_ids'][$activityId] = true;
                    }
                } else {
                    $row['groups'][$group]['participant_hours'] += $hoursAssigned;
                    if ($activityId > 0) {
                        $row['groups'][$group]['participant_activity_ids'][$activityId] = true;
                    }
                }
                $row['groups'][$group]['hours_total'] += $hoursAssigned;
                break;

            case 'conferences':
                $typeBucket = self::resolveConferenceBucket((string) ($detail['type_code'] ?? ''));
                if ($activityId > 0 && $typeBucket !== null) {
                    $row['groups'][$group][$typeBucket][$activityId] = true;
                }
                if ($typeBucket === 'report_ids') {
                    $row['groups'][$group]['report_hours'] += $hoursAssigned;
                } elseif ($typeBucket === 'attend_ids') {
                    $row['groups'][$group]['attend_hours'] += $hoursAssigned;
                }
                $row['groups'][$group]['hours_total'] += $hoursAssigned;
                break;
        }
    }

    private static function finalizeRow(array $row, bool $deriveTotalFromGroups = false): array
    {
        $computedHoursTotal = $row['groups']['national_projects']['principal_hours']
            + $row['groups']['national_projects']['participant_hours']
            + $row['groups']['school_projects']['principal_hours']
            + $row['groups']['school_projects']['participant_hours']
            + $row['groups']['papers']['point_1_2']['hours_total']
            + $row['groups']['papers']['point_le_1']['hours_total']
            + $row['groups']['papers']['other']['hours_total']
            + $row['groups']['textbooks']['hours_total']
            + $row['groups']['scholarly_books']['hours_total']
            + $row['groups']['conferences']['hours_total'];

        $hoursTotal = $deriveTotalFromGroups
            ? $computedHoursTotal
            : (float) $row['hours_total'];

        return [
            'tt' => $row['tt'],
            'lecturer_id' => $row['lecturer_id'],
            'lecturer_code' => $row['lecturer_code'],
            'lecturer_full_name' => $row['lecturer_full_name'],
            'faculty_name' => $row['faculty_name'],
            'department_name' => $row['department_name'],
            'academic_year_code' => $row['academic_year_code'],
            'national_projects_principal_summary' => self::summaryValue(
                $row['groups']['national_projects']['principal_activity_ids'],
                $row['groups']['national_projects']['principal_hours']
            ),
            'national_projects_participant_summary' => self::summaryValue(
                $row['groups']['national_projects']['participant_activity_ids'],
                $row['groups']['national_projects']['participant_hours']
            ),
            'national_projects_count' => count($row['groups']['national_projects']['activity_ids']),
            'school_projects_principal_summary' => self::summaryValue(
                $row['groups']['school_projects']['principal_activity_ids'],
                $row['groups']['school_projects']['principal_hours']
            ),
            'school_projects_participant_summary' => self::summaryValue(
                $row['groups']['school_projects']['participant_activity_ids'],
                $row['groups']['school_projects']['participant_hours']
            ),
            'school_projects_count' => count($row['groups']['school_projects']['activity_ids']),
            'papers_point_1_2_summary' => self::summaryValue(
                $row['groups']['papers']['point_1_2']['activity_ids'],
                $row['groups']['papers']['point_1_2']['hours_total']
            ),
            'papers_point_le_1_summary' => self::summaryValue(
                $row['groups']['papers']['point_le_1']['activity_ids'],
                $row['groups']['papers']['point_le_1']['hours_total']
            ),
            'papers_other_summary' => self::summaryValue(
                $row['groups']['papers']['other']['activity_ids'],
                $row['groups']['papers']['other']['hours_total']
            ),
            'textbooks_principal_summary' => self::summaryValue(
                $row['groups']['textbooks']['principal_activity_ids'],
                $row['groups']['textbooks']['principal_hours']
            ),
            'textbooks_participant_summary' => self::summaryValue(
                $row['groups']['textbooks']['participant_activity_ids'],
                $row['groups']['textbooks']['participant_hours']
            ),
            'textbooks_hours_total' => self::roundHours($row['groups']['textbooks']['hours_total']),
            'scholarly_books_principal_summary' => self::summaryValue(
                $row['groups']['scholarly_books']['principal_activity_ids'],
                $row['groups']['scholarly_books']['principal_hours']
            ),
            'scholarly_books_participant_summary' => self::summaryValue(
                $row['groups']['scholarly_books']['participant_activity_ids'],
                $row['groups']['scholarly_books']['participant_hours']
            ),
            'scholarly_books_hours_total' => self::roundHours($row['groups']['scholarly_books']['hours_total']),
            'conferences_report_summary' => self::summaryValue(
                $row['groups']['conferences']['report_ids'],
                $row['groups']['conferences']['report_hours']
            ),
            'conferences_attend_summary' => self::summaryValue(
                $row['groups']['conferences']['attend_ids'],
                $row['groups']['conferences']['attend_hours']
            ),
            'conferences_hours_total' => self::roundHours($row['groups']['conferences']['hours_total']),
            'hours_total' => self::roundHours($hoursTotal),
        ];
    }

    private static function resolveGroup(string $kindCode, string $typeCode): ?string
    {
        $normalizedKind = self::normalizeToken($kindCode);
        $normalizedType = self::normalizeToken($typeCode);

        if ($normalizedKind === 'project') {
            if (self::containsAny($normalizedType, ['coso', 'co_so', 'university', 'truong', 'cap_truong', 'faculty', 'khoa'])) {
                return 'school_projects';
            }

            return 'national_projects';
        }

        if ($normalizedKind === 'paper') {
            return 'papers';
        }

        if ($normalizedKind === 'book') {
            if (self::containsAny($normalizedType, ['textbook', 'giao_trinh', 'reference', 'tham_khao', 'tai_lieu', 'book_textbook', 'book_reference'])) {
                return 'textbooks';
            }

            return 'scholarly_books';
        }

        if ($normalizedKind === 'conference') {
            return 'conferences';
        }

        return null;
    }

    private static function resolvePaperBucket(string $typeCode): string
    {
        $normalized = self::normalizeToken($typeCode);

        if (self::containsAny($normalized, ['hdgsnn_900', 'hdgsnn_12', 'point_12'])) {
            return 'point_1_2';
        }

        if (self::containsAny($normalized, ['hdgsnn_600', 'point_le_1', 'point_1'])) {
            return 'point_le_1';
        }

        return 'other';
    }

    private static function resolveConferenceBucket(string $typeCode): ?string
    {
        $normalized = self::normalizeToken($typeCode);

        if (self::containsAny($normalized, ['report', 'bao_cao', 'presentation', 'present'])) {
            return 'report_ids';
        }

        if (self::containsAny($normalized, ['attend', 'tham_du', 'thamdu', 'participant'])) {
            return 'attend_ids';
        }

        return null;
    }

    private static function isPrincipalRole(?string $memberRoleCode): bool
    {
        if (! $memberRoleCode) {
            return false;
        }

        return in_array(strtolower($memberRoleCode), ['principal', 'chief_editor'], true);
    }

    private static function normalizeToken(string $value): string
    {
        $ascii = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $ascii);

        return trim((string) $normalized, '_');
    }

    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function roundHours(float $value): float
    {
        return round($value, 2);
    }

    private static function summaryValue(array $activityIds, float $hours): array
    {
        return [
            'count' => count($activityIds),
            'hours' => self::roundHours($hours),
        ];
    }
}
