<?php

namespace App\Http\Controllers;

use App\Services\SqlServerSnapshotService;
use App\Services\BackupDirectoryBrowser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use PDO;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

class BackupController extends Controller
{
    protected string $defaultBackupDir = 'E:\\PaoloPaolo_Backups';
    protected string $settingsFile;

    public function __construct()
    {
        $this->settingsFile = storage_path('app/backup_settings.json');
    }

    protected function getSettings(): array
    {
        if (File::exists($this->settingsFile)) {
            $data = json_decode(File::get($this->settingsFile), true);
            if (is_array($data)) {
                return array_merge($this->defaultSettings(), $data);
            }
        }
        return $this->defaultSettings();
    }

    protected function defaultSettings(): array
    {
        return [
            'backup_mode' => 'automatic',
            'frequency' => '1_day',
            'backup_time' => '18:00',
            'last_automatic_backup_at' => null,
            'retention' => '1_month',
            'storage_path' => $this->defaultBackupDir,
            'last_backup_at' => null,
            'gdrive_enabled' => false,
            'gdrive_folder_id' => '',
            'gdrive_email' => '',
        ];
    }

    protected function saveSettings(array $settings): void
    {
        $dir = dirname($this->settingsFile);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0777, true, true);
        }
        File::replace($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    protected function getBackupDirectory(array $settings): string
    {
        $path = !empty($settings['storage_path']) ? trim($settings['storage_path']) : $this->defaultBackupDir;

        // Verify if drive/path exists or can be created (e.g. external E:\ drive)
        try {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0777, true, true);
            }
            if (is_writable($path)) {
                return $path;
            }
        } catch (\Exception $e) {
            // Drive not available (e.g. E:\ not plugged in), fallback to local storage
        }

        $fallback = storage_path('app/backups');
        if (!File::exists($fallback)) {
            File::makeDirectory($fallback, 0777, true, true);
        }
        return $fallback;
    }

    protected function getArchiveDirectory(array $settings): string
    {
        $backupDir = $this->getBackupDirectory($settings);
        $archiveDir = rtrim($backupDir, '\\/') . DIRECTORY_SEPARATOR . 'archive';
        if (!File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0777, true, true);
        }
        return $archiveDir;
    }

    public function index()
    {
        $settings = $this->getSettings();
        $backupDir = $this->getBackupDirectory($settings);
        $archiveDir = $this->getArchiveDirectory($settings);

        $files = [];
        if (File::exists($backupDir)) {
            $allFiles = File::files($backupDir);
            foreach ($allFiles as $file) {
                if (in_array(strtolower($file->getExtension()), ['sql', 'gz', 'bak', 'json'])) {
                    $bytes = $file->getSize();
                    $size = $bytes >= 1048576 
                        ? number_format($bytes / 1048576, 2) . ' MB' 
                        : number_format($bytes / 1024, 2) . ' KB';

                    $files[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'size' => $size,
                        'bytes' => $bytes,
                        'created_at' => Carbon::createFromTimestamp($file->getMTime()),
                    ];
                }
            }

            // Sort newest first
            usort($files, fn($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);
        }

        // Load Archived / Recoverable Backups
        $archivedFiles = [];
        if (File::exists($archiveDir)) {
            $allArchived = File::files($archiveDir);
            foreach ($allArchived as $file) {
                if (in_array(strtolower($file->getExtension()), ['sql', 'gz', 'bak', 'json'])) {
                    $bytes = $file->getSize();
                    $size = $bytes >= 1048576 
                        ? number_format($bytes / 1048576, 2) . ' MB' 
                        : number_format($bytes / 1024, 2) . ' KB';

                    $archivedFiles[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'size' => $size,
                        'bytes' => $bytes,
                        'archived_at' => Carbon::createFromTimestamp($file->getMTime()),
                    ];
                }
            }

            usort($archivedFiles, fn($a, $b) => $b['archived_at']->timestamp <=> $a['archived_at']->timestamp);
        }

        return view('backup.index', compact('settings', 'files', 'archivedFiles', 'backupDir', 'archiveDir'));
    }

    private function withBackupLock(callable $action)
    {
        File::ensureDirectoryExists(storage_path('app'));
        $lock = fopen(storage_path('app/database-backup.lock'), 'c');
        if (!$lock) throw new \RuntimeException('Unable to open the backup lock.');
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) return null;
            return $action();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function createBackup()
    {
        try {
            $result = $this->withBackupLock(fn () => $this->writeBackup(false, Carbon::now('Asia/Manila')));
            if ($result === null) return redirect()->route('backup.index')->with('error', 'A backup is already running.');
            return redirect()->route('backup.index')->with('success', $result);
        } catch (\Throwable $exception) {
            Log::error('Database backup failed.', ['message' => $exception->getMessage()]);
            return redirect()->route('backup.index')->with('error', 'Backup failed. Check the destination folder and application log.');
        }
    }

    public function runScheduledBackup(?Carbon $now = null): bool
    {
        $now ??= Carbon::now('Asia/Manila');
        try {
            return (bool) $this->withBackupLock(function () use ($now) {
                if (!app(\App\Services\BackupSchedule::class)->isDue($this->getSettings(), $now)) return false;
                $this->writeBackup(true, $now);
                return true;
            });
        } catch (\Throwable $exception) {
            Log::error('Automatic database backup failed; it will be retried.', ['message' => $exception->getMessage()]);
            return false;
        }
    }

    private function writeBackup(bool $automatic, Carbon $now): string
    {
        $settings = $this->getSettings();
        $backupDir = $this->getBackupDirectory($settings);
        $extension = DB::connection()->getDriverName() === 'sqlsrv' ? 'json' : 'sql';
        $filename = 'backup_p7db_'.$now->format('Y-m-d_His').'_' . bin2hex(random_bytes(4)).'.'.$extension;
        $fullPath = rtrim($backupDir, '\\/').DIRECTORY_SEPARATOR.$filename;
        $temporary = $fullPath.'.partial';
        try {
            if (!$this->performDatabaseDump($temporary) || !File::exists($temporary) || File::size($temporary) === 0) {
                throw new \RuntimeException('Database dump did not complete.');
            }
            File::move($temporary, $fullPath);
        } finally {
            if (File::exists($temporary)) File::delete($temporary);
        }
        // Re-read to retain settings changed while the snapshot was being written.
        $current = $this->getSettings();
        $current['last_backup_at'] = $now->toIso8601String();
        if ($automatic) $current['last_automatic_backup_at'] = $now->toIso8601String();
        $this->saveSettings($current);
        $this->cleanupOldBackups($settings, $backupDir);
        $message = "Database backup '{$filename}' created successfully in {$backupDir}!";
        if (!empty($settings['gdrive_enabled'])) {
            $message .= $this->uploadToGoogleDrive($fullPath, $filename)
                ? ' Google Drive upload completed.' : ' Google Drive upload failed; the local backup is available.';
        }
        return $message;
    }

    protected function uploadToGoogleDrive(string $filePath, string $filename): bool
    {
        try {
            $url = config('services.google_drive.url', env('GOOGLE_DRIVE_WEB_APP_URL'));
            $secret = config('services.google_drive.secret', env('GOOGLE_DRIVE_UPLOAD_SECRET'));

            if (empty($url) || empty($secret)) {
                Log::warning('Google Drive backup is enabled but the Web App URL or secret is missing.');
                return false;
            }

            if (!File::exists($filePath)) {
                Log::error('Google Drive upload failed: backup file does not exist.', [
                    'file' => $filePath,
                ]);
                return false;
            }

            $content = base64_encode(File::get($filePath));

            $response = Http::withoutVerifying()
                ->timeout(120)
                ->asJson()
                ->post($url, [
                    'secret' => $secret,
                    'filename' => $filename,
                    'mimeType' => str_ends_with($filename, '.json') ? 'application/json' : 'application/sql',
                    'content' => $content,
                ]);

            if ($response->successful() && $response->json('success') === true) {
                Log::info('Backup uploaded to Google Drive successfully.', [
                    'filename' => $filename,
                    'file_id' => $response->json('fileId'),
                ]);

                return true;
            }

            Log::error('Google Drive backup upload failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;

        } catch (\Throwable $e) {
            Log::error('Google Drive backup upload exception.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function performDatabaseDump(string $targetFile): bool
    {
        try {
            if (DB::connection()->getDriverName() === 'sqlsrv') {
                app(SqlServerSnapshotService::class)->write(DB::connection(), $targetFile);
                return true;
            }

            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                $tables = $pdo->query("SELECT name, 'BASE TABLE' FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_NUM);
            } else {
                $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
            }

            $output = "-- ========================================================\n";
            $output .= "-- PAOLO PAOLO MANAGEMENT SYSTEM (P7)\n";
            $output .= "-- Database Dump: " . config('database.connections.mysql.database') . "\n";
            $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- ========================================================\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $row) {
                $tableName = $row[0];
                $tableType = $row[1] ?? 'BASE TABLE';

                if ($tableType === 'VIEW') {
                    continue; // Skip views, recreate structure after
                }

                if ($driver === 'sqlite') {
                    $createTableStmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = '{$tableName}'")->fetch(PDO::FETCH_ASSOC);
                    $createSql = $createTableStmt['sql'] ?? "CREATE TABLE `{$tableName}`;";
                    $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                    $output .= $createSql . ";\n\n";
                } else {
                    $createTableStmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`")->fetch(PDO::FETCH_ASSOC);
                    $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                    $output .= $createTableStmt['Create Table'] . ";\n\n";
                }

                $rows = $pdo->query("SELECT * FROM `{$tableName}`")->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) > 0) {
                    $columns = array_keys($rows[0]);
                    $colList = '`' . implode('`, `', $columns) . '`';

                    $output .= "INSERT INTO `{$tableName}` ({$colList}) VALUES\n";
                    $valueLines = [];
                    foreach ($rows as $r) {
                        $values = array_map(function ($val) use ($pdo) {
                            return is_null($val) ? 'NULL' : $pdo->quote($val);
                        }, array_values($r));
                        $valueLines[] = '(' . implode(', ', $values) . ')';
                    }
                    $output .= implode(",\n", $valueLines) . ";\n\n";
                }
            }

            // Append view definition
            $output .= "-- View structure for User\n";
            $output .= "CREATE OR REPLACE VIEW `User` AS SELECT id AS ID, name AS Name, email AS Email, password AS Password, role AS Role, created_at FROM users;\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

            File::put($targetFile, $output);
            return true;
        } catch (\Exception $e) {
            \Log::error('Backup dump failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function cleanupOldBackups(array $settings, string $dir): void
    {
        $retention = $settings['retention'] ?? '1_month';
        if ($retention === 'keep_all' || !File::exists($dir)) {
            return;
        }

        $days = match($retention) {
            '1_week' => 7,
            '1_month' => 30,
            '1_year' => 365,
            default => 30,
        };

        $threshold = Carbon::now()->subDays($days)->timestamp;

        foreach (File::files($dir) as $file) {
            if (str_starts_with($file->getFilename(), 'backup_p7db_') && in_array(strtolower($file->getExtension()), ['sql', 'gz', 'bak', 'json'])) {
                if ($file->getMTime() < $threshold) {
                    @unlink($file->getPathname());
                }
            }
        }
    }

    public function downloadBackup(string $filename)
    {
        $settings = $this->getSettings();
        $backupDir = $this->getBackupDirectory($settings);
        $clean = basename($filename);
        $path = rtrim($backupDir, '\\/') . DIRECTORY_SEPARATOR . $clean;
        $archiveDir = $this->getArchiveDirectory($settings);
        $archivePath = rtrim($archiveDir, '\\/') . DIRECTORY_SEPARATOR . $clean;
        $contentType = str_ends_with(strtolower($clean), '.json') ? 'application/json' : 'application/sql';

        if (File::exists($path)) {
            return response()->download($path, $clean, [
                'Content-Type' => $contentType,
            ]);
        }

        if (File::exists($archivePath)) {
            return response()->download($archivePath, $clean, [
                'Content-Type' => $contentType,
            ]);
        }

        return redirect()->route('backup.index')->with('error', 'Backup file not found.');
    }

    public function deleteBackup(Request $request)
    {
        $request->validate(['filename' => 'required|string']);
        $settings = $this->getSettings();
        $backupDir = $this->getBackupDirectory($settings);
        $archiveDir = $this->getArchiveDirectory($settings);

        $clean = basename($request->filename);
        $path = rtrim($backupDir, '\\/') . DIRECTORY_SEPARATOR . $clean;
        $archivePath = rtrim($archiveDir, '\\/') . DIRECTORY_SEPARATOR . $clean;

        if (File::exists($path)) {
            // Move file to recovery archive instead of permanently unlinking
            File::move($path, $archivePath);
            return redirect()->route('backup.index')->with('success', "Backup '{$clean}' moved to Recovery Archive. You can recover it at any time.");
        }

        return redirect()->route('backup.index')->with('error', 'Backup file not found.');
    }

    public function recoverBackup(Request $request)
    {
        $request->validate(['filename' => 'required|string']);
        $settings = $this->getSettings();
        $backupDir = $this->getBackupDirectory($settings);
        $archiveDir = $this->getArchiveDirectory($settings);

        $clean = basename($request->filename);
        $archivePath = rtrim($archiveDir, '\\/') . DIRECTORY_SEPARATOR . $clean;
        $activePath = rtrim($backupDir, '\\/') . DIRECTORY_SEPARATOR . $clean;

        if (File::exists($archivePath)) {
            File::move($archivePath, $activePath);
            return redirect()->route('backup.index')->with('success', "Backup '{$clean}' successfully recovered and restored to active backups!");
        }

        return redirect()->route('backup.index')->with('error', 'Archived backup file not found.');
    }

    public function purgeBackup(Request $request)
    {
        $request->validate(['filename' => 'required|string']);
        $settings = $this->getSettings();
        $archiveDir = $this->getArchiveDirectory($settings);

        $clean = basename($request->filename);
        $archivePath = rtrim($archiveDir, '\\/') . DIRECTORY_SEPARATOR . $clean;

        if (File::exists($archivePath)) {
            @unlink($archivePath);
            return redirect()->route('backup.index')->with('success', "Archived backup '{$clean}' has been permanently deleted from storage.");
        }

        return redirect()->route('backup.index')->with('error', 'Archived file not found.');
    }

    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'nullable|file|extensions:sql,json',
            'existing_file' => 'nullable|string',
        ]);

        $settings = $this->getSettings();
        $backupDir = $this->getBackupDirectory($settings);
        $archiveDir = $this->getArchiveDirectory($settings);
        $targetFile = null;

        if ($request->hasFile('backup_file')) {
            $file = $request->file('backup_file');
            $filename = 'uploaded_restore_' . date('Ymd_His') . '.' . strtolower($file->getClientOriginalExtension());
            File::ensureDirectoryExists(storage_path('app/temp'));
            $file->move(storage_path('app/temp'), $filename);
            $targetFile = storage_path('app/temp/' . $filename);
        } elseif ($request->filled('existing_file')) {
            $clean = basename($request->existing_file);
            $existing = rtrim($backupDir, '\\/') . DIRECTORY_SEPARATOR . $clean;
            $archiveExisting = rtrim($archiveDir, '\\/') . DIRECTORY_SEPARATOR . $clean;
            if (File::exists($existing)) {
                $targetFile = $existing;
            } elseif (File::exists($archiveExisting)) {
                $targetFile = $archiveExisting;
            }
        }

        if (!$targetFile || !File::exists($targetFile)) {
            return redirect()->route('backup.index')->with('error', 'Invalid backup file selected for restore.');
        }

        try {
            if (DB::connection()->getDriverName() === 'sqlsrv') {
                app(SqlServerSnapshotService::class)->restoreFile(DB::connection(), $targetFile);
            } else {
                $sql = File::get($targetFile);
                DB::unprepared($sql);
            }

            if (str_contains($targetFile, 'temp')) {
                @unlink($targetFile);
            }

            return redirect()->route('backup.index')->with('success', 'Database restored successfully from backup!');
        } catch (\Throwable $e) {
            return redirect()->route('backup.index')->with('error', 'Restore error: ' . ($e->getPrevious() ?? $e)->getMessage());
        }
    }

    public function browseFolders(Request $request, BackupDirectoryBrowser $browser)
    {
        $validated = $request->validate(['path' => 'nullable|string|max:255']);

        try {
            return response()->json($browser->browse($validated['path'] ?? null));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function pickFolder(Request $request, \App\Services\NativeBackupFolderPicker $picker, BackupDirectoryBrowser $browser)
    {
        abort_unless(in_array($request->ip(), ['127.0.0.1', '::1']), 403, 'Choose the folder on the server computer.');
        $validated = $request->validate(['initial_directory' => 'nullable|string|max:255']);
        set_time_limit(310);
        try {
            $path = $picker->choose($validated['initial_directory'] ?? null);
            return response()->json(['cancelled' => $path === null, 'path' => $path === null ? null : $browser->select($path)]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'backup_mode' => 'required|in:automatic,manual',
            'frequency' => 'required|in:1_day,1_week,1_month',
            'backup_time' => 'sometimes|required|date_format:H:i',
            'retention' => 'required|in:1_week,1_month,1_year,keep_all',
            'storage_path' => 'nullable|string|max:255',
            'gdrive_enabled' => 'nullable|boolean',
            'gdrive_folder_id' => 'nullable|string|max:255',
            'gdrive_email' => 'nullable|string|max:255',
        ]);

        $settings = $this->getSettings();
        $selectedPath = trim($validated['storage_path'] ?? '');
        if ($selectedPath !== '' && $selectedPath !== ($settings['storage_path'] ?? '')) {
            try {
                $validated['storage_path'] = app(BackupDirectoryBrowser::class)->select($selectedPath);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['storage_path' => $exception->getMessage()]);
            }
        }
        $settings['backup_mode'] = $validated['backup_mode'];
        $settings['frequency'] = $validated['frequency'];
        $settings['backup_time'] = $validated['backup_time'] ?? $settings['backup_time'];
        $settings['retention'] = $validated['retention'];
        $settings['storage_path'] = !empty($validated['storage_path']) ? $validated['storage_path'] : $this->defaultBackupDir;
        $settings['gdrive_enabled'] = !empty($request->gdrive_enabled);
        $settings['gdrive_folder_id'] = $validated['gdrive_folder_id'] ?? '';
        $settings['gdrive_email'] = $validated['gdrive_email'] ?? '';

        $saved = $this->withBackupLock(function () use ($settings) {
            $latest = $this->getSettings();
            $settings['last_backup_at'] = $latest['last_backup_at'];
            $settings['last_automatic_backup_at'] = $latest['last_automatic_backup_at'];
            $this->saveSettings($settings);
            return true;
        });
        if (!$saved) return redirect()->route('backup.index')->with('error', 'A backup is running. Save settings after it finishes.');
        return redirect()->route('backup.index')->with('success', 'Backup settings updated successfully!');
    }
}
