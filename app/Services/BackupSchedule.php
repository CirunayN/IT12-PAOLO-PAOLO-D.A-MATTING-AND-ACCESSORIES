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
        $now = $now->copy();
        $time = $settings['backup_time'] ?? '18:00';
        if (! preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return false;
        }
        $today = $now->copy()->startOfDay()->setTimeFromTimeString($time);
        $last = $settings['last_automatic_backup_at'] ?? null;
        if (isset($settings['last_automatic_schedule'])
            && $settings['last_automatic_schedule'] !== $this->key($settings)) {
            $last = null;
        }
        if (! $last) {
            return $now->gte($today);
        }
        $last = Carbon::parse($last, $now->getTimezone())->timezone($now->getTimezone())->startOfDay();
        $next = match ($settings['frequency'] ?? '1_day') {
            '1_week' => $last->addWeek(),
            '1_month' => $last->addMonthNoOverflow(),
            default => $last->addDay(),
        };

        return $now->gte($next->setTimeFromTimeString($time));
    }

    public function key(array $settings): string
    {
        return ($settings['frequency'] ?? '1_day').'|'.($settings['backup_time'] ?? '18:00');
    }
}
