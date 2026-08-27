<?php

use Tests\Fakes\FakeSpreadsheetStorage;

it('reads and writes in-memory sheet data', function () {
    $storage = new FakeSpreadsheetStorage;
    $storage->seed('Settings', ['congregation', 'title'], [
        ['congregation' => 'West Tagalog Congregation', 'title' => 'AUDIO/VIDEO SCHEDULE'],
    ]);

    expect($storage->readSheet('Settings'))->toHaveCount(1)
        ->and($storage->sheetHasData('Settings'))->toBeTrue();

    $storage->writeSheet('Brothers', ['id', 'name'], [
        ['id' => '1', 'name' => 'Joel'],
    ]);

    expect($storage->readSheet('Brothers'))->toBe([
        ['id' => '1', 'name' => 'Joel'],
    ]);
});

it('returns an empty array for missing sheets', function () {
    $storage = new FakeSpreadsheetStorage;

    expect($storage->readSheet('Settings'))->toBe([])
        ->and($storage->sheetHasData('Settings'))->toBeFalse();
});
