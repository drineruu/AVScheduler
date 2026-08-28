<?php

namespace App\Repositories;

use App\Services\Contracts\SpreadsheetStorageInterface;
use App\Services\SheetMapper;

class ScheduleRepository
{
    public function __construct(
        private SpreadsheetStorageInterface $storage,
    ) {}

    /**
     * @return array<int, array{
     *     date: string,
     *     audio: int|null,
     *     video: int|null,
     *     mics: int|null,
     *     stage: int|null,
     *     preparation: int|null
     * }>
     */
    public function all(): array
    {
        $sheet = config('google.sheets.schedule');
        $rows = $this->storage->readSheet($sheet['name']);

        $schedule = array_map(fn (array $row) => $this->mapRow($row), $rows);

        usort($schedule, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return $schedule;
    }

    /**
     * @param  array<int, array{
     *     date: string,
     *     audio?: int|null,
     *     video?: int|null,
     *     mics?: int|null,
     *     stage?: int|null,
     *     preparation?: int|null
     * }>  $schedule
     */
    public function save(array $schedule): void
    {
        $sheet = config('google.sheets.schedule');

        usort($schedule, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        $rows = array_map(fn (array $entry) => [
            'date' => $entry['date'],
            'audio' => SheetMapper::fromIntOrNull($entry['audio'] ?? null),
            'video' => SheetMapper::fromIntOrNull($entry['video'] ?? null),
            'mics' => SheetMapper::fromIntOrNull($entry['mics'] ?? null),
            'stage' => SheetMapper::fromIntOrNull($entry['stage'] ?? null),
            'preparation' => SheetMapper::fromIntOrNull($entry['preparation'] ?? null),
        ], $schedule);

        $this->storage->writeSheet($sheet['name'], $sheet['headers'], $rows);
    }

    public function clear(): void
    {
        $sheet = config('google.sheets.schedule');
        $this->storage->writeSheet($sheet['name'], $sheet['headers'], []);
    }

    /**
     * @param  array<string, string>  $row
     * @return array{
     *     date: string,
     *     audio: int|null,
     *     video: int|null,
     *     mics: int|null,
     *     stage: int|null,
     *     preparation: int|null
     * }
     */
    private function mapRow(array $row): array
    {
        $sheetName = config('google.sheets.schedule.name');
        SheetMapper::assertRequiredColumns($sheetName, $row, config('google.sheets.schedule.headers'));

        return [
            'date' => $row['date'],
            'audio' => SheetMapper::toIntOrNull($row['audio'], $sheetName, 'audio'),
            'video' => SheetMapper::toIntOrNull($row['video'], $sheetName, 'video'),
            'mics' => SheetMapper::toIntOrNull($row['mics'], $sheetName, 'mics'),
            'stage' => SheetMapper::toIntOrNull($row['stage'], $sheetName, 'stage'),
            'preparation' => SheetMapper::toIntOrNull($row['preparation'], $sheetName, 'preparation'),
        ];
    }
}
