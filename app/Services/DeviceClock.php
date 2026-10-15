<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeviceClock
{
    private ?string $offset = null;

    private int $checkedAt = 0;

    public function now(): Carbon
    {
        // Keep reading the OS clock; only cache its local UTC offset briefly.
        // hrtime remains monotonic when the user changes the laptop's clock.
        if ($this->offset === null || hrtime(true) - $this->checkedAt >= 60_000_000_000) {
            try {
                $this->offset = $this->readOffset();
            } catch (\Throwable $exception) {
                Log::warning('Could not read the device time zone; retaining the last known offset.', ['message' => $exception->getMessage()]);
                $this->offset ??= Carbon::now(config('app.timezone'))->format('P');
            }
            $this->checkedAt = hrtime(true);
        }

        return Carbon::now($this->offset);
    }

    protected function readOffset(): string
    {
        if (app()->runningUnitTests()) {
            return Carbon::now(config('app.timezone'))->format('P');
        }

        $offset = PHP_OS_FAMILY === 'Windows'
            ? $this->readWindowsOffset()
            : $this->commandOutput(['date', '+%:z']);
        if (! preg_match('/\A[+-](?:0\d|1[0-4]):[0-5]\d\z/', $offset)) {
            throw new \RuntimeException('The device returned an invalid time-zone offset.');
        }

        return $offset;
    }

    protected function readWindowsOffset(): string
    {
        try {
            $offset = $this->commandOutput(['powershell.exe', '-NoProfile', '-NonInteractive', '-WindowStyle', 'Hidden', '-Command', '[DateTimeOffset]::Now.ToString("zzz")']);
            if (preg_match('/\A[+-](?:0\d|1[0-4]):[0-5]\d\z/', $offset)) return $offset;
        } catch (\Throwable) {
            // Some PHP server environments cannot load Windows PowerShell.
        }

        // Windows maintains this signed minute bias, including daylight saving.
        $output = $this->commandOutput(['reg.exe', 'query', 'HKLM\\SYSTEM\\CurrentControlSet\\Control\\TimeZoneInformation', '/v', 'ActiveTimeBias']);
        if (! preg_match('/ActiveTimeBias\s+REG_DWORD\s+0x([0-9a-f]{1,8})\b/i', $output, $matches)) {
            throw new \RuntimeException('Unable to read the active Windows time-zone bias.');
        }
        $bias = hexdec($matches[1]);
        if ($bias >= 0x80000000) $bias -= 0x100000000;
        $minutes = -$bias;
        if (abs($minutes) > 14 * 60) throw new \RuntimeException('The Windows time-zone bias is invalid.');

        return sprintf('%s%02d:%02d', $minutes < 0 ? '-' : '+', intdiv(abs($minutes), 60), abs($minutes) % 60);
    }

    protected function commandOutput(array $command): string
    {
        $process = new Process($command);
        $process->setTimeout(5);
        $process->mustRun();

        return trim($process->getOutput());
    }
}
