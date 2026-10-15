<?php

namespace Tests\Feature;

use App\Http\Controllers\BackupController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Status;
use App\Models\StockIn;
use App\Models\User;
use App\Services\DeviceClock;
use App\Services\ExpiryAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineBackupAndExpiryTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryRoot;

    private User $admin;

    private Product $product;

    private DeviceClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryRoot = sys_get_temp_dir().'/paolo-clock-expiry-'.Str::uuid();
        File::ensureDirectoryExists($this->temporaryRoot.'/storage/app');
        File::ensureDirectoryExists($this->temporaryRoot.'/backups');
        $this->app->useStoragePath($this->temporaryRoot.'/storage');
        Http::preventStrayRequests();
        $this->clock = new class extends DeviceClock {
            public Carbon $time;

            public function now(): Carbon { return $this->time->copy(); }
        };
        // This date differs from Manila, proving expiry uses the device date.
        $this->clock->time = Carbon::parse('2026-10-15 23:59:00', '-07:00');
        $this->app->instance(DeviceClock::class, $this->clock);
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->product = Product::create(['Name' => 'Engine oil',
            'Category_ID' => Category::create(['Name' => 'Automotive'])->ID,
            'Status_ID' => Status::create(['Name' => 'Active'])->ID]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        if (isset($this->temporaryRoot)) {
            $resolved = realpath($this->temporaryRoot);
            if ($resolved && str_starts_with($resolved, realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'paolo-clock-expiry-')) {
                File::deleteDirectory($resolved);
            }
        }
        parent::tearDown();
    }

    private function batch(int $days, array $overrides = []): StockIn
    {
        return StockIn::create(array_replace([
            'Product_ID' => $this->product->ID, 'User_ID' => $this->admin->id,
            'Quantity' => 10, 'Remaining_Quantity' => 6, 'Condition' => 'Good',
            'Cost_Price' => 100, 'Retail_Price' => 150, 'Has_Expiration' => true,
            'Expiration_Date' => $this->clock->now()->addDays($days)->toDateString(),
        ], $overrides));
    }

    private function settings(array $overrides = []): array
    {
        $settings = array_replace(['backup_mode' => 'automatic', 'frequency' => '1_day',
            'backup_time' => '18:30', 'last_automatic_backup_at' => null, 'last_automatic_schedule' => null,
            'last_backup_at' => null, 'storage_path' => $this->temporaryRoot.'/backups',
            'retention' => 'keep_all', 'gdrive_enabled' => false], $overrides);
        File::put(storage_path('app/backup_settings.json'), json_encode($settings));
        return $settings;
    }

    private function backupController(): BackupController
    {
        $controller = new class extends BackupController {
            public int $dumps = 0;
            public bool $fail = false;

            protected function performDatabaseDump(string $targetFile): bool
            {
                $this->dumps++;
                if ($this->fail) throw new \RuntimeException('Isolated test dump failure');
                File::put($targetFile, 'isolated test backup');
                return true;
            }
        };
        $this->app->instance(BackupController::class, $controller);
        return $controller;
    }

    public function test_expiry_alerts_include_today_and_day_thirty_but_exclude_unsellable_batches(): void
    {
        $today = $this->batch(0);
        $dayThirty = $this->batch(30);
        $this->batch(-1);
        $this->batch(31);
        $this->batch(5, ['Remaining_Quantity' => 0]);
        $this->batch(5, ['Condition' => 'Damaged']);
        $this->batch(5, ['Condition' => 'Defective']);
        $this->batch(5, ['Has_Expiration' => false]);
        $archived = Product::create(['Name' => 'Archived item', 'Category_ID' => $this->product->Category_ID,
            'Status_ID' => Status::create(['Name' => 'Archived'])->ID]);
        $this->batch(5, ['Product_ID' => $archived->ID]);

        $this->actingAs($this->admin)->getJson(route('notifications.expiry'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('count', 2)->assertJsonPath('as_of', '2026-10-15')
            ->assertJsonPath('through', '2026-11-14')
            ->assertJsonPath('items.0.batch', 'SI-'.$today->ID)->assertJsonPath('items.0.days_remaining', 0)
            ->assertJsonPath('items.1.batch', 'SI-'.$dayThirty->ID)->assertJsonPath('items.1.days_remaining', 30);
    }

    public function test_expiry_alerts_refresh_after_sales_edits_and_device_midnight(): void
    {
        $today = $this->batch(0);
        $future = $this->batch(31);
        $this->assertSame(1, app(ExpiryAlerts::class)->summary()['count']);
        $today->update(['Remaining_Quantity' => 0]);
        $this->assertSame(0, app(ExpiryAlerts::class)->summary()['count']);
        $this->clock->time->addMinutes(2);
        $summary = app(ExpiryAlerts::class)->summary();
        $this->assertSame('2026-10-16', $summary['as_of']);
        $this->assertSame('SI-'.$future->ID, $summary['items'][0]['batch']);
        $this->assertSame(30, $summary['items'][0]['days_remaining']);
    }

    public function test_notification_count_covers_all_batches_while_list_is_limited_and_safe(): void
    {
        $this->product->update(['Name' => '<script>alert(1)</script>']);
        for ($i = 0; $i < 22; $i++) $this->batch($i);
        $summary = app(ExpiryAlerts::class)->summary();
        $this->assertSame(22, $summary['count']);
        $this->assertCount(20, $summary['items']);
        $this->actingAs($this->admin)->get(route('settings.account'))->assertOk()
            ->assertSee('expiryNotificationButton', false)->assertSee('Expiring Within 30 Days')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Current laptop time')->assertDontSee("timeZone: 'Asia/Manila'", false);
    }

    public function test_cashiers_and_guests_cannot_read_administrator_expiry_notifications(): void
    {
        $this->getJson(route('notifications.expiry'))->assertUnauthorized();
        $cashier = User::factory()->create(['role' => 'Cashier']);
        $this->actingAs($cashier)->getJson(route('notifications.expiry'))->assertForbidden();
        $this->get(route('settings.account'))->assertOk()->assertDontSee('expiryNotificationButton', false);
    }

    public function test_background_command_runs_at_device_time_once_without_browser_requests(): void
    {
        $this->settings();
        $controller = $this->backupController();
        $this->clock->time = Carbon::parse('2026-10-15 18:29:59', '-07:00');
        $this->assertSame(0, Artisan::call('backup:watch', ['--once' => true]));
        $this->assertSame(0, $controller->dumps);
        $this->clock->time->addSecond();
        $this->assertSame(0, Artisan::call('backup:watch', ['--once' => true]));
        $this->assertSame(1, $controller->dumps);
        $this->clock->time->addHours(2);
        Artisan::call('backup:watch', ['--once' => true]);
        $this->assertSame(1, $controller->dumps);
        $saved = json_decode(File::get(storage_path('app/backup_settings.json')), true);
        $this->assertSame('2026-10-15T18:30:00-07:00', $saved['last_automatic_backup_at']);
        $this->assertSame('1_day|18:30', $saved['last_automatic_schedule']);
        $this->assertCount(1, File::files($this->temporaryRoot.'/backups'));
    }

    public function test_failed_backups_retry_and_manual_mode_does_not_run(): void
    {
        $this->settings();
        $controller = $this->backupController();
        $controller->fail = true;
        $this->assertFalse($controller->runScheduledBackup());
        $this->assertNull(json_decode(File::get(storage_path('app/backup_settings.json')), true)['last_automatic_backup_at']);
        $controller->fail = false;
        $this->assertTrue($controller->runScheduledBackup());
        $this->settings(['backup_mode' => 'manual']);
        $this->assertFalse($controller->runScheduledBackup());
        $this->assertSame(2, $controller->dumps);
    }

    public function test_changing_backup_time_can_run_a_new_schedule_today(): void
    {
        $this->settings(['backup_time' => '17:00', 'last_automatic_backup_at' => '2026-10-15T17:00:00-07:00']);
        $this->clock->time = Carbon::parse('2026-10-15 18:29:00', '-07:00');
        $controller = $this->backupController();
        $this->actingAs($this->admin)->post(route('backup.settings'), ['backup_mode' => 'automatic',
            'frequency' => '1_day', 'backup_time' => '18:30', 'retention' => 'keep_all',
            'storage_path' => $this->temporaryRoot.'/backups'])->assertSessionHasNoErrors();
        $this->assertFalse($controller->runScheduledBackup());
        $this->clock->time->addMinute();
        $this->assertTrue($controller->runScheduledBackup());
        $this->assertFalse($controller->runScheduledBackup());
        $this->actingAs($this->admin)->get(route('backup.index'))->assertOk()
            ->assertSee('Automatic Backup Time (Laptop time)')->assertSee('data-device-clock', false);
    }

    public function test_device_clock_reads_system_time_using_the_device_offset(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 01:30:00', 'UTC'));
        $clock = new class extends DeviceClock {
            protected function readOffset(): string { return '-07:00'; }
        };
        $this->assertSame('2026-10-15T18:30:00-07:00', $clock->now()->toIso8601String());
        Carbon::setTestNow(Carbon::parse('2026-10-16 01:31:00', 'UTC'));
        $this->assertSame('2026-10-15T18:31:00-07:00', $clock->now()->toIso8601String());
    }
}
