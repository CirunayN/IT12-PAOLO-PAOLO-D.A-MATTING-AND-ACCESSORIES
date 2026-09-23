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
}
