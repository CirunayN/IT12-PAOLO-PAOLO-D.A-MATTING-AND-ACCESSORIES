<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class BackupSchedule
{
    public function isDue(array $settings, Carbon $now): bool
    {
        if (($settings['backup_mode'] ?? 'automatic') !== 'automatic') {
            return false;
        }
        $now = $now->copy()->timezone('Asia/Manila');
        $time = $settings['backup_time'] ?? '18:00';
        if (! preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return false;
        }
        $today = $now->copy()->startOfDay()->setTimeFromTimeString($time);
        if ($now->lt($today)) {
            return false;
        }
        $last = $settings['last_automatic_backup_at'] ?? null;
        if (! $last) {
            return true;
        }
        $last = Carbon::parse($last)->timezone('Asia/Manila')->startOfDay();
        $next = match ($settings['frequency'] ?? '1_day') {
            '1_week' => $last->addWeek(),
            '1_month' => $last->addMonthNoOverflow(),
            default => $last->addDay(),
        };

        return $now->gte($next->setTimeFromTimeString($time));
    }
}
