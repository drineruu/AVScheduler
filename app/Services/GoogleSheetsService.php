<?php

namespace App\Services;

use App\Exceptions\SpreadsheetException;
use App\Services\Contracts\SpreadsheetStorageInterface;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\AddSheetRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\Request as SheetsRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GoogleSheetsService implements SpreadsheetStorageInterface
{
    private ?Sheets $sheets = null;

    public function __construct(
        private ?Client $client = null,
        private ?Sheets $sheetsService = null,
    ) {}

    public function readSheet(string $sheetName): array
    {
        return $this->remember($sheetName, function () use ($sheetName) {
            $values = $this->fetchValues("{$sheetName}!A1:Z");

            if ($values === []) {
                return [];
            }

            $headerRow = array_shift($values);

            if (! is_array($headerRow)) {
                return [];
            }

            return SheetMapper::rowsToAssociative($headerRow, $values);
        });
    }

    public function writeSheet(string $sheetName, array $headers, array $rows): void
    {
        $spreadsheetId = $this->spreadsheetId();
        $this->ensureSheetExists($sheetName);

        $payload = [
            $headers,
            ...array_map(
                static fn (array $row) => SheetMapper::associativeToRow($headers, $row),
                $rows,
            ),
        ];

        try {
            $this->service()->spreadsheets_values->clear(
                $spreadsheetId,
                "{$sheetName}!A:Z",
                new ClearValuesRequest,
            );

            $valueRange = new ValueRange([
                'values' => $payload,
            ]);

            $this->service()->spreadsheets_values->update(
                $spreadsheetId,
                "{$sheetName}!A1",
                $valueRange,
                ['valueInputOption' => 'RAW'],
            );
        } catch (Throwable $exception) {
            throw SpreadsheetException::apiFailure($exception->getMessage(), $exception);
        }

        $this->clearCache($sheetName);
    }

    public function sheetHasData(string $sheetName): bool
    {
        return $this->readSheet($sheetName) !== [];
    }

    public function ensureSheetExists(string $sheetName): void
    {
        if ($this->sheetTabExists($sheetName)) {
            return;
        }

        try {
            $request = new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    new SheetsRequest([
                        'addSheet' => new AddSheetRequest([
                            'properties' => [
                                'title' => $sheetName,
                            ],
                        ]),
                    ]),
                ],
            ]);

            $this->service()->spreadsheets->batchUpdate($this->spreadsheetId(), $request);
        } catch (Throwable $exception) {
            throw SpreadsheetException::apiFailure($exception->getMessage(), $exception);
        }
    }

    public function clearCache(?string $sheetName = null): void
    {
        if (! config('google.cache.enabled')) {
            return;
        }

        if ($sheetName === null) {
            foreach (config('google.sheets') as $sheet) {
                Cache::forget($this->cacheKey($sheet['name']));
            }

            return;
        }

        Cache::forget($this->cacheKey($sheetName));
    }

    /**
     * @return array<string, mixed>
     */
    public function readSettings(): array
    {
        $sheetName = config('google.sheets.settings.name');
        $rows = $this->readSheet($sheetName);

        if ($rows === []) {
            return config('google.default_settings');
        }

        $row = $rows[0];

        return [
            'congregation' => $row['congregation'] ?? '',
            'address' => $row['address'] ?? '',
            'title' => $row['title'] ?? config('google.default_settings.title'),
            'include_preparation' => SheetMapper::toBool($row['include_preparation'] ?? 'true'),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function writeSettings(array $settings): void
    {
        $headers = config('google.sheets.settings.headers');
        $sheetName = config('google.sheets.settings.name');

        $this->writeSheet($sheetName, $headers, [[
            'congregation' => (string) ($settings['congregation'] ?? ''),
            'address' => (string) ($settings['address'] ?? ''),
            'title' => (string) ($settings['title'] ?? config('google.default_settings.title')),
            'include_preparation' => SheetMapper::fromBool((bool) ($settings['include_preparation'] ?? false)),
        ]]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function fetchValues(string $range): array
    {
        $spreadsheetId = $this->spreadsheetId();

        try {
            $response = $this->service()->spreadsheets_values->get($spreadsheetId, $range);

            return $response->getValues() ?? [];
        } catch (Throwable $exception) {
            throw SpreadsheetException::apiFailure($exception->getMessage(), $exception);
        }
    }

    private function sheetTabExists(string $sheetName): bool
    {
        $spreadsheetId = $this->spreadsheetId();

        try {
            $spreadsheet = $this->service()->spreadsheets->get($spreadsheetId);

            foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
                if ($sheet->getProperties()?->getTitle() === $sheetName) {
                    return true;
                }
            }
        } catch (Throwable $exception) {
            throw SpreadsheetException::apiFailure($exception->getMessage(), $exception);
        }

        return false;
    }

    /**
     * @param  callable(): array<int, array<string, string>>  $callback
     * @return array<int, array<string, string>>
     */
    private function remember(string $sheetName, callable $callback): array
    {
        if (! config('google.cache.enabled')) {
            return $callback();
        }

        return Cache::remember(
            $this->cacheKey($sheetName),
            config('google.cache.ttl_seconds'),
            $callback,
        );
    }

    private function cacheKey(string $sheetName): string
    {
        return 'google_sheets.'.md5($this->spreadsheetId()).'.'.$sheetName;
    }

    private function spreadsheetId(): string
    {
        $spreadsheetId = config('google.spreadsheet_id');

        if (! is_string($spreadsheetId) || trim($spreadsheetId) === '') {
            throw SpreadsheetException::missingConfiguration('GOOGLE_SHEETS_SPREADSHEET_ID');
        }

        return trim($spreadsheetId);
    }

    private function service(): Sheets
    {
        if ($this->sheetsService instanceof Sheets) {
            return $this->sheetsService;
        }

        if ($this->sheets instanceof Sheets) {
            return $this->sheets;
        }

        $client = $this->client ?? $this->makeClient();
        $this->sheets = new Sheets($client);

        return $this->sheets;
    }

    private function makeClient(): Client
    {
        $credentials = config('google.service_account');

        if (! is_string($credentials) || trim($credentials) === '') {
            throw SpreadsheetException::missingConfiguration('GOOGLE_SERVICE_ACCOUNT_JSON');
        }

        $client = new Client;
        $client->setApplicationName(config('app.name', 'Audio Video Scheduler'));
        $client->setScopes([Sheets::SPREADSHEETS]);

        try {
            if (str_starts_with(trim($credentials), '{')) {
                $client->setAuthConfig(json_decode($credentials, true, flags: JSON_THROW_ON_ERROR));
            } else {
                $path = $credentials;

                if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
                    $path = base_path($path);
                }

                $client->setAuthConfig($path);
            }
        } catch (Throwable $exception) {
            throw SpreadsheetException::invalidCredentials();
        }

        return $client;
    }
}
