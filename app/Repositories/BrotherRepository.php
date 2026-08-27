<?php

namespace App\Repositories;

use App\Enums\TrainingRole;
use App\Exceptions\SpreadsheetException;
use App\Services\Contracts\SpreadsheetStorageInterface;
use App\Services\SheetMapper;

class BrotherRepository
{
    public function __construct(
        private SpreadsheetStorageInterface $storage,
    ) {}

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }>
     */
    public function all(): array
    {
        $sheet = config('google.sheets.brothers');
        $rows = $this->storage->readSheet($sheet['name']);

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }|null
     */
    public function find(int $id): ?array
    {
        foreach ($this->all() as $brother) {
            if ($brother['id'] === $id) {
                return $brother;
            }
        }

        return null;
    }

    /**
     * @param  array{
     *     name: string,
     *     is_ms?: bool,
     *     can_audio?: bool,
     *     can_video?: bool,
     *     can_mic?: bool,
     *     can_stage?: bool,
     *     can_prep?: bool,
     *     training_role?: string
     * }  $data
     * @return array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }
     */
    public function create(array $data): array
    {
        $brother = $this->normalize([
            'id' => $this->nextId(),
            ...$data,
        ]);

        $brothers = $this->all();
        $brothers[] = $brother;
        $this->persist($brothers);

        return $brother;
    }

    /**
     * @param  array{
     *     name?: string,
     *     is_ms?: bool,
     *     can_audio?: bool,
     *     can_video?: bool,
     *     can_mic?: bool,
     *     can_stage?: bool,
     *     can_prep?: bool,
     *     training_role?: string
     * }  $data
     * @return array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }
     */
    public function update(int $id, array $data): array
    {
        $brothers = $this->all();
        $updated = null;

        foreach ($brothers as $index => $brother) {
            if ($brother['id'] !== $id) {
                continue;
            }

            $updated = $this->normalize([
                ...$brother,
                ...$data,
                'id' => $id,
            ]);
            $brothers[$index] = $updated;
        }

        if ($updated === null) {
            throw SpreadsheetException::notFound('Brother', (string) $id);
        }

        $this->persist($brothers);

        return $updated;
    }

    public function delete(int $id): void
    {
        $brothers = array_values(array_filter(
            $this->all(),
            static fn (array $brother) => $brother['id'] !== $id,
        ));

        if (count($brothers) === count($this->all())) {
            throw SpreadsheetException::notFound('Brother', (string) $id);
        }

        $this->persist($brothers);
    }

    public function nextId(): int
    {
        $ids = array_map(static fn (array $brother) => $brother['id'], $this->all());

        return $ids === [] ? 1 : max($ids) + 1;
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }>  $brothers
     */
    private function persist(array $brothers): void
    {
        $sheet = config('google.sheets.brothers');

        $rows = array_map(fn (array $brother) => [
            'id' => (string) $brother['id'],
            'name' => $brother['name'],
            'is_ms' => SheetMapper::fromBool($brother['is_ms']),
            'can_audio' => SheetMapper::fromBool($brother['can_audio']),
            'can_video' => SheetMapper::fromBool($brother['can_video']),
            'can_mic' => SheetMapper::fromBool($brother['can_mic']),
            'can_stage' => SheetMapper::fromBool($brother['can_stage']),
            'can_prep' => SheetMapper::fromBool($brother['can_prep']),
            'training_role' => $brother['training_role'],
        ], $brothers);

        $this->storage->writeSheet($sheet['name'], $sheet['headers'], $rows);
    }

    /**
     * @param  array<string, string>  $row
     * @return array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }
     */
    private function mapRow(array $row): array
    {
        $sheetName = config('google.sheets.brothers.name');
        SheetMapper::assertRequiredColumns($sheetName, $row, config('google.sheets.brothers.headers'));

        $trainingRole = $row['training_role'] !== '' ? $row['training_role'] : TrainingRole::None->value;

        if (! TrainingRole::isValid($trainingRole)) {
            throw SpreadsheetException::malformedRow($sheetName, "invalid training role [{$trainingRole}]");
        }

        return [
            'id' => SheetMapper::toIntOrNull($row['id'], $sheetName, 'id') ?? 0,
            'name' => $row['name'],
            'is_ms' => SheetMapper::toBool($row['is_ms']),
            'can_audio' => SheetMapper::toBool($row['can_audio']),
            'can_video' => SheetMapper::toBool($row['can_video']),
            'can_mic' => SheetMapper::toBool($row['can_mic']),
            'can_stage' => SheetMapper::toBool($row['can_stage']),
            'can_prep' => SheetMapper::toBool($row['can_prep']),
            'training_role' => $trainingRole,
        ];
    }

    /**
     * @param  array{
     *     id: int,
     *     name?: string,
     *     is_ms?: bool,
     *     can_audio?: bool,
     *     can_video?: bool,
     *     can_mic?: bool,
     *     can_stage?: bool,
     *     can_prep?: bool,
     *     training_role?: string
     * }  $data
     * @return array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }
     */
    private function normalize(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw SpreadsheetException::malformedRow(config('google.sheets.brothers.name'), 'name is required');
        }

        $trainingRole = (string) ($data['training_role'] ?? TrainingRole::None->value);

        if (! TrainingRole::isValid($trainingRole)) {
            throw SpreadsheetException::malformedRow(config('google.sheets.brothers.name'), "invalid training role [{$trainingRole}]");
        }

        return [
            'id' => (int) $data['id'],
            'name' => $name,
            'is_ms' => (bool) ($data['is_ms'] ?? false),
            'can_audio' => (bool) ($data['can_audio'] ?? false),
            'can_video' => (bool) ($data['can_video'] ?? false),
            'can_mic' => (bool) ($data['can_mic'] ?? false),
            'can_stage' => (bool) ($data['can_stage'] ?? false),
            'can_prep' => (bool) ($data['can_prep'] ?? false),
            'training_role' => $trainingRole,
        ];
    }
}
