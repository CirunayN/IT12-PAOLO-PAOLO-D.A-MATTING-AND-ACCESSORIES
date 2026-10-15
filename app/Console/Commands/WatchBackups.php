<?php

namespace App\Console\Commands;

use App\Http\Controllers\BackupController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WatchBackups extends Command
{
    protected $signature = 'backup:watch {--once : Check the saved schedule once and exit}';

    protected $description = 'Watch the local device clock and run due database backups offline';

    public function handle(BackupController $backups): int
    {
        File::ensureDirectoryExists(storage_path('app'));
        $lock = fopen(storage_path('app/backup-worker.lock'), 'c');
        if (! $lock) {
            $this->error('Unable to start the backup worker.');
            return self::FAILURE;
        }

        try {
            // A second launcher waits here and takes over if the first exits.
            while (! flock($lock, LOCK_EX | LOCK_NB)) {
                if ($this->option('once')) return self::SUCCESS;
                sleep(1);
            }
            do {
                $backups->runScheduledBackup();
                if ($this->option('once')) break;
                sleep(1);
            } while (true);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return self::SUCCESS;
    }
}
