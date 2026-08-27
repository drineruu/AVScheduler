<?php

namespace App\Repositories;

use App\Exceptions\SpreadsheetException;
use App\Services\Contracts\SpreadsheetStorageInterface;
use App\Services\MeetingTypeService;
use App\Services\SheetMapper;

class MeetingRepository
{
    public function __construct(
        private SpreadsheetStorageInterface $storage,
        private ?MeetingTypeService $meetingTypes = null,
    ) {}

    private function meetingTypes(): MeetingTypeService
    {
        return $this->meetingTypes ??= app(MeetingTypeService::class);
    }

    /**
     * @return array<int, array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>,
     *     type: string
     * }>
     */
    public function all(): array
    {
        $sheet = config('google.sheets.meetings');
        $rows = $this->storage->readSheet($sheet['name']);

        $meetings = array_map(fn (array $row) => $this->mapRow($row), $rows);

        usort($meetings, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return $meetings;
    }

    /**
     * @return array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>,
     *     type: string
     * }|null
     */
    public function findByDate(string $date): ?array
    {
        foreach ($this->all() as $meeting) {
            if ($meeting['date'] === $date) {
                return $meeting;
            }
        }

        return null;
    }

    /**
     * @param  array{
     *     date: string,
     *     skip?: bool,
     *     allow_trainee?: bool,
     *     busy_brothers?: array<int, int>
     * }  $data
     * @return array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>,
     *     type: string
     * }
     */
    public function create(array $data): array
    {
        $meeting = $this->normalize($data);

        if ($this->findByDate($meeting['date']) !== null) {
            throw SpreadsheetException::duplicate('Meeting', $meeting['date']);
        }

        $meetings = $this->all();
        $meetings[] = $meeting;
        $this->persist($meetings);

        return $meeting;
    }

    /**
     * @param  array{
     *     skip?: bool,
     *     allow_trainee?: bool,
     *     busy_brothers?: array<int, int>
     * }  $data
     * @return array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>,
     *     type: string
     * }
     */
    public function update(string $date, array $data): array
    {
        $meetings = $this->all();
        $updated = null;

        foreach ($meetings as $index => $meeting) {
            if ($meeting['date'] !== $date) {
                continue;
            }

            $updated = $this->normalize([
                ...$meeting,
                ...$data,
                'date' => $date,
            ]);
            $meetings[$index] = $updated;
        }

        if ($updated === null) {
            throw SpreadsheetException::notFound('Meeting', $date);
        }

        $this->persist($meetings);

        return $updated;
    }

    public function delete(string $date): void
    {
        $meetings = array_values(array_filter(
            $this->all(),
            static fn (array $meeting) => $meeting['date'] !== $date,
        ));

        if (count($meetings) === count($this->all())) {
            throw SpreadsheetException::notFound('Meeting', $date);
        }

        $this->persist($meetings);
    }

    /**
     * @param  array<int, int>  $busyBrothers
     */
    public function assertValidBrotherReferences(array $busyBrothers, BrotherRepository $brothers): void
    {
        foreach ($busyBrothers as $brotherId) {
            if ($brothers->find($brotherId) === null) {
                throw SpreadsheetException::invalidReference("brother ID [{$brotherId}] does not exist");
            }
        }
    }

    /**
     * @param  array<int, array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>
     * }>  $meetings
     */
    private function persist(array $meetings): void
    {
        $sheet = config('google.sheets.meetings');

        usort($meetings, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        $rows = array_map(fn (array $meeting) => [
            'date' => $meeting['date'],
            'skip' => SheetMapper::fromBool($meeting['skip']),
            'allow_trainee' => SheetMapper::fromBool($meeting['allow_trainee']),
            'busy_brothers' => SheetMapper::formatCsvInts($meeting['busy_brothers']),
        ], $meetings);

        $this->storage->writeSheet($sheet['name'], $sheet['headers'], $rows);
    }

    /**
     * @param  array<string, string>  $row
     * @return array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>
     * }
     */
    private function mapRow(array $row): array
    {
        $sheetName = config('google.sheets.meetings.name');
        SheetMapper::assertRequiredColumns($sheetName, $row, config('google.sheets.meetings.headers'));

        return $this->withMeetingType([
            'date' => $row['date'],
            'skip' => SheetMapper::toBool($row['skip']),
            'allow_trainee' => SheetMapper::toBool($row['allow_trainee']),
            'busy_brothers' => SheetMapper::parseCsvInts($row['busy_brothers'], $sheetName, 'busy_brothers'),
        ]);
    }

    /**
     * @param  array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>
     * }  $meeting
     * @return array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>,
     *     type: string
     * }
     */
    private function withMeetingType(array $meeting): array
    {
        return [
            ...$meeting,
            'type' => $this->meetingTypes()->fromDate($meeting['date'])->value,
        ];
    }

    /**
     * @param  array{
     *     date: string,
     *     skip?: bool,
     *     allow_trainee?: bool,
     *     busy_brothers?: array<int, int>
     * }  $data
     * @return array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>
     * }
     */
    private function normalize(array $data): array
    {
        $date = trim((string) ($data['date'] ?? ''));

        if ($date === '' || ! $this->isValidDate($date)) {
            throw SpreadsheetException::malformedRow(config('google.sheets.meetings.name'), 'date must use YYYY-MM-DD format');
        }

        return $this->withMeetingType([
            'date' => $date,
            'skip' => (bool) ($data['skip'] ?? false),
            'allow_trainee' => (bool) ($data['allow_trainee'] ?? true),
            'busy_brothers' => array_values(array_map('intval', $data['busy_brothers'] ?? [])),
        ]);
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
