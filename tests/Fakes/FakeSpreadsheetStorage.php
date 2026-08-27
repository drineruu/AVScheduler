<?php

namespace Tests\Fakes;

use App\Services\Contracts\SpreadsheetStorageInterface;

class FakeSpreadsheetStorage implements SpreadsheetStorageInterface
{
    /** @var array<string, array{headers: array<int, string>, rows: array<int, array<string, mixed>>}> */
    private array $sheets = [];

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function seed(string $sheetName, array $headers, array $rows = []): void
    {
        $this->sheets[$sheetName] = [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function readSheet(string $sheetName): array
    {
        if (! isset($this->sheets[$sheetName])) {
            return [];
        }

        return array_map(function (array $row) use ($sheetName) {
            $mapped = [];
            $headers = $this->sheets[$sheetName]['headers'];

            foreach ($headers as $header) {
                $mapped[$header] = (string) ($row[$header] ?? '');
            }

            return $mapped;
        }, $this->sheets[$sheetName]['rows']);
    }

    public function writeSheet(string $sheetName, array $headers, array $rows): void
    {
        $this->sheets[$sheetName] = [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function sheetHasData(string $sheetName): bool
    {
        return ! empty($this->sheets[$sheetName]['rows'] ?? []);
    }

    public function ensureSheetExists(string $sheetName): void
    {
        if (! isset($this->sheets[$sheetName])) {
            $this->sheets[$sheetName] = [
                'headers' => [],
                'rows' => [],
            ];
        }
    }

    public function clearCache(?string $sheetName = null): void
    {
        // No-op for the in-memory fake.
    }

    /**
     * @return array<int, string>
     */
    public function headers(string $sheetName): array
    {
        return $this->sheets[$sheetName]['headers'] ?? [];
    }
}
