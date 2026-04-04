<?php

namespace App\Console\Commands;

use App\Services\Hours\RecalculateLecturerYearlyHoursService;
use Illuminate\Console\Command;

class HoursRecalculateYearlyCommand extends Command
{
    protected $signature = 'hours:recalculate-yearly
        {--lecturer_id= : Recalculate for a single lecturer}
        {--academic_year_id= : Recalculate for a single academic year}
        {--activity_id=* : Recalculate only pairs affected by one or more activity IDs}';

    protected $description = 'Recalculate lecturer_yearly_hours cache from approved hours source data';

    private RecalculateLecturerYearlyHoursService $recalculateLecturerYearlyHoursService;

    public function __construct(RecalculateLecturerYearlyHoursService $recalculateLecturerYearlyHoursService)
    {
        parent::__construct();
        $this->recalculateLecturerYearlyHoursService = $recalculateLecturerYearlyHoursService;
    }

    public function handle(): int
    {
        $lecturerId = $this->intOption('lecturer_id');
        $academicYearId = $this->intOption('academic_year_id');
        $activityIds = collect((array) $this->option('activity_id'))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($activityIds !== []) {
            $result = $this->recalculateLecturerYearlyHoursService->recalculateForActivities($activityIds);
        } else {
            $result = $this->recalculateLecturerYearlyHoursService->recalculate($lecturerId, $academicYearId);
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total pairs', (string) ($result['total_pairs'] ?? 0)],
                ['Created rows', (string) ($result['created'] ?? 0)],
                ['Updated rows', (string) ($result['updated'] ?? 0)],
                ['Unchanged rows', (string) ($result['unchanged'] ?? 0)],
            ]
        );

        $this->info('Yearly hours cache recalculation completed.');

        return self::SUCCESS;
    }

    private function intOption(string $name): ?int
    {
        $value = $this->option($name);
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $parsed = (int) $value;
        return $parsed > 0 ? $parsed : null;
    }
}
