<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use App\Services\LaptopAdminTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaptopInitializationTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/paolo-laptop-test-'.Str::uuid();
        File::ensureDirectoryExists($this->root.'/storage/app');
        $this->app->useStoragePath($this->root.'/storage');
    }

    protected function tearDown(): void
    {
        $resolved = isset($this->root) ? realpath($this->root) : false;
        if ($resolved && str_starts_with($resolved, realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'paolo-laptop-test-')) File::deleteDirectory($resolved);
        parent::tearDown();
    }

    public function test_empty_installation_creates_a_private_owner_account_and_no_demo_records(): void
    {
        $this->artisan('app:initialize-laptop')
            ->expectsQuestion('Owner name', 'Store Owner')->expectsQuestion('Login username', 'shop_owner')
            ->expectsQuestion('Account email', 'owner@example.test')
            ->expectsQuestion('Password (at least 8 characters)', 'UniqueOwner2026!')
            ->expectsQuestion('Confirm password', 'UniqueOwner2026!')->assertSuccessful();
        $owner = User::sole();
        $this->assertSame('Admin', $owner->role);
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check('UniqueOwner2026!', $owner->password));
        $this->assertDatabaseCount('tbl_product', 0);
        $this->assertDatabaseCount('tbl_sale', 0);
        $this->assertSame(['Cash', 'GCash'], PaymentMethod::orderBy('Name')->pluck('Name')->all());
        $this->assertSame(4, Status::count());
        $settings = json_decode(File::get(storage_path('app/backup_settings.json')), true);
        $this->assertSame(storage_path('app/backups'), $settings['storage_path']);
        $this->assertSame('18:00', $settings['backup_time']);
        $this->assertFalse($settings['gdrive_enabled']);
    }

    public function test_rerunning_setup_preserves_accounts_passwords_and_backup_preferences(): void
    {
        $owner = User::factory()->create(['role' => 'Admin']);
        $hash = $owner->password;
        Status::create(['Name' => 'Active']);
        PaymentMethod::create(['Name' => 'Cash']);
        $settings = '{"backup_mode":"manual","backup_time":"12:34","storage_path":"existing-folder"}';
        File::put(storage_path('app/backup_settings.json'), $settings);
        $this->artisan('app:initialize-laptop')->assertSuccessful();
        $this->artisan('app:initialize-laptop')->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertSame($hash, $owner->fresh()->password);
        $this->assertSame($settings, File::get(storage_path('app/backup_settings.json')));
        $this->assertSame(4, Status::count());
        $this->assertSame(2, PaymentMethod::count());
    }

    public function test_invalid_password_confirmation_does_not_create_an_account_or_settings(): void
    {
        $this->artisan('app:initialize-laptop')
            ->expectsQuestion('Owner name', 'Store Owner')->expectsQuestion('Login username', 'admin')
            ->expectsQuestion('Account email', 'owner@example.test')
            ->expectsQuestion('Password (at least 8 characters)', 'UniqueOwner2026!')
            ->expectsQuestion('Confirm password', 'different')->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->assertFileDoesNotExist(storage_path('app/backup_settings.json'));
    }

    public function test_noninteractive_empty_setup_does_not_create_a_default_password(): void
    {
        $this->artisan('app:initialize-laptop', ['--no-interaction' => true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    private function prepareAdminTransfer(?string $hash = null): string
    {
        $hash ??= Hash::make('OriginalAdmin2026!');
        $path = app(LaptopAdminTransfer::class)->path();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(['format'=>'paolo-laptop-admin','version'=>1,'account'=>[
            'name'=>'Retained Owner','username'=>'admin','email'=>'owner@example.test','password_hash'=>$hash,
        ]]));
        return $hash;
    }

    public function test_export_keeps_only_one_admin_login_and_leaves_source_data_unchanged(): void
    {
        $owner = User::factory()->create(['role'=>'Admin','is_active'=>true,'remember_token'=>'private-remember-token']);
        User::factory()->create(['role'=>'Cashier']);
        DB::table('tbl_category')->insert(['Name'=>'Old category']);
        $this->artisan('app:export-laptop-admin')->assertSuccessful();
        $document = json_decode(File::get(app(LaptopAdminTransfer::class)->path()), true);
        $this->assertSame(['format','version','account'], array_keys($document));
        $this->assertSame(['name','username','email','password_hash'], array_keys($document['account']));
        $this->assertSame($owner->getRawOriginal('password'), $document['account']['password_hash']);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('tbl_category', 1);
        $this->assertSame('private-remember-token', $owner->fresh()->remember_token);
    }

    public function test_fresh_installation_retains_admin_password_and_has_no_business_records(): void
    {
        $hash = $this->prepareAdminTransfer();
        $this->artisan('app:initialize-laptop', ['--no-interaction'=>true])->assertSuccessful();
        $owner = User::sole();
        $this->assertSame('admin', $owner->username);
        $this->assertSame($hash, $owner->getRawOriginal('password'));
        $this->assertTrue(Hash::check('OriginalAdmin2026!', $owner->password));
        $this->assertTrue($owner->isAdmin());
        $this->assertNull($owner->remember_token);
        foreach (['tbl_category','tbl_product','tbl_stock_in','tbl_sale','tbl_sold_item','account_recovery_codes','sessions','password_reset_tokens'] as $table) $this->assertDatabaseCount($table, 0);
        $this->assertFileDoesNotExist(app(LaptopAdminTransfer::class)->path());
        $this->assertTrue(app(LaptopAdminTransfer::class)->wasInitialized());
    }

    public function test_rerun_keeps_new_data_and_does_not_reapply_the_old_password(): void
    {
        $oldHash = $this->prepareAdminTransfer();
        $this->artisan('app:initialize-laptop', ['--no-interaction'=>true])->assertSuccessful();
        $owner = User::sole();
        $owner->update(['password'=>Hash::make('NewStorePassword2026!')]);
        User::factory()->create(['role'=>'Cashier']);
        DB::table('tbl_category')->insert(['Name'=>'New shop category']);
        $this->prepareAdminTransfer($oldHash);
        $this->artisan('app:initialize-laptop', ['--no-interaction'=>true])->assertSuccessful();
        $this->assertTrue(Hash::check('NewStorePassword2026!', $owner->fresh()->password));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('tbl_category', 1);
        $this->assertFileDoesNotExist(app(LaptopAdminTransfer::class)->path());
    }

    public function test_initial_transfer_refuses_existing_business_records_or_other_accounts(): void
    {
        $this->prepareAdminTransfer();
        DB::table('tbl_category')->insert(['Name'=>'Old records']);
        $this->artisan('app:initialize-laptop', ['--no-interaction'=>true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('tbl_category', 1);
        DB::table('tbl_category')->delete();
        $other = User::factory()->create(['role'=>'Cashier']);
        $this->artisan('app:initialize-laptop', ['--no-interaction'=>true])->assertFailed();
        $this->assertSame($other->id, User::sole()->id);
        $this->assertFalse(app(LaptopAdminTransfer::class)->wasInitialized());
    }

    public function test_plaintext_or_malformed_transfer_does_not_create_an_account(): void
    {
        $this->prepareAdminTransfer('plaintext-password');
        $this->artisan('app:initialize-laptop', ['--no-interaction'=>true])->assertFailed();
        File::put(app(LaptopAdminTransfer::class)->path(), '{broken');
        $this->artisan('app:initialize-laptop', ['--no-interaction'=>true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->assertFalse(app(LaptopAdminTransfer::class)->wasInitialized());
    }

    public function test_multiple_admins_require_an_explicit_selection(): void
    {
        User::factory()->create(['role'=>'Admin','is_active'=>true,'username'=>'first_admin']);
        User::factory()->create(['role'=>'Admin','is_active'=>true,'username'=>'second_admin']);
        $this->artisan('app:export-laptop-admin')->assertFailed();
        $this->assertFileDoesNotExist(app(LaptopAdminTransfer::class)->path());
        $this->artisan('app:export-laptop-admin', ['--username'=>'second_admin'])->assertSuccessful();
        $this->assertSame('second_admin', app(LaptopAdminTransfer::class)->read()['username']);
    }
}
