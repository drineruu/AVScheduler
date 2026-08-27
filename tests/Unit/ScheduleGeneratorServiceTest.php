<?php

use App\Enums\TrainingRole;
use App\Services\MeetingTypeService;
use App\Services\ScheduleGeneratorService;
use App\Services\Scheduling\SeededRandomizer;

function scheduleBrother(int $id, array $overrides = []): array
{
    return array_merge([
        'id' => $id,
        'name' => "Brother {$id}",
        'is_ms' => false,
        'can_audio' => false,
        'can_video' => false,
        'can_mic' => false,
        'can_stage' => false,
        'can_prep' => false,
        'training_role' => TrainingRole::None->value,
    ], $overrides);
}

function scheduleMeeting(string $date, array $overrides = []): array
{
    return array_merge([
        'date' => $date,
        'skip' => false,
        'allow_trainee' => true,
        'busy_brothers' => [],
    ], $overrides);
}

function makeGenerator(int $seed = 1): ScheduleGeneratorService
{
    return new ScheduleGeneratorService(new MeetingTypeService, new SeededRandomizer($seed));
}

it('excludes skipped meetings from the generated schedule', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [scheduleBrother(1, ['can_audio' => true, 'is_ms' => true])],
        [
            scheduleMeeting('2026-08-02', ['skip' => true]),
            scheduleMeeting('2026-08-09'),
        ],
    );

    expect($result['schedule'])->toHaveCount(1)
        ->and($result['schedule'][0]['date'])->toBe('2026-08-09');
});

it('generates meetings in chronological order', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [scheduleBrother(1, ['can_audio' => true, 'can_video' => true, 'can_mic' => true, 'can_stage' => true, 'is_ms' => true])],
        [
            scheduleMeeting('2026-08-16'),
            scheduleMeeting('2026-08-02'),
        ],
    );

    expect($result['schedule'][0]['date'])->toBe('2026-08-02')
        ->and($result['schedule'][1]['date'])->toBe('2026-08-16');
});

it('enforces audio and video cooldown across consecutive weeks', function () {
    $brothers = [
        scheduleBrother(1, ['can_audio' => true, 'can_video' => true, 'is_ms' => true]),
        scheduleBrother(2, ['can_audio' => true, 'can_video' => true, 'is_ms' => true]),
    ];

    $result = makeGenerator(10)->generate(
        ['include_preparation' => false],
        $brothers,
        [
            scheduleMeeting('2026-08-02'),
            scheduleMeeting('2026-08-09'),
        ],
    );

    $firstAv = array_filter([$result['schedule'][0]['audio'], $result['schedule'][0]['video']]);
    $secondAv = array_filter([$result['schedule'][1]['audio'], $result['schedule'][1]['video']]);

    expect($firstAv)->not->toBeEmpty()
        ->and(array_intersect($firstAv, $secondAv))->toBeEmpty();
});

it('requires a ministerial servant on audio or video', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, ['can_audio' => true, 'can_video' => true]),
            scheduleBrother(2, ['can_audio' => true, 'can_video' => true]),
        ],
        [scheduleMeeting('2026-08-02')],
    );

    expect($result['schedule'][0]['audio'])->toBeNull()
        ->and($result['schedule'][0]['video'])->toBeNull()
        ->and($result['warnings'])->not->toBeEmpty();
});

it('assigns a ministerial servant when one is available', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, ['can_audio' => true, 'can_video' => true]),
            scheduleBrother(2, ['can_audio' => true, 'can_video' => true, 'is_ms' => true]),
        ],
        [scheduleMeeting('2026-08-02')],
    );

    $assigned = [$result['schedule'][0]['audio'], $result['schedule'][0]['video']];

    expect($assigned)->toContain(2);
});

it('excludes busy brothers from assignments', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, ['can_audio' => true, 'can_video' => true, 'can_mic' => true, 'can_stage' => true, 'is_ms' => true]),
            scheduleBrother(2, ['can_audio' => true, 'can_video' => true, 'can_mic' => true, 'can_stage' => true, 'is_ms' => true]),
        ],
        [scheduleMeeting('2026-08-02', ['busy_brothers' => [1]])],
    );

    expect($result['schedule'][0])->not->toContain(1);
});

it('does not assign trainees when trainees are disabled', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, [
                'can_audio' => true,
                'is_ms' => true,
                'training_role' => TrainingRole::TraineeAudio->value,
            ]),
            scheduleBrother(2, ['can_audio' => true, 'can_video' => true, 'is_ms' => true]),
            scheduleBrother(3, ['can_video' => true, 'is_ms' => true]),
        ],
        [scheduleMeeting('2026-08-02', ['allow_trainee' => false])],
    );

    expect($result['schedule'][0]['audio'])->toBe(2)
        ->and($result['schedule'][0]['video'])->not->toBe(1);
});

it('can assign trainees when trainees are enabled', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, [
                'can_audio' => true,
                'is_ms' => true,
                'training_role' => TrainingRole::TraineeAudio->value,
            ]),
            scheduleBrother(2, ['can_video' => true, 'is_ms' => true]),
        ],
        [scheduleMeeting('2026-08-02')],
    );

    expect($result['schedule'][0]['audio'])->toBe(1)
        ->and($result['schedule'][0]['video'])->toBe(2);
});

it('never assigns brothers without the required qualification', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [scheduleBrother(1, ['can_mic' => true, 'is_ms' => true])],
        [scheduleMeeting('2026-08-02')],
    );

    expect($result['schedule'][0]['audio'])->toBeNull()
        ->and($result['schedule'][0]['video'])->toBeNull()
        ->and($result['warnings'])->not->toBeEmpty();
});

it('prefers trainer roles for matching audio and video assignments', function () {
    $result = makeGenerator(25)->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, [
                'can_audio' => true,
                'is_ms' => true,
                'training_role' => TrainingRole::TrainerAudio->value,
            ]),
            scheduleBrother(2, [
                'can_audio' => true,
                'is_ms' => true,
            ]),
            scheduleBrother(3, [
                'can_video' => true,
                'is_ms' => true,
            ]),
        ],
        [scheduleMeeting('2026-08-07')],
    );

    expect($result['schedule'][0]['audio'])->toBe(1)
        ->and($result['schedule'][0]['video'])->toBe(3);
});

it('attempts midweek to weekend audio and video swaps', function () {
    $result = makeGenerator(50)->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, [
                'can_audio' => true,
                'can_video' => true,
                'is_ms' => true,
                'training_role' => TrainingRole::TrainerAudio->value,
            ]),
            scheduleBrother(2, [
                'can_audio' => true,
                'can_video' => true,
                'is_ms' => true,
                'training_role' => TrainingRole::TrainerVideo->value,
            ]),
        ],
        [
            scheduleMeeting('2026-08-06'),
            scheduleMeeting('2026-08-09'),
        ],
    );

    expect($result['schedule'][0]['audio'])->toBe(1)
        ->and($result['schedule'][0]['video'])->toBe(2)
        ->and($result['schedule'][1]['audio'])->toBe(2)
        ->and($result['schedule'][1]['video'])->toBe(1);
});

it('balances workload when selecting candidates', function () {
    $result = makeGenerator(100)->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, ['can_mic' => true, 'is_ms' => true]),
            scheduleBrother(2, ['can_mic' => true, 'is_ms' => true]),
        ],
        [
            scheduleMeeting('2026-08-02'),
            scheduleMeeting('2026-08-09'),
        ],
    );

    expect($result['schedule'][0]['mics'])->not->toBeNull()
        ->and($result['schedule'][1]['mics'])->not->toBe($result['schedule'][0]['mics']);
});

it('assigns preparation when enabled', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => true],
        [
            scheduleBrother(1, ['can_audio' => true, 'can_video' => true, 'can_mic' => true, 'can_stage' => true, 'is_ms' => true]),
            scheduleBrother(2, ['can_prep' => true]),
        ],
        [scheduleMeeting('2026-08-02')],
    );

    expect($result['schedule'][0]['preparation'])->toBe(2);
});

it('leaves preparation empty when disabled in settings', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [
            scheduleBrother(1, ['can_audio' => true, 'can_video' => true, 'can_mic' => true, 'can_stage' => true, 'is_ms' => true]),
            scheduleBrother(2, ['can_prep' => true]),
        ],
        [scheduleMeeting('2026-08-02')],
    );

    expect($result['schedule'][0]['preparation'])->toBeNull();
});

it('returns warnings for impossible stage assignments', function () {
    $result = makeGenerator()->generate(
        ['include_preparation' => false],
        [scheduleBrother(1, ['can_audio' => true, 'can_video' => true, 'can_mic' => true, 'is_ms' => true])],
        [scheduleMeeting('2026-08-02')],
    );

    expect($result['schedule'][0]['stage'])->toBeNull()
        ->and(collect($result['warnings'])->contains(
            fn (array $warning) => $warning['role'] === 'stage',
        ))->toBeTrue();
});
