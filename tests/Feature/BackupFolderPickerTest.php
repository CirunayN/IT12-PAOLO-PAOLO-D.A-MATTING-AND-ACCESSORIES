<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackupFolderPickerTest extends TestCase
{
    use RefreshDatabase;

    private string $testPath;

    private string $selectedPath;

    private string $settingsPath;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPath = sys_get_temp_dir().'/paolo-backup-folder-tests-'.Str::uuid();
        foreach (['/selected/subfolder', '/empty', '/storage/app'] as $directory) {
            File::ensureDirectoryExists($this->testPath.$directory);
        }
        $this->selectedPath = realpath($this->testPath.'/selected');
        $this->app->useStoragePath($this->testPath.'/storage');
        $this->settingsPath = storage_path('app/backup_settings.json');
        File::put($this->selectedPath.'/notes.txt', 'This file must not appear in the folder picker.');
        File::put($this->settingsPath, json_encode([
            'backup_mode' => 'automatic', 'frequency' => '1_day', 'retention' => '1_month',
            'storage_path' => $this->selectedPath, 'last_backup_at' => '2026-10-01 10:00:00',
        ]));
        $this->admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->testPath)) {
                File::delete([$this->settingsPath, $this->selectedPath.'/notes.txt']);
                foreach (['/selected/archive', '/selected/subfolder', '/selected', '/empty', '/storage/app', '/storage', ''] as $directory) {
                    if (is_dir($this->testPath.$directory)) {
                        rmdir($this->testPath.$directory);
                    }
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_admin_can_browse_available_drives(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('backup.folders'))
            ->assertOk()->assertJsonPath('path', null)->assertJsonPath('can_select', false);
        $this->assertNotEmpty($response->json('folders'));
        foreach ($response->json('folders') as $folder) {
            $this->assertDirectoryExists($folder['path']);
        }
        $root = $response->json('folders.0.path');
        $listing = $this->getJson(route('backup.folders', ['path' => $root]))->assertOk();
        foreach ($listing->json('folders') as $folder) {
            $this->assertDirectoryExists($folder['path']);
            $this->assertTrue(is_readable($folder['path']));
        }
    }

    public function test_browsing_returns_only_subfolders_and_does_not_save_settings(): void
    {
        $before = File::get($this->settingsPath);
        $response = $this->actingAs($this->admin)->getJson(route('backup.folders', ['path' => $this->selectedPath]))
            ->assertOk()->assertJsonPath('path', $this->selectedPath)
            ->assertJsonPath('parent', realpath($this->testPath))
            ->assertJsonPath('can_select', true)->assertJsonCount(1, 'folders')
            ->assertJsonPath('folders.0.name', 'subfolder');
        $this->assertDirectoryExists($response->json('folders.0.path'));
        $this->assertSame($before, File::get($this->settingsPath));
    }

    public function test_empty_folders_can_be_selected(): void
    {
        $this->actingAs($this->admin)->getJson(route('backup.folders', ['path' => $this->testPath.'/empty']))
            ->assertOk()->assertJsonPath('can_select', true)->assertJsonPath('folders', []);
    }

    public function test_missing_folders_files_and_relative_paths_are_rejected(): void
    {
        foreach ([$this->testPath.'/missing', $this->selectedPath.'/notes.txt', 'storage'] as $path) {
            $this->actingAs($this->admin)->getJson(route('backup.folders', ['path' => $path]))
                ->assertUnprocessable()->assertJsonStructure(['message']);
        }
    }

    public function test_guests_and_employees_cannot_browse_server_folders(): void
    {
        $this->getJson(route('backup.folders'))->assertUnauthorized();
        $employee = User::factory()->create(['role' => 'Cashier', 'is_active' => true]);
        $this->actingAs($employee)->getJson(route('backup.folders'))->assertForbidden();
    }

    public function test_saving_a_chosen_folder_preserves_backup_history(): void
    {
        $this->actingAs($this->admin)->post(route('backup.settings'), [
            'backup_mode' => 'manual', 'frequency' => '1_week', 'retention' => 'keep_all',
            'storage_path' => $this->testPath.'/empty',
        ])->assertRedirect(route('backup.index'))->assertSessionHasNoErrors();
        $settings = json_decode(File::get($this->settingsPath), true);
        $this->assertSame(realpath($this->testPath.'/empty'), $settings['storage_path']);
        $this->assertSame('2026-10-01 10:00:00', $settings['last_backup_at']);
        $this->assertSame('manual', $settings['backup_mode']);
    }

    public function test_invalid_selection_preserves_existing_settings(): void
    {
        $before = File::get($this->settingsPath);
        $this->actingAs($this->admin)->postJson(route('backup.settings'), [
            'backup_mode' => 'manual', 'frequency' => '1_week', 'retention' => 'keep_all',
            'storage_path' => $this->testPath.'/missing',
        ])->assertUnprocessable()->assertJsonValidationErrors('storage_path');
        $this->assertSame($before, File::get($this->settingsPath));
        $this->assertDirectoryDoesNotExist($this->testPath.'/missing');
    }

    public function test_existing_disconnected_destination_can_be_kept_when_updating_other_settings(): void
    {
        $path = $this->testPath.'/disconnected';
        File::put($this->settingsPath, json_encode(['storage_path' => $path]));
        $this->actingAs($this->admin)->post(route('backup.settings'), [
            'backup_mode' => 'manual', 'frequency' => '1_week', 'retention' => 'keep_all',
            'storage_path' => $path,
        ])->assertRedirect(route('backup.index'))->assertSessionHasNoErrors();
        $this->assertDirectoryDoesNotExist($path);
        $this->assertSame($path, json_decode(File::get($this->settingsPath), true)['storage_path']);
    }

    public function test_backup_page_uses_a_folder_picker_and_read_only_destination(): void
    {
        $this->actingAs($this->admin)->get(route('backup.index'))->assertOk()
            ->assertSee('Choose Folder')->assertSee('Use This Folder')
            ->assertSee('Selected Folder Active')
            ->assertSee('id="backupStoragePath" readonly', false)
            ->assertDontSee('External Drive / Folder Path');
    }
}
