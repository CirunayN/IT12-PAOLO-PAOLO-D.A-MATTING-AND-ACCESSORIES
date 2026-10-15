<?php

namespace App\Console\Commands;

use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use App\Services\LaptopAdminTransfer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InitializeLaptop extends Command
{
    protected $signature = 'app:initialize-laptop';

    protected $description = 'Prepare a fresh laptop with the retained administrator login or a first owner';

    public function handle(LaptopAdminTransfer $transfer): int
    {
        if (File::exists($transfer->path())) {
            try {
                $account = $transfer->read();
                if (! $transfer->wasInitialized() || ! User::exists()) {
                    if ($transfer->hasBusinessRecords()) throw new \InvalidArgumentException('This database already contains business records. Choose a new empty database for deployment. No records were deleted.');
                    DB::transaction(function () use ($account) {
                        if (User::exists()) {
                            $owner = User::first();
                            if (User::count() === 1 && $owner->isAdmin() && $owner->username === $account['username'] && hash_equals($owner->getRawOriginal('password'), $account['password_hash'])) return;
                            throw new \InvalidArgumentException('This database already has other accounts. Choose a new empty database. No accounts were changed.');
                        }
                        User::create(['name' => $account['name'], 'username' => $account['username'], 'email' => $account['email'],
                            'password' => $account['password_hash'], 'role' => 'Admin', 'is_active' => true, 'email_verified_at' => now()]);
                    });
                    $this->info('Administrator login retained. Inventory, sales, categories and employee accounts start empty.');
                }
            } catch (\Throwable $error) {
                $this->error($error instanceof \InvalidArgumentException ? $error->getMessage() : 'Could not import the administrator login. Check the transfer file and database configuration.');
                return self::FAILURE;
            }
        }
        if (! User::exists()) {
            if (! $this->input->isInteractive()) {
                $this->error('Run app:initialize-laptop interactively to create the first administrator.');
                return self::FAILURE;
            }
            $this->info('Create the owner account. No sample accounts, products, or sales will be added.');
            $details = [
                'name' => trim((string) $this->ask('Owner name')),
                'username' => trim((string) $this->ask('Login username', 'admin')),
                'email' => trim((string) $this->ask('Account email', 'owner@paolopaolo.local')),
                'password' => (string) $this->secret('Password (at least 8 characters)'),
                'password_confirmation' => (string) $this->secret('Confirm password'),
            ];
            $validator = Validator::make($details, [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_.-]+\z/'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) $this->error($error);
                return self::FAILURE;
            }
            $created = DB::transaction(function () use ($details) {
                if (User::exists()) return false;
                User::create([
                    'name' => $details['name'], 'username' => $details['username'], 'email' => $details['email'],
                    'password' => Hash::make($details['password']), 'role' => 'Admin',
                    'is_active' => true, 'email_verified_at' => now(),
                ]);
                return true;
            });
            $this->info($created ? 'Owner account created.' : 'Existing accounts preserved.');
        } else {
            $this->info('Existing accounts and passwords preserved.');
        }

        DB::transaction(function () {
            foreach (['Active', 'Low Stock', 'Out of Stock', 'Archived'] as $name) Status::firstOrCreate(['Name' => $name]);
            foreach (['Cash', 'GCash'] as $name) PaymentMethod::firstOrCreate(['Name' => $name]);
        });
        File::ensureDirectoryExists(storage_path('app/backups'));
        if (! File::exists(storage_path('app/backup_settings.json'))) {
            File::put(storage_path('app/backup_settings.json'), json_encode([
                'backup_mode' => 'automatic', 'frequency' => '1_day', 'backup_time' => '18:00',
                'retention' => '1_month', 'storage_path' => storage_path('app/backups'),
                'last_backup_at' => null, 'last_automatic_backup_at' => null,
                'last_automatic_schedule' => null, 'gdrive_enabled' => false,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        }
        $transfer->finish();
        $this->info('Installation data ready. After signing in, save a recovery code in My Account > Password.');
        $this->info('Automatic backup defaults to 6:00 PM laptop time. Change it in Database Backup if needed.');
        return self::SUCCESS;
    }
}
