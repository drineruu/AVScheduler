<?php

use App\Support\FlashMessages;
use Inertia\Testing\AssertableInertia as Assert;

function seedTestBrother(int $id = 1, string $name = 'Joel Angan-angan'): array
{
    return [
        'id' => (string) $id,
        'name' => $name,
        'is_ms' => 'true',
        'can_audio' => 'true',
        'can_video' => 'true',
        'can_mic' => 'true',
        'can_stage' => 'true',
        'can_prep' => 'false',
        'training_role' => 'NONE',
    ];
}

it('lists meetings for the selected month', function () {
    $storage = fakeBrothersStorage();
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), [
        [
            'date' => '2026-08-02',
            'skip' => 'false',
            'allow_trainee' => 'true',
            'busy_brothers' => '1',
        ],
        [
            'date' => '2026-09-01',
            'skip' => 'false',
            'allow_trainee' => 'true',
            'busy_brothers' => '',
        ],
    ]);

    $this->get(route('meetings.index', ['month' => '2026-08']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Meetings/Index')
            ->where('month', '2026-08')
            ->has('meetings', 1)
            ->where('meetings.0.date', '2026-08-02')
            ->where('meetings.0.type', 'Weekend'));
});

it('creates a meeting', function () {
    $storage = fakeBrothersStorage();
    $storage->seed('Brothers', config('google.sheets.brothers.headers'), [seedTestBrother()]);

    $this->post(route('meetings.store'), [
        'date' => '2026-08-02',
        'skip' => false,
        'allow_trainee' => true,
        'busy_brothers' => [1],
    ])
        ->assertRedirect(route('meetings.index', ['month' => '2026-08']))
        ->assertSessionHas('success', FlashMessages::DATA_UPDATED);

    $this->get(route('meetings.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('meetings', 1)
            ->where('meetings.0.date', '2026-08-02')
            ->where('meetings.0.skip', false)
            ->where('meetings.0.allow_trainee', true)
            ->where('meetings.0.busy_brothers', [1]));
});

it('updates a meeting', function () {
    $storage = fakeBrothersStorage();
    $storage->seed('Brothers', config('google.sheets.brothers.headers'), [
        seedTestBrother(),
        seedTestBrother(2, 'Noli Conrado'),
    ]);
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), [[
        'date' => '2026-08-02',
        'skip' => 'false',
        'allow_trainee' => 'true',
        'busy_brothers' => '1',
    ]]);

    $this->put(route('meetings.update', '2026-08-02'), [
        'skip' => true,
        'allow_trainee' => false,
        'busy_brothers' => [2],
    ])
        ->assertRedirect(route('meetings.index', ['month' => '2026-08']))
        ->assertSessionHas('success', FlashMessages::DATA_UPDATED);

    $this->get(route('meetings.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('meetings.0.skip', true)
            ->where('meetings.0.allow_trainee', false)
            ->where('meetings.0.busy_brothers', [2]));
});

it('deletes a meeting', function () {
    $storage = fakeBrothersStorage();
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), [[
        'date' => '2026-08-02',
        'skip' => 'false',
        'allow_trainee' => 'true',
        'busy_brothers' => '',
    ]]);

    $this->delete(route('meetings.destroy', '2026-08-02'))
        ->assertRedirect(route('meetings.index', ['month' => '2026-08']))
        ->assertSessionHas('success', FlashMessages::DATA_UPDATED);

    $this->get(route('meetings.index', ['month' => '2026-08']))
        ->assertInertia(fn (Assert $page) => $page->has('meetings', 0));
});

it('prevents duplicate meeting dates on create', function () {
    $storage = fakeBrothersStorage();
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), [[
        'date' => '2026-08-02',
        'skip' => 'false',
        'allow_trainee' => 'true',
        'busy_brothers' => '',
    ]]);

    $this->from(route('meetings.create'))
        ->post(route('meetings.store'), [
            'date' => '2026-08-02',
            'allow_trainee' => true,
            'busy_brothers' => [],
        ])
        ->assertRedirect(route('meetings.create'))
        ->assertSessionHasErrors(['date']);
});

it('rejects invalid busy brother references on create', function () {
    fakeBrothersStorage();

    $this->from(route('meetings.create'))
        ->post(route('meetings.store'), [
            'date' => '2026-08-02',
            'allow_trainee' => true,
            'busy_brothers' => [999],
        ])
        ->assertRedirect(route('meetings.create'))
        ->assertSessionHasErrors(['busy_brothers.0']);
});

it('validates meeting date format on create', function () {
    fakeBrothersStorage();

    $this->from(route('meetings.create'))
        ->post(route('meetings.store'), [
            'date' => '08/02/2026',
            'allow_trainee' => true,
            'busy_brothers' => [],
        ])
        ->assertRedirect(route('meetings.create'))
        ->assertSessionHasErrors(['date']);
});

it('returns 404 when editing a missing meeting', function () {
    fakeBrothersStorage();

    $this->get(route('meetings.edit', '2026-08-02'))->assertNotFound();
});

it('renders the create meeting page', function () {
    fakeBrothersStorage();

    $this->get(route('meetings.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Meetings/Create')
            ->has('brothers')
            ->has('weekendDays'));
});

it('renders the edit meeting page', function () {
    $storage = fakeBrothersStorage();
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), [[
        'date' => '2026-08-02',
        'skip' => 'false',
        'allow_trainee' => 'true',
        'busy_brothers' => '',
    ]]);

    $this->get(route('meetings.edit', '2026-08-02'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Meetings/Edit')
            ->where('meeting.date', '2026-08-02')
            ->where('meeting.type', 'Weekend'));
});

it('defaults to the current month when the filter is invalid', function () {
    fakeBrothersStorage();

    $this->get(route('meetings.index', ['month' => 'invalid']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('month', now()->format('Y-m')));
});
