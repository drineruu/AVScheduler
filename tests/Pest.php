<?php

use App\Services\Contracts\SpreadsheetStorageInterface;
use Tests\Fakes\FakeSpreadsheetStorage;

pest()->extend(Tests\TestCase::class)
    ->in('Feature', 'Unit');

function fakeBrothersStorage(): FakeSpreadsheetStorage
{
    $storage = new FakeSpreadsheetStorage;
    $storage->seed('Brothers', config('google.sheets.brothers.headers'), []);
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), []);
    $storage->seed('Schedule', config('google.sheets.schedule.headers'), []);

    app()->instance(SpreadsheetStorageInterface::class, $storage);

    return $storage;
}
