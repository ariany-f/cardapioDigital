<?php

namespace App\Services;

use App\Models\Branch;
use Carbon\Carbon;

class BranchHoursService
{
    public function isOpen(Branch $branch): bool
    {
        return match ($branch->orders_status_override) {
            'closed' => false,
            'open' => true,
            default => $this->isOpenBySchedule($branch),
        };
    }

    public function isOpenBySchedule(Branch $branch): bool
    {
        $hours = $branch->opening_hours ?? [];
        if (empty($hours)) {
            return true;
        }

        $branch->loadMissing('tenant');
        $timezone = $branch->tenant?->timezone ?? config('app.timezone', 'America/Sao_Paulo');
        $now = now($timezone);

        $dayKey = $this->dayKeyFor($now);
        if (! $dayKey || empty($hours[$dayKey]) || ! is_array($hours[$dayKey])) {
            return false;
        }

        [$open, $close] = [
            $this->normalizeTime($hours[$dayKey][0] ?? null),
            $this->normalizeTime($hours[$dayKey][1] ?? null),
        ];

        if (! $open || ! $close) {
            return false;
        }

        return $this->isTimeWithinRange($now, $open, $close);
    }

    public function adminStatusPayload(Branch $branch): array
    {
        $scheduleOpen = $this->isOpenBySchedule($branch);
        $effectiveOpen = $this->isOpen($branch);
        $override = $branch->orders_status_override;

        $label = match ($override) {
            'open' => 'Aberto manualmente',
            'closed' => 'Fechado manualmente',
            default => $scheduleOpen ? 'Aberto (horário)' : 'Fechado (horário)',
        };

        return [
            'is_open' => $effectiveOpen,
            'is_open_by_schedule' => $scheduleOpen,
            'orders_status_override' => $override,
            'status_label' => $label,
            'accepting_orders' => $effectiveOpen,
        ];
    }

    public function nextOpenMessage(Branch $branch): ?string
    {
        return $this->isOpen($branch) ? null : __('branch.closed_now');
    }

    protected function dayKeyFor(Carbon $moment): ?string
    {
        $map = [
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            0 => 'sun',
            7 => 'sun',
        ];

        return $map[$moment->dayOfWeek] ?? null;
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return null;
    }

    protected function isTimeWithinRange(Carbon $now, string $open, string $close): bool
    {
        $current = $this->minutesFromMidnight($now);
        $openMinutes = $this->minutesFromTime($open);
        $closeMinutes = $this->minutesFromTime($close);

        if ($closeMinutes < $openMinutes) {
            return $current >= $openMinutes || $current <= $closeMinutes;
        }

        return $current >= $openMinutes && $current <= $closeMinutes;
    }

    protected function minutesFromMidnight(Carbon $moment): int
    {
        return $moment->hour * 60 + $moment->minute;
    }

    protected function minutesFromTime(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }
}
