<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $today = now()->startOfDay();
        $driver = DB::getDriverName();

        DB::table('project_version')
            ->select(['id', 'downloads', 'created_at'])
            ->where('downloads', '>', 0)
            ->orderBy('id')
            ->chunkById(250, function ($versions) use ($today, $driver): void {
                $rows = [];
                $now = now();

                foreach ($versions as $version) {
                    $downloads = (int) $version->downloads;

                    if ($downloads <= 0) {
                        continue;
                    }

                    $startDate = Carbon::parse($version->created_at)->startOfDay();
                    if ($startDate->greaterThan($today)) {
                        $startDate = $today->copy();
                    }

                    $days = $startDate->diffInDays($today) + 1;
                    if ($days <= 0) {
                        continue;
                    }

                    $base = intdiv($downloads, $days);
                    $remainder = $downloads % $days;

                    for ($i = 0; $i < $days; $i++) {
                        $allocated = $base + ($i < $remainder ? 1 : 0);

                        if ($allocated === 0) {
                            continue;
                        }

                        $rows[] = [
                            'project_version_id' => $version->id,
                            'date' => $startDate->copy()->addDays($i)->toDateString(),
                            'downloads' => $allocated,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (empty($rows)) {
                    return;
                }

                if (in_array($driver, ['mysql', 'mariadb'], true)) {
                    DB::table('project_version_daily_download')->upsert(
                        $rows,
                        ['project_version_id', 'date'],
                        [
                            'downloads' => DB::raw('downloads + VALUES(downloads)'),
                            'updated_at' => DB::raw('VALUES(updated_at)'),
                        ]
                    );

                    return;
                }

                foreach ($rows as $row) {
                    $updated = DB::table('project_version_daily_download')
                        ->where('project_version_id', $row['project_version_id'])
                        ->where('date', $row['date'])
                        ->increment('downloads', $row['downloads'], ['updated_at' => $row['updated_at']]);

                    if (! $updated) {
                        DB::table('project_version_daily_download')->insert($row);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: impossible to safely distinguish historical backfilled rows from real daily rows.
    }
};

