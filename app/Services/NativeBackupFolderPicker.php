<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class NativeBackupFolderPicker
{
    public function choose(?string $initialDirectory = null): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            throw new \RuntimeException('The native folder chooser requires Windows.');
        }
        $process = new Process(['powershell.exe', '-NoProfile', '-STA', '-ExecutionPolicy', 'Bypass', '-File', base_path('scripts/choose-backup-folder.ps1'), '-InitialDirectory', $initialDirectory ?? '']);
        $process->setTimeout(300);
        $process->mustRun();
        $result = json_decode(trim($process->getOutput()), true, 512, JSON_THROW_ON_ERROR);

        return ! empty($result['cancelled']) ? null : ($result['path'] ?? null);
    }
}
