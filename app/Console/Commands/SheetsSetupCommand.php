<?php

namespace App\Console\Commands;

use App\Repositories\SettingsRepository;
use App\Services\Contracts\SpreadsheetStorageInterface;
use App\Services\SheetMapper;
use Illuminate\Console\Command;

class SheetsSetupCommand extends Command
{
    protected $signature = 'sheets:setup';

    protected $description = 'Initialize Google Sheets tabs, headers, and default settings row';

    public function handle(
        SpreadsheetStorageInterface $storage,
        SettingsRepository $settings,
    ): int {
        foreach (config('google.sheets') as $sheet) {
            $storage->ensureSheetExists($sheet['name']);
        }

        $settingsSheet = config('google.sheets.settings');

        if (! $storage->sheetHasData($settingsSheet['name'])) {
            $defaults = config('google.default_settings');

            $storage->writeSheet($settingsSheet['name'], $settingsSheet['headers'], [[
                'congregation' => $defaults['congregation'],
                'address' => $defaults['address'],
                'title' => $defaults['title'],
                'include_preparation' => SheetMapper::fromBool($defaults['include_preparation']),
            ]]);

            $this->info('Default settings row created.');
        } else {
            $this->line('Settings sheet already contains data.');
        }

        foreach (config('google.sheets') as $key => $sheet) {
            if ($key === 'settings') {
                continue;
            }

            if ($storage->sheetHasData($sheet['name'])) {
                $this->line("{$sheet['name']} sheet already contains data.");

                continue;
            }

            $storage->writeSheet($sheet['name'], $sheet['headers'], []);
            $this->info("{$sheet['name']} headers initialized.");
        }

        $this->info('Spreadsheet setup complete.');
        $this->line('Current settings: '.json_encode($settings->get()));

        return self::SUCCESS;
    }
}
