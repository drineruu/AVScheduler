<?php

namespace App\Services\Scheduling;

class SchedulingState
{
    /** @var array<int, array<string, int>> */
    private array $counts = [];

    /** @var array<int, string> */
    private array $lastAvWeek = [];

    /**
     * @param  array<int, array{id: int}>  $brothers
     */
    public function initialize(array $brothers): void
    {
        foreach ($brothers as $brother) {
            $this->counts[$brother['id']] = [
                'audio' => 0,
                'video' => 0,
                'av_total' => 0,
                'mics' => 0,
                'stage' => 0,
                'preparation' => 0,
                'total' => 0,
            ];
        }
    }

    public function roleCount(int $brotherId, string $role): int
    {
        return $this->counts[$brotherId][$role] ?? 0;
    }

    public function totalCount(int $brotherId): int
    {
        return $this->counts[$brotherId]['total'] ?? 0;
    }

    public function recordAssignment(int $brotherId, string $role, string $meetingDate): void
    {
        if (! isset($this->counts[$brotherId])) {
            return;
        }

        $this->counts[$brotherId][$role]++;
        $this->counts[$brotherId]['total']++;

        if (in_array($role, ['audio', 'video'], true)) {
            $this->counts[$brotherId]['av_total']++;
            $this->lastAvWeek[$brotherId] = self::isoWeek($meetingDate);
        }
    }

    public function isOnAvCooldown(int $brotherId, string $meetingDate): bool
    {
        if (! isset($this->lastAvWeek[$brotherId])) {
            return false;
        }

        $previousWeek = self::isoWeek(
            (new \DateTimeImmutable($meetingDate))->modify('-1 week')->format('Y-m-d'),
        );

        return $this->lastAvWeek[$brotherId] === $previousWeek;
    }

    public static function isoWeek(string $date): string
    {
        $parsed = new \DateTimeImmutable($date);

        return $parsed->format('o').'-W'.str_pad($parsed->format('W'), 2, '0', STR_PAD_LEFT);
    }
}
