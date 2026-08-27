<?php

use App\Exceptions\SpreadsheetException;
use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SettingsRepository;
use Tests\Fakes\FakeSpreadsheetStorage;

function fakeStorage(): FakeSpreadsheetStorage
{
    return new FakeSpreadsheetStorage;
}

it('returns default settings when the settings sheet is empty', function () {
    $repository = new SettingsRepository(fakeStorage());

    expect($repository->get())->toBe(config('google.default_settings'));
});

it('reads and updates settings rows', function () {
    $storage = fakeStorage();
    $repository = new SettingsRepository($storage);

    $updated = $repository->update([
        'congregation' => 'West Tagalog Congregation',
        'address' => '06 Lipnica St.',
        'title' => 'AUDIO/VIDEO SCHEDULE',
        'include_preparation' => false,
    ]);

    expect($updated['congregation'])->toBe('West Tagalog Congregation')
        ->and($repository->get()['include_preparation'])->toBeFalse();
});

it('creates brothers with generated ids', function () {
    $repository = new BrotherRepository(fakeStorage());

    $first = $repository->create([
        'name' => 'Joel Angan-angan',
        'is_ms' => true,
        'can_audio' => true,
        'can_video' => true,
        'can_mic' => true,
        'can_stage' => true,
        'training_role' => 'NONE',
    ]);

    $second = $repository->create([
        'name' => 'Mark Santos',
        'can_audio' => true,
    ]);

    expect($first['id'])->toBe(1)
        ->and($second['id'])->toBe(2)
        ->and($repository->find(1)['name'])->toBe('Joel Angan-angan');
});

it('updates and deletes brothers', function () {
    $repository = new BrotherRepository(fakeStorage());
    $brother = $repository->create(['name' => 'Joel Angan-angan', 'can_audio' => true]);

    $updated = $repository->update($brother['id'], ['can_video' => true]);

    expect($updated['can_video'])->toBeTrue();

    $repository->delete($brother['id']);

    expect($repository->find($brother['id']))->toBeNull();
});

it('rejects malformed brother rows', function () {
    $storage = fakeStorage();
    $storage->seed('Brothers', config('google.sheets.brothers.headers'), [
        [
            'id' => '1',
            'name' => 'Joel',
            'is_ms' => 'TRUE',
            'can_audio' => 'TRUE',
            'can_video' => 'TRUE',
            'can_mic' => 'TRUE',
            'can_stage' => 'TRUE',
            'can_prep' => 'FALSE',
            'training_role' => 'Invalid Role',
        ],
    ]);

    $repository = new BrotherRepository($storage);
    $repository->all();
})->throws(SpreadsheetException::class);

it('creates and sorts meetings by date', function () {
    $repository = new MeetingRepository(fakeStorage());

    $repository->create([
        'date' => '2026-08-09',
        'busy_brothers' => [],
    ]);

    $repository->create([
        'date' => '2026-08-07',
        'allow_trainee' => false,
        'busy_brothers' => [1, 2],
    ]);

    $repository->create([
        'date' => '2026-08-02',
        'allow_trainee' => false,
        'busy_brothers' => [1, 2],
    ]);

    expect($repository->all()[0]['date'])->toBe('2026-08-02')
        ->and($repository->findByDate('2026-08-09')['allow_trainee'])->toBeTrue()
        ->and($repository->findByDate('2026-08-02')['type'])->toBe('Weekend')
        ->and($repository->findByDate('2026-08-07')['type'])->toBe('Midweek');
});

it('prevents duplicate meeting dates', function () {
    $repository = new MeetingRepository(fakeStorage());
    $repository->create(['date' => '2026-08-02']);

    $repository->create(['date' => '2026-08-02']);
})->throws(SpreadsheetException::class);

it('accepts valid busy brother references', function () {
    $brothers = new BrotherRepository(fakeStorage());
    $meetings = new MeetingRepository(fakeStorage());

    $brother = $brothers->create(['name' => 'Joel Angan-angan']);

    $meetings->assertValidBrotherReferences([$brother['id']], $brothers);

    expect(true)->toBeTrue();
});

it('rejects invalid busy brother references', function () {
    $brothers = new BrotherRepository(fakeStorage());
    $meetings = new MeetingRepository(fakeStorage());

    $meetings->assertValidBrotherReferences([999], $brothers);
})->throws(SpreadsheetException::class);

it('reads and writes schedule rows with nullable assignments', function () {
    $repository = new ScheduleRepository(fakeStorage());

    $repository->save([
        [
            'date' => '2026-08-02',
            'audio' => 1,
            'video' => 4,
            'mics' => 7,
            'stage' => 8,
            'preparation' => null,
        ],
    ]);

    expect($repository->all())->toBe([
        [
            'date' => '2026-08-02',
            'audio' => 1,
            'video' => 4,
            'mics' => 7,
            'stage' => 8,
            'preparation' => null,
        ],
    ]);

    $repository->clear();

    expect($repository->all())->toBe([]);
});
