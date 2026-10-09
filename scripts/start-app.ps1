param([int] $Port = 8000)
$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectDirectory
$wrapper = Join-Path $PSScriptRoot 'php.ps1'
$logs = Join-Path $projectDirectory 'storage\logs'
New-Item -ItemType Directory -Path $logs -Force | Out-Null
$worker = $null
$server = $null
try {
    # The wrapper loads the same SQL Server drivers for both processes.
    $worker = Start-Process -FilePath 'powershell.exe' -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', ('"' + $wrapper + '"'), 'artisan', 'schedule:work') -WorkingDirectory $projectDirectory -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logs 'backup-worker.log') -RedirectStandardError (Join-Path $logs 'backup-worker-error.log') -PassThru
    $server = Start-Process -FilePath 'powershell.exe' -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', ('"' + $wrapper + '"'), 'artisan', 'serve', '--host=127.0.0.1', ('--port=' + $Port), '--no-reload') -WorkingDirectory $projectDirectory -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logs 'app-server.log') -RedirectStandardError (Join-Path $logs 'app-server-error.log') -PassThru
    Write-Host ('Paolo Paolo: http://127.0.0.1:' + $Port + ' (scheduled backups enabled)')
    $server.WaitForExit()
    $server.Refresh()
    $result = $server.ExitCode
} finally {
    # Terminate only the process trees this launcher created.
    foreach ($child in @($server, $worker)) {
        if ($child -and -not $child.HasExited) { & taskkill.exe /PID $child.Id /T /F | Out-Null }
    }
}
exit $result
