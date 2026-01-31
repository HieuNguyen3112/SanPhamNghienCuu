<?php

namespace App\Console\Commands;

use App\Support\FacultyCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NormalizeFacultiesCommand extends Command
{
    protected $signature = 'faculties:normalize {--dry-run : Only report changes}';
    protected $description = 'Normalize faculties to canonical HCMUE list and merge duplicates safely.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $canonical = FacultyCatalog::all();
        $canonicalByCode = collect($canonical)->keyBy('code');
        $canonicalNameMap = collect($canonical)->mapWithKeys(function ($row) {
            return [$this->normalizeName($row['name']) => $row['code']];
        });

        $this->info('Normalizing faculties (dry-run=' . ($dryRun ? 'yes' : 'no') . ')');

        DB::transaction(function () use ($dryRun, $canonicalByCode, $canonicalNameMap) {
            foreach ($canonicalByCode as $row) {
                if ($dryRun) {
                    continue;
                }
                DB::table('faculties')->updateOrInsert(
                    ['code' => $row['code']],
                    ['name' => $row['name'], 'updated_at' => now(), 'created_at' => now()]
                );
            }

            $canonicalIds = DB::table('faculties')
                ->whereIn('code', $canonicalByCode->keys()->all())
                ->pluck('id', 'code')
                ->all();

            $tables = DB::table('information_schema.columns')
                ->select('table_name')
                ->where('table_schema', DB::raw('database()'))
                ->where('column_name', 'faculty_id')
                ->pluck('table_name')
                ->filter(fn ($table) => $table !== 'faculties')
                ->values();

            $faculties = DB::table('faculties')->get();
            $deleted = [];
            $updatedCounts = [];
            $unmapped = [];

            foreach ($faculties as $faculty) {
                $targetId = null;
                if (isset($canonicalIds[$faculty->code])) {
                    $targetId = (int) $canonicalIds[$faculty->code];
                } else {
                    $normalized = $this->normalizeName($faculty->name);
                    $targetCode = $canonicalNameMap[$normalized] ?? null;
                    if ($targetCode && isset($canonicalIds[$targetCode])) {
                        $targetId = (int) $canonicalIds[$targetCode];
                    }
                }

                if (! $targetId || (int) $faculty->id === $targetId) {
                    if (! $targetId) {
                        $unmapped[] = ['id' => $faculty->id, 'code' => $faculty->code, 'name' => $faculty->name];
                    }
                    continue;
                }

                foreach ($tables as $table) {
                    if ($dryRun) {
                        continue;
                    }
                    $affected = DB::table($table)->where('faculty_id', $faculty->id)->count();
                    if ($affected > 0) {
                        $updatedCounts[$table] = ($updatedCounts[$table] ?? 0) + $affected;
                    }
                    DB::table($table)->where('faculty_id', $faculty->id)->update([
                        'faculty_id' => $targetId,
                    ]);
                }

                if (! $dryRun) {
                    DB::table('faculties')->where('id', $faculty->id)->delete();
                    $deleted[] = $faculty->id;
                }
            }

            if ($dryRun) {
                $this->info('Dry-run completed.');
                return;
            }

            $this->info('Deleted duplicate faculties: ' . implode(', ', $deleted));
            if ($updatedCounts) {
                foreach ($updatedCounts as $table => $count) {
                    $this->info("Updated {$count} rows in {$table}");
                }
            }
            if ($unmapped) {
                $this->warn('Unmapped faculties (manual review needed):');
                foreach ($unmapped as $row) {
                    $this->warn(' - ' . $row['id'] . ' | ' . $row['code'] . ' | ' . $row['name']);
                }
            }
        });

        return self::SUCCESS;
    }

    private function normalizeName(?string $name): string
    {
        return Str::of((string) $name)
            ->lower()
            ->trim()
            ->ascii()
            ->toString();
    }
}
