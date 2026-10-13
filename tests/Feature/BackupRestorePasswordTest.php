<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SqlServerSnapshotService;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BackupRestorePasswordTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Restore-test-password';

    private User $admin;

    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryRoot = sys_get_temp_dir().'/paolo-restore-tests-'.Str::uuid();
        $this->app->useStoragePath($this->temporaryRoot.'/storage');
        File::ensureDirectoryExists(storage_path('app/backups/archive'));
        File::put(storage_path('app/backup_settings.json'), json_encode([
            'storage_path' => storage_path('app/backups'),
            'backup_mode' => 'manual',
        ]));
        File::put(storage_path('app/backups/backup.json'), '{}');
        File::put(storage_path('app/backups/archive/archived.json'), '{}');
        $this->admin = User::factory()->create([
            'role' => 'Admin', 'is_active' => true, 'password' => self::PASSWORD,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->temporaryRoot)) {
            $resolved = realpath($this->temporaryRoot);
            if ($resolved && str_starts_with($resolved, realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'paolo-restore-tests-')) {
                File::deleteDirectory($resolved);
            }
        }
        parent::tearDown();
    }

    private function payload(string $source, ?string $password): array
    {
        $payload = $source === 'upload'
            ? ['backup_file' => UploadedFile::fake()->createWithContent('backup.json', '{}')]
            : ['existing_file' => $source === 'archive' ? 'archived.json' : 'backup.json'];
        if ($password !== null) {
            $payload['current_password'] = $password;
        }

        return $payload;
    }

    public static function unauthorizedRestores(): array
    {
        $cases = [];
        foreach (['active', 'archive', 'upload'] as $source) {
            $cases[$source.' without password'] = [$source, null];
            $cases[$source.' with wrong password'] = [$source, 'Wrong-password'];
        }

        return $cases;
    }

    #[DataProvider('unauthorizedRestores')]
    public function test_invalid_password_blocks_restore_before_any_file_or_database_changes(string $source, ?string $password): void
    {
        $this->mock(SqlServerSnapshotService::class, fn ($mock) => $mock->shouldNotReceive('restoreFile'));

        $this->actingAs($this->admin)->from(route('backup.index'))
            ->post(route('backup.restore'), $this->payload($source, $password))
            ->assertRedirect(route('backup.index'))
            ->assertSessionHasErrorsIn('restoreBackup', 'current_password')
            ->assertSessionMissing('_old_input.current_password');

        $this->assertDirectoryDoesNotExist(storage_path('app/temp'));
        $this->assertSame('{}', File::get(storage_path('app/backups/backup.json')));
        $this->assertSame('{}', File::get(storage_path('app/backups/archive/archived.json')));
        $this->assertDatabaseCount('users', 1);
    }

    public static function authorizedRestores(): array
    {
        return [['active'], ['archive'], ['upload']];
    }

    #[DataProvider('authorizedRestores')]
    public function test_correct_admin_password_allows_each_restore_source(string $source): void
    {
        $this->mock(SqlServerSnapshotService::class, function ($mock) {
            $mock->shouldReceive('restoreFile')->once()->withArgs(function (Connection $connection, string $path) {
                return File::exists($path) && File::get($path) === '{}';
            });
        });

        $this->actingAs($this->admin)->post(route('backup.restore'), $this->payload($source, self::PASSWORD))
            ->assertRedirect(route('backup.index'))->assertSessionHasNoErrors()->assertSessionHas('success');
    }

    public function test_another_admin_password_cannot_authorize_the_signed_in_admin(): void
    {
        User::factory()->create(['role' => 'Admin', 'password' => 'Other-admin-password']);
        $this->mock(SqlServerSnapshotService::class, fn ($mock) => $mock->shouldNotReceive('restoreFile'));

        $this->actingAs($this->admin)->post(route('backup.restore'), $this->payload('active', 'Other-admin-password'))
            ->assertSessionHasErrorsIn('restoreBackup', 'current_password');
    }

    public function test_previous_password_confirmation_does_not_authorize_the_next_restore(): void
    {
        $this->mock(SqlServerSnapshotService::class, fn ($mock) => $mock->shouldReceive('restoreFile')->once());
        $this->actingAs($this->admin)->post(route('backup.restore'), $this->payload('active', self::PASSWORD))
            ->assertSessionHas('success');
        $this->post(route('backup.restore'), $this->payload('archive', null))
            ->assertSessionHasErrorsIn('restoreBackup', 'current_password');
    }

    public function test_employee_cannot_restore_even_with_the_admin_password(): void
    {
        $employee = User::factory()->create(['role' => 'Employee', 'is_active' => true]);
        $this->mock(SqlServerSnapshotService::class, fn ($mock) => $mock->shouldNotReceive('restoreFile'));
        $this->actingAs($employee)->post(route('backup.restore'), $this->payload('active', self::PASSWORD))
            ->assertForbidden();
    }

    public function test_password_error_is_shown_when_the_selected_backup_modal_reopens(): void
    {
        $this->mock(SqlServerSnapshotService::class, fn ($mock) => $mock->shouldNotReceive('restoreFile'));
        $this->actingAs($this->admin)->from(route('backup.index'))
            ->post(route('backup.restore'), $this->payload('active', 'Wrong-password'))
            ->assertSessionHasErrorsIn('restoreBackup', 'current_password')
            ->assertSessionHas('_old_input.existing_file', 'backup.json');
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('backup.index'))->assertOk()
            ->assertSee('The administrator password is incorrect.')
            ->assertSee('promptRestoreBackup("backup.json",', false)
            ->assertDontSee('Wrong-password');
    }
}
