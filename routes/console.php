<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Illuminate\Support\Facades\Schedule::call(fn () => app(App\Http\Controllers\BackupController::class)->runScheduledBackup())
    ->name('automatic-database-backup')->everyMinute()->withoutOverlapping(10);

Artisan::command('db:restore-snapshot {path? : Path to the snapshot JSON file}', function (?string $path = null) {
    if (!$path) {
        $defaultCandidates = [
            'E:\\PaoloPaolo_Backups\\backup_p7db_2026-10-10_051707_a825b96d.json',
            'C:\\Users\\Cirunay\\Downloads\\backup_p7db_2026-10-10_051707_a825b96d.json',
        ];
        foreach ($defaultCandidates as $candidate) {
            if (file_exists($candidate)) {
                $path = $candidate;
                break;
            }
        }
    }

    if (!$path || !file_exists($path)) {
        $this->error("Snapshot file not found at: " . ($path ?? 'default path'));
        return 1;
    }

    $this->info("Restoring database snapshot from: {$path}");
    $this->comment("Target database: " . DB::connection()->getDriverName() . " -> " . DB::connection()->getDatabaseName());

    try {
        app(App\Services\SqlServerSnapshotService::class)->restoreFile(DB::connection(), $path);
        $this->info("Database snapshot successfully restored!");
        return 0;
    } catch (\Throwable $e) {
        $this->error("Restore failed: " . $e->getMessage());
        return 1;
    }
})->purpose('Restore application database from a JSON snapshot file');
