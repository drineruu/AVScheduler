<?php

use App\Exceptions\SpreadsheetException;
use App\Services\SheetMapper;

it('maps sheet rows to associative arrays', function () {
    $rows = SheetMapper::rowsToAssociative(
        ['id', 'name'],
        [['1', 'Joel'], ['2', 'Mark']],
    );

    expect($rows)->toBe([
        ['id' => '1', 'name' => 'Joel'],
        ['id' => '2', 'name' => 'Mark'],
    ]);
});

it('converts associative data back to ordered rows', function () {
    $row = SheetMapper::associativeToRow(['id', 'name'], ['name' => 'Joel', 'id' => '1']);

    expect($row)->toBe(['1', 'Joel']);
});

it('parses booleans and csv ids', function () {
    expect(SheetMapper::toBool('TRUE'))->toBeTrue()
        ->and(SheetMapper::fromBool(false))->toBe('FALSE')
        ->and(SheetMapper::parseCsvInts('2,5'))->toBe([2, 5])
        ->and(SheetMapper::formatCsvInts([2, 5]))->toBe('2,5');
});

it('throws when csv ids are malformed', function () {
    SheetMapper::parseCsvInts('2,x', 'Meetings', 'busy_brothers');
})->throws(SpreadsheetException::class);
