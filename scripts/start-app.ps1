param(
    [ValidateRange(1, 65535)]
    [int] $Port = 8000,
    [switch] $NoBrowser,
    [ValidateRange(10, 180)]
    [int] $StartupTimeoutSeconds = 60
)

$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectDirectory
$wrapper = Join-Path $PSScriptRoot 'php.ps1'
$logs = Join-Path $projectDirectory 'storage\logs'
$appUrl = 'http://127.0.0.1:' + $Port
$logSuffix = if ($Port -eq 8000) { '' } else { '-' + $Port }
$serverLog = Join-Path $logs ('app-server' + $logSuffix + '.log')
$serverErrorLog = Join-Path $logs ('app-server-error' + $logSuffix + '.log')
$workerLog = Join-Path $logs ('backup-worker' + $logSuffix + '.log')
$workerErrorLog = Join-Path $logs ('backup-worker-error' + $logSuffix + '.log')
$worker = $null
$server = $null
$previousAppUrl = $env:APP_URL
$result = 1

function Get-AppStatus {
    $response = $null
    $reader = $null
    try {
        $request = [System.Net.HttpWebRequest]::Create($appUrl + '/login')
        # Local startup must not depend on internet access or proxy settings.
        $request.Proxy = $null
        $request.Timeout = 2000
        $request.ReadWriteTimeout = 2000
        $response = $request.GetResponse()
        $reader = [System.IO.StreamReader]::new($response.GetResponseStream())
        $body = $reader.ReadToEnd()
        return [pscustomobject]@{
            Ready = ([int] $response.StatusCode -eq 200 -and $body -match '<title>\s*Login\s*\|\s*Paolo Paolo\s*</title>')
            Detail = 'HTTP ' + [int] $response.StatusCode
        }
    } catch {
        $failure = $_.Exception.GetBaseException()
        if ($failure -is [System.Net.WebException] -and $failure.Response) { $response = $failure.Response }
        return [pscustomobject]@{ Ready = $false; Detail = $failure.Message }
    } finally {
        if ($reader) { $reader.Dispose() }
        if ($response) { $response.Close() }
    }
}

function Test-PortInUse {
    $client = [System.Net.Sockets.TcpClient]::new()
    try {
        $connection = $client.ConnectAsync('127.0.0.1', $Port)
        return ($connection.Wait(1000) -and $client.Connected)
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Open-App {
    if (-not $NoBrowser) {
        try {
            Start-Process -FilePath $appUrl | Out-Null
        } catch {
            Write-Warning ('Could not open the browser. Open ' + $appUrl + ' manually.')
        }
    }
}

try {
    $status = Get-AppStatus
    if ($status.Ready) {
        Write-Host ('Paolo Paolo is already running: ' + $appUrl)
        Write-Host 'Keep the original launcher window open while using the app.'
        Open-App
        $result = 0
    } else {
        if (Test-PortInUse) {
            throw ('Port ' + $Port + ' is in use, but the Paolo Paolo login page is not ready (' + $status.Detail + '). Check the existing launcher and storage\logs, or use start.bat -Port 8001.')
        }

        foreach ($requiredFile in @('.env', 'vendor\autoload.php', 'scripts\php.ps1', 'scripts\check-startup.php')) {
            if (-not (Test-Path -LiteralPath (Join-Path $projectDirectory $requiredFile))) {
                throw ('Required file is missing: ' + $requiredFile + '. Complete the installation described in README.md first.')
            }
        }

        $env:APP_URL = $appUrl
        Write-Host 'Checking PHP, local assets, and the database...'
        & $wrapper (Join-Path $PSScriptRoot 'check-startup.php')
        if ($LASTEXITCODE -ne 0) { throw 'The startup check failed. Fix the reported error, then run start.bat again.' }

        New-Item -ItemType Directory -Path $logs -Force | Out-Null
        # Keep the driver configuration when Laravel starts its PHP server.
        $server = Start-Process -FilePath 'powershell.exe' -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', ('"' + $wrapper + '"'), 'artisan', 'serve', '--host=127.0.0.1', ('--port=' + $Port), '--tries=1', '--no-reload') -WorkingDirectory $projectDirectory -WindowStyle Hidden -RedirectStandardOutput $serverLog -RedirectStandardError $serverErrorLog -PassThru

        $deadline = [DateTime]::UtcNow.AddSeconds($StartupTimeoutSeconds)
        do {
            $server.Refresh()
            if ($server.HasExited) { throw 'The local web server stopped during startup.' }
            $status = Get-AppStatus
            if ($status.Ready) { break }
            Start-Sleep -Milliseconds 250
        } while ([DateTime]::UtcNow -lt $deadline)
        if (-not $status.Ready) { throw ('The app did not become ready at ' + $appUrl + ' (' + $status.Detail + ').') }

        $worker = Start-Process -FilePath 'powershell.exe' -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', ('"' + $wrapper + '"'), 'artisan', 'schedule:work') -WorkingDirectory $projectDirectory -WindowStyle Hidden -RedirectStandardOutput $workerLog -RedirectStandardError $workerErrorLog -PassThru
        Write-Host ('Paolo Paolo is ready: ' + $appUrl + ' (scheduled backups enabled)')
        Write-Host 'Keep this window open while using the app. Press Ctrl+C to stop it.'
        Open-App
        while (-not $server.WaitForExit(500)) { }
        $server.Refresh()
        $result = $server.ExitCode
        if ($result -ne 0) { throw ('The local web server stopped with exit code ' + $result + '.') }
    }
} catch {
    Write-Host ('Startup failed: ' + $_.Exception.Message) -ForegroundColor Red
    if ($server) {
        foreach ($logFile in @($serverLog, $serverErrorLog)) {
            if (Test-Path -LiteralPath $logFile) {
                Write-Host ('Server log: ' + $logFile)
                Get-Content -LiteralPath $logFile -Tail 12 | ForEach-Object { Write-Host $_ }
            }
        }
    }
    $result = 1
} finally {
    $env:APP_URL = $previousAppUrl
    # Never terminate another launcher or a process using the requested port.
    foreach ($child in @($server, $worker)) {
        if ($child -and -not $child.HasExited) { & taskkill.exe /PID $child.Id /T /F | Out-Null }
    }
}
exit $result
