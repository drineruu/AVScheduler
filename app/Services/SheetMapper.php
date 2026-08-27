<?php

namespace App\Services;

use App\Exceptions\SpreadsheetException;

class SheetMapper
{
    /**
     * @param  array<int, string>  $headerRow
     * @param  array<int, array<int, string>>  $dataRows
     * @return array<int, array<string, string>>
     */
    public static function rowsToAssociative(array $headerRow, array $dataRows): array
    {
        $headers = array_map(static fn ($header) => trim((string) $header), $headerRow);

        return array_values(array_map(function (array $row) use ($headers) {
            $mapped = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $mapped[$header] = trim((string) ($row[$index] ?? ''));
            }

            return $mapped;
        }, $dataRows));
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public static function associativeToRow(array $headers, array $data): array
    {
        return array_map(static fn (string $header) => (string) ($data[$header] ?? ''), $headers);
    }

    public static function toBool(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'y'], true);
    }

    public static function fromBool(bool $value): string
    {
        return $value ? 'TRUE' : 'FALSE';
    }

    public static function toIntOrNull(?string $value, string $sheetName = 'sheet', string $field = 'value'): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! ctype_digit($value)) {
            throw SpreadsheetException::malformedRow($sheetName, "expected integer for [{$field}], got [{$value}]");
        }

        return (int) $value;
    }

    public static function fromIntOrNull(?int $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    /**
     * @return array<int, int>
     */
    public static function parseCsvInts(string $value, string $sheetName = 'sheet', string $field = 'busy_brothers'): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        return array_values(array_map(static function (string $part) use ($value, $sheetName, $field) {
            $part = trim($part);

            if ($part === '' || ! ctype_digit($part)) {
                throw SpreadsheetException::malformedRow($sheetName, "expected comma-separated IDs for [{$field}], got [{$value}]");
            }

            return (int) $part;
        }, explode(',', $value)));
    }

    /**
     * @param  array<int, int>  $ids
     */
    public static function formatCsvInts(array $ids): string
    {
        return implode(',', array_map(static fn (int $id) => (string) $id, $ids));
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<int, string>  $requiredColumns
     */
    public static function assertRequiredColumns(string $sheetName, array $row, array $requiredColumns): void
    {
        foreach ($requiredColumns as $column) {
            if (! array_key_exists($column, $row)) {
                throw SpreadsheetException::missingColumn($sheetName, $column);
            }
        }
    }
}
