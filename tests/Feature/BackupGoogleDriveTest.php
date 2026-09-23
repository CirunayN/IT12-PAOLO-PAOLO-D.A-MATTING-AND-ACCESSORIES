<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BackupGoogleDriveTest extends TestCase
{
    use RefreshDatabase;

    protected string $settingsFile;
    protected ?string $originalSettings = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsFile = storage_path('app/backup_settings.json');
        if (File::exists($this->settingsFile)) {
            $this->originalSettings = File::get($this->settingsFile);
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalSettings !== null) {
            File::put($this->settingsFile, $this->originalSettings);
        } else {
            File::delete($this->settingsFile);
        }
        parent::tearDown();
    }

    public function test_google_drive_upload_success_when_enabled(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        // Enable Google Drive in settings
        $settings = [
            'backup_mode' => 'manual',
            'frequency' => '1_day',
            'retention' => 'keep_all',
            'storage_path' => storage_path('app/test_backups'),
            'last_backup_at' => null,
            'gdrive_enabled' => true,
            'gdrive_folder_id' => 'test_folder_id',
            'gdrive_email' => 'admin@test.com',
        ];
        File::ensureDirectoryExists(dirname($this->settingsFile));
        File::put($this->settingsFile, json_encode($settings));

        // Mock Google Apps Script endpoint
        Http::fake([
            '*script.google.com*' => Http::response([
                'success' => true,
                'fileId' => 'google-drive-mock-file-id-abc',
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post('/backup/create');

        $response->assertRedirect(route('backup.index'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Google Drive upload completed successfully', session('success'));

        // Clean up test backups
        File::deleteDirectory(storage_path('app/test_backups'));
    }

    public function test_google_drive_upload_failure_shows_fallback_message(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        // Enable Google Drive in settings
        $settings = [
            'backup_mode' => 'manual',
            'frequency' => '1_day',
            'retention' => 'keep_all',
            'storage_path' => storage_path('app/test_backups'),
            'last_backup_at' => null,
            'gdrive_enabled' => true,
            'gdrive_folder_id' => 'test_folder_id',
            'gdrive_email' => 'admin@test.com',
        ];
        File::ensureDirectoryExists(dirname($this->settingsFile));
        File::put($this->settingsFile, json_encode($settings));

        // Mock Google Apps Script failure
        Http::fake([
            '*script.google.com*' => Http::response([
                'success' => false,
                'error' => 'Permission denied or invalid secret',
            ], 500),
        ]);

        $response = $this->actingAs($admin)->post('/backup/create');

        $response->assertRedirect(route('backup.index'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Google Drive upload failed. The local backup is still available', session('success'));

        // Clean up test backups
        File::deleteDirectory(storage_path('app/test_backups'));
    }

    public function test_google_drive_not_called_when_disabled(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        // Disable Google Drive in settings
        $settings = [
            'backup_mode' => 'manual',
            'frequency' => '1_day',
            'retention' => 'keep_all',
            'storage_path' => storage_path('app/test_backups'),
            'last_backup_at' => null,
            'gdrive_enabled' => false,
            'gdrive_folder_id' => '',
            'gdrive_email' => '',
        ];
        File::ensureDirectoryExists(dirname($this->settingsFile));
        File::put($this->settingsFile, json_encode($settings));

        Http::fake();

        $response = $this->actingAs($admin)->post('/backup/create');

        $response->assertRedirect(route('backup.index'));
        Http::assertNothingSent();

        // Clean up test backups
        File::deleteDirectory(storage_path('app/test_backups'));
    }

    public function test_delete_backup_moves_file_to_archive_and_can_be_recovered(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        $testDir = storage_path('app/test_backups_archive');
        File::ensureDirectoryExists($testDir);

        $settings = [
            'backup_mode' => 'manual',
            'frequency' => '1_day',
            'retention' => 'keep_all',
            'storage_path' => $testDir,
            'last_backup_at' => null,
            'gdrive_enabled' => false,
            'gdrive_folder_id' => '',
            'gdrive_email' => '',
        ];
        File::ensureDirectoryExists(dirname($this->settingsFile));
        File::put($this->settingsFile, json_encode($settings));

        // Create a dummy backup file in active directory
        $dummyFile = 'test_backup_snapshot.sql';
        File::put($testDir . '/' . $dummyFile, '-- Test backup content');

        $this->assertTrue(File::exists($testDir . '/' . $dummyFile));

        // 1. Delete (Archive)
        $resDelete = $this->actingAs($admin)->post('/backup/delete', [
            'filename' => $dummyFile,
        ]);
        $resDelete->assertRedirect(route('backup.index'));
        $resDelete->assertSessionHas('success');

        // File should NO LONGER be in active directory, but MUST exist in archive
        $this->assertFalse(File::exists($testDir . '/' . $dummyFile));
        $this->assertTrue(File::exists($testDir . '/archive/' . $dummyFile));

        // 2. Recover back to active
        $resRecover = $this->actingAs($admin)->post('/backup/recover', [
            'filename' => $dummyFile,
        ]);
        $resRecover->assertRedirect(route('backup.index'));
        $resRecover->assertSessionHas('success');

        // File should be back in active directory
        $this->assertTrue(File::exists($testDir . '/' . $dummyFile));
        $this->assertFalse(File::exists($testDir . '/archive/' . $dummyFile));

        // Clean up
        File::deleteDirectory($testDir);
    }

    public function test_archived_backup_can_be_permanently_purged(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'Admin',
        ]);

        $testDir = storage_path('app/test_backups_purge');
        $archiveDir = $testDir . '/archive';
        File::ensureDirectoryExists($archiveDir);

        $settings = [
            'backup_mode' => 'manual',
            'frequency' => '1_day',
            'retention' => 'keep_all',
            'storage_path' => $testDir,
            'last_backup_at' => null,
            'gdrive_enabled' => false,
            'gdrive_folder_id' => '',
            'gdrive_email' => '',
        ];
        File::ensureDirectoryExists(dirname($this->settingsFile));
        File::put($this->settingsFile, json_encode($settings));

        $dummyFile = 'purge_target.sql';
        File::put($archiveDir . '/' . $dummyFile, '-- Purge content');
        $this->assertTrue(File::exists($archiveDir . '/' . $dummyFile));

        // Purge
        $resPurge = $this->actingAs($admin)->post('/backup/purge', [
            'filename' => $dummyFile,
        ]);
        $resPurge->assertRedirect(route('backup.index'));
        $resPurge->assertSessionHas('success');

        // File must be completely gone
        $this->assertFalse(File::exists($archiveDir . '/' . $dummyFile));

        // Clean up
        File::deleteDirectory($testDir);
    }
}
