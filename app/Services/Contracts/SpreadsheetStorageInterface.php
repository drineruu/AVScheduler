<?php

namespace App\Services\Contracts;

interface SpreadsheetStorageInterface
{
    /**
     * @return array<int, array<string, string>>
     */
    public function readSheet(string $sheetName): array;

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function writeSheet(string $sheetName, array $headers, array $rows): void;

    public function sheetHasData(string $sheetName): bool;

    public function ensureSheetExists(string $sheetName): void;

    public function clearCache(?string $sheetName = null): void;
}
