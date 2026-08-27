<?php

use App\Exceptions\SpreadsheetException;
use App\Services\GoogleSheetsService;
use Google\Service\Sheets;
use Google\Service\Sheets\Resource\Spreadsheets;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\Sheet;
use Google\Service\Sheets\SheetProperties;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('google.cache.enabled', false);
});

it('requires a spreadsheet id', function () {
    $service = new GoogleSheetsService;

    expect(fn () => $service->readSheet('Settings'))
        ->toThrow(SpreadsheetException::class, 'GOOGLE_SHEETS_SPREADSHEET_ID');
});

it('requires service account credentials', function () {
    Config::set('google.spreadsheet_id', 'spreadsheet-id');
    Config::set('google.service_account', '');

    $service = new GoogleSheetsService;

    expect(fn () => $service->readSheet('Settings'))
        ->toThrow(SpreadsheetException::class, 'GOOGLE_SERVICE_ACCOUNT_JSON');
});

it('reads settings through the google adapter', function () {
    Config::set('google.spreadsheet_id', 'spreadsheet-id');
    $values = Mockery::mock(SpreadsheetsValues::class);
    $values->shouldReceive('get')
        ->once()
        ->with('spreadsheet-id', 'Settings!A1:Z')
        ->andReturn(new ValueRange([
            'values' => [
                ['congregation', 'address', 'title', 'include_preparation'],
                ['West Tagalog Congregation', '06 Lipnica St.', 'AUDIO/VIDEO SCHEDULE', 'TRUE'],
            ],
        ]));

    $spreadsheets = Mockery::mock(Spreadsheets::class);
    $spreadsheets->shouldReceive('get')
        ->andReturn(new Spreadsheet([
            'sheets' => [
                new Sheet(['properties' => new SheetProperties(['title' => 'Settings'])]),
            ],
        ]));

    $sheets = Mockery::mock(Sheets::class);
    $sheets->spreadsheets_values = $values;
    $sheets->spreadsheets = $spreadsheets;

    $service = new GoogleSheetsService(sheetsService: $sheets);

    expect($service->readSettings())->toBe([
        'congregation' => 'West Tagalog Congregation',
        'address' => '06 Lipnica St.',
        'title' => 'AUDIO/VIDEO SCHEDULE',
        'include_preparation' => true,
    ]);
});

it('wraps google api failures', function () {
    Config::set('google.spreadsheet_id', 'spreadsheet-id');

    $values = Mockery::mock(SpreadsheetsValues::class);
    $values->shouldReceive('get')
        ->once()
        ->andThrow(new RuntimeException('API unavailable'));

    $sheets = Mockery::mock(Sheets::class);
    $sheets->spreadsheets_values = $values;

    $service = new GoogleSheetsService(sheetsService: $sheets);

    expect(fn () => $service->readSheet('Settings'))
        ->toThrow(SpreadsheetException::class, 'Google Sheets API error: API unavailable');
});

afterEach(function () {
    Mockery::close();
});
