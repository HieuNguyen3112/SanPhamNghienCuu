<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMojibakeCommand extends Command
{
    protected $signature = 'text:fix-mojibake {--dry-run : Only report changes}';
    protected $description = 'Fix mojibake in text columns by converting from ISO-8859-1 to UTF-8 when safe.';

    public function handle(): int
    {
        $targets = [
            ['table' => 'faculties', 'column' => 'name'],
            ['table' => 'departments', 'column' => 'name'],
            ['table' => 'lecturers', 'column' => 'full_name'],
            ['table' => 'users', 'column' => 'name'],
        ];

        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        DB::transaction(function () use ($targets, $dryRun, &$total) {
            foreach ($targets as $target) {
                $table = $target['table'];
                $column = $target['column'];

                $rows = DB::table($table)
                    ->select(['id', $column])
                    ->whereRaw($column . " like concat('%', char(195), '%')")
                    ->orWhereRaw($column . " like concat('%', char(194), '%')")
                    ->orWhereRaw($column . " like concat('%', char(196), '%')")
                    ->get();

                foreach ($rows as $row) {
                    $current = $row->{$column};
                    $fixed = $this->fix($current);
                    if ($fixed === $current) {
                        continue;
                    }

                    $this->line("[{$table}.{$column}] {$row->id}: {$current} => {$fixed}");
                    $total++;

                    if (! $dryRun) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([
                                $column => $fixed,
                                'updated_at' => now(),
                            ]);
                    }
                }
            }
        });

        $this->info('Total updated: ' . $total . ($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    private function fix(string $value): string
    {
        $converted = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        if ($this->mojibakeScore($converted) < $this->mojibakeScore($value)) {
            return $converted;
        }

        return $value;
    }

    private function mojibakeScore(string $value): int
    {
        $score = 0;
        $needles = [
            chr(0xC3),
            chr(0xC2),
            chr(0xC4),
            "\u{00E1}\u{00BA}",
            "\u{00E2}\u{20AC}\u{201C}",
            "\u{00E2}\u{20AC}\u{201D}",
            "\u{00E2}\u{20AC}",
        ];

        foreach ($needles as $needle) {
            $score += substr_count($value, $needle);
        }

        return $score;
    }
}
