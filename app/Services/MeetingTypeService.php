<?php

namespace App\Services;

use App\Enums\MeetingType;
use App\Exceptions\SpreadsheetException;

class MeetingTypeService
{
    public function fromDate(string $date): MeetingType
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (! $parsed instanceof \DateTimeImmutable || $parsed->format('Y-m-d') !== $date) {
            throw SpreadsheetException::malformedRow('Meetings', 'date must use YYYY-MM-DD format');
        }

        $dayOfWeek = (int) $parsed->format('w');

        return in_array($dayOfWeek, config('schedule.weekend_days', []), true)
            ? MeetingType::Weekend
            : MeetingType::Midweek;
    }

    public function isWeekend(string $date): bool
    {
        return $this->fromDate($date) === MeetingType::Weekend;
    }
}
