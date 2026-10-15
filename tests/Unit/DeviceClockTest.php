<?php

namespace Tests\Unit;

use App\Services\DeviceClock;
use PHPUnit\Framework\TestCase;

class DeviceClockTest extends TestCase
{
    private function windowsClock(string $bias): DeviceClock
    {
        return new class($bias) extends DeviceClock {
            public function __construct(private string $bias) {}

            public function windowsOffset(): string { return $this->readWindowsOffset(); }

            protected function commandOutput(array $command): string
            {
                if ($command[0] === 'powershell.exe') throw new \RuntimeException('PowerShell unavailable');
                return 'ActiveTimeBias    REG_DWORD    '.$this->bias;
            }
        };
    }

    public function test_native_windows_fallback_handles_east_west_and_fractional_time_zones(): void
    {
        foreach (['0xfffffe20' => '+08:00', '0x1a4' => '-07:00', '0xfffffea7' => '+05:45', '0xd2' => '-03:30', '0x0' => '+00:00'] as $bias => $offset) {
            $this->assertSame($offset, $this->windowsClock($bias)->windowsOffset());
        }
    }

    public function test_invalid_windows_bias_is_not_used_as_a_clock_offset(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->windowsClock('0x7fffffff')->windowsOffset();
    }
}
