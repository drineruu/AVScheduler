<?php

use App\Enums\TrainingRole;
use Inertia\Testing\AssertableInertia as Assert;

function seedSchedulableData(): void
{
    $storage = fakeBrothersStorage();
    $storage->seed('Settings', config('google.sheets.settings.headers'), [[
        'congregation' => 'Test Congregation',
        'address' => '123 Main St',
        'title' => 'AUDIO/VIDEO SCHEDULE',
        'include_preparation' => 'false',
    ]]);
    $storage->seed('Brothers', config('google.sheets.brothers.headers'), [[
        'id' => '1',
        'name' => 'Joel Angan-angan',
        'is_ms' => 'true',
        'can_audio' => 'true',
        'can_video' => 'true',
        'can_mic' => 'true',
        'can_stage' => 'true',
        'can_prep' => 'false',
        'training_role' => TrainingRole::None->value,
    ], [
        'id' => '2',
        'name' => 'Noli Conrado',
        'is_ms' => 'true',
        'can_audio' => 'true',
        'can_video' => 'true',
        'can_mic' => 'true',
        'can_stage' => 'true',
        'can_prep' => 'false',
        'training_role' => TrainingRole::None->value,
    ]]);
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), [[
        'date' => '2026-08-02',
        'skip' => 'false',
        'allow_trainee' => 'true',
        'busy_brothers' => '',
    ]]);
}

it('renders the schedule page with settings and brothers', function () {
    seedSchedulableData();

    $this->get(route('schedule.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Schedule/Index')
            ->has('schedule')
            ->has('brothers', 2)
            ->has('settings')
            ->where('settings.title', 'AUDIO/VIDEO SCHEDULE'));
});

it('filters the saved schedule by month', function () {
    $storage = fakeBrothersStorage();
    $storage->seed('Schedule', config('google.sheets.schedule.headers'), [
        [
            'date' => '2026-08-02',
            'audio' => '1',
            'video' => '2',
            'mics' => '1',
            'stage' => '2',
            'preparation' => '',
        ],
        [
            'date' => '2026-09-01',
            'audio' => '1',
            'video' => '2',
            'mics' => '1',
            'stage' => '2',
            'preparation' => '',
        ],
    ]);

    $this->get(route('schedule.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('schedule', 1)
            ->where('schedule.0.date', '2026-08-02'));
});

it('generates and saves a schedule', function () {
    seedSchedulableData();

    $this->post(route('schedule.generate'))
        ->assertRedirect(route('schedule.index', ['month' => '2026-08']))
        ->assertSessionHas('success')
        ->assertSessionHas('schedule_warnings');

    $this->get(route('schedule.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('schedule', 1)
            ->where('hasSavedSchedule', true)
            ->where('schedule.0.audio', fn ($value) => in_array($value, [1, 2], true))
            ->where('schedule.0.video', fn ($value) => in_array($value, [1, 2], true)));
});

it('shows generator warnings after schedule generation', function () {
    seedSchedulableData();

    $this->post(route('schedule.generate'))
        ->assertSessionHas('schedule_warnings');

    $this->get(route('schedule.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page->has('warnings'));
});

it('does not regenerate the schedule when visiting the index page', function () {
    seedSchedulableData();

    $this->post(route('schedule.generate'));

    $this->get(route('schedule.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page->has('schedule', 1));

    $storage = app(App\Services\Contracts\SpreadsheetStorageInterface::class);
    $storage->seed('Schedule', config('google.sheets.schedule.headers'), []);

    $this->get(route('schedule.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page->has('schedule', 0));
});

it('uses regenerate messaging when a schedule already exists', function () {
    seedSchedulableData();

    $storage = app(App\Services\Contracts\SpreadsheetStorageInterface::class);
    $storage->seed('Schedule', config('google.sheets.schedule.headers'), [[
        'date' => '2026-08-02',
        'audio' => '1',
        'video' => '2',
        'mics' => '1',
        'stage' => '2',
        'preparation' => '',
    ]]);

    $this->post(route('schedule.generate'))
        ->assertSessionHas('success', 'Schedule regenerated successfully.');
});
