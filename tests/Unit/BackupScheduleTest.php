<?php

namespace Tests\Unit;

use App\Services\BackupSchedule;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class BackupScheduleTest extends TestCase
{
    public function test_selected_local_time_and_one_run_per_day(): void
    {
        $schedule = new BackupSchedule;
        $settings = ['backup_mode' => 'automatic', 'frequency' => '1_day', 'backup_time' => '18:30'];
        $this->assertFalse($schedule->isDue($settings, Carbon::parse('2026-10-10 10:29:00', 'UTC')));
        $this->assertTrue($schedule->isDue($settings, Carbon::parse('2026-10-10 10:30:00', 'UTC')));
        $settings['last_automatic_backup_at'] = '2026-10-10T18:31:00+08:00';
        $this->assertFalse($schedule->isDue($settings, Carbon::parse('2026-10-10 23:00', 'Asia/Manila')));
        $this->assertTrue($schedule->isDue($settings, Carbon::parse('2026-10-11 18:30', 'Asia/Manila')));
        $settings['backup_mode'] = 'manual';
        $this->assertFalse($schedule->isDue($settings, Carbon::parse('2026-10-12 20:00', 'Asia/Manila')));
    }

    public function test_weekly_and_month_end_schedules_catch_up_once(): void
    {
        $schedule = new BackupSchedule;
        $settings = ['backup_mode' => 'automatic', 'frequency' => '1_week', 'backup_time' => '08:00', 'last_automatic_backup_at' => '2026-10-01T08:00:00+08:00'];
        $this->assertFalse($schedule->isDue($settings, Carbon::parse('2026-10-07 12:00', 'Asia/Manila')));
        $this->assertTrue($schedule->isDue($settings, Carbon::parse('2026-10-09 12:00', 'Asia/Manila')));
        $settings['frequency'] = '1_month';
        $settings['last_automatic_backup_at'] = '2026-01-31T08:00:00+08:00';
        $this->assertFalse($schedule->isDue($settings, Carbon::parse('2026-02-27 12:00', 'Asia/Manila')));
        $this->assertTrue($schedule->isDue($settings, Carbon::parse('2026-02-28 08:00', 'Asia/Manila')));
    }
}
