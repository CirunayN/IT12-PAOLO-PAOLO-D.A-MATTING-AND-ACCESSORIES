<?php

namespace App\Console\Commands;

use App\Services\LaptopAdminTransfer;
use Illuminate\Console\Command;

class ExportLaptopAdmin extends Command
{
    protected $signature = 'app:export-laptop-admin {--username= : Administrator login to retain}';
    protected $description = 'Prepare one administrator login for a fresh laptop, without exporting business records';

    public function handle(LaptopAdminTransfer $transfer): int
    {
        try {
            $transfer->export($this->option('username'));
        } catch (\Throwable $error) {
            $this->error($error instanceof \InvalidArgumentException ? $error->getMessage() : 'Could not export the administrator. Check local database access and writable storage.');
            return self::FAILURE;
        }
        $this->info('One administrator login prepared. No business records, other accounts, sessions or recovery codes were exported.');
        $this->info('Keep the deployment ZIP private: it contains the administrator password hash.');
        return self::SUCCESS;
    }
}
