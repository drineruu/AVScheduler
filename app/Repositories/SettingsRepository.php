<?php

namespace App\Repositories;

use App\Services\Contracts\SpreadsheetStorageInterface;
use App\Services\SheetMapper;

class SettingsRepository
{
    public function __construct(
        private SpreadsheetStorageInterface $storage,
    ) {}

    /**
     * @return array{
     *     congregation: string,
     *     address: string,
     *     title: string,
     *     include_preparation: bool
     * }
     */
    public function get(): array
    {
        $sheet = config('google.sheets.settings');
        $rows = $this->storage->readSheet($sheet['name']);

        if ($rows === []) {
            return config('google.default_settings');
        }

        return $this->mapRow($rows[0]);
    }

    /**
     * @param  array{
     *     congregation?: string,
     *     address?: string,
     *     title?: string,
     *     include_preparation?: bool
     * }  $settings
     */
    public function update(array $settings): array
    {
        $current = $this->get();
        $merged = [
            'congregation' => (string) ($settings['congregation'] ?? $current['congregation']),
            'address' => (string) ($settings['address'] ?? $current['address']),
            'title' => (string) ($settings['title'] ?? $current['title']),
            'include_preparation' => (bool) ($settings['include_preparation'] ?? $current['include_preparation']),
        ];

        $sheet = config('google.sheets.settings');
        $this->storage->writeSheet($sheet['name'], $sheet['headers'], [[
            'congregation' => $merged['congregation'],
            'address' => $merged['address'],
            'title' => $merged['title'],
            'include_preparation' => SheetMapper::fromBool($merged['include_preparation']),
        ]]);

        return $merged;
    }

    /**
     * @param  array<string, string>  $row
     * @return array{
     *     congregation: string,
     *     address: string,
     *     title: string,
     *     include_preparation: bool
     * }
     */
    private function mapRow(array $row): array
    {
        SheetMapper::assertRequiredColumns(config('google.sheets.settings.name'), $row, config('google.sheets.settings.headers'));

        return [
            'congregation' => $row['congregation'],
            'address' => $row['address'],
            'title' => $row['title'],
            'include_preparation' => SheetMapper::toBool($row['include_preparation']),
        ];
    }
}
