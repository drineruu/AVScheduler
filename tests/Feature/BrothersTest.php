<?php

use App\Enums\TrainingRole;
use Inertia\Testing\AssertableInertia as Assert;

it('lists brothers on the index page', function () {
    fakeBrothersStorage();

    $this->get(route('brothers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Brothers/Index')
            ->has('brothers', 0));
});

it('creates a brother', function () {
    fakeBrothersStorage();

    $this->post(route('brothers.store'), [
        'name' => 'Joel Angan-angan',
        'is_ms' => true,
        'can_audio' => true,
        'can_video' => true,
        'can_mic' => true,
        'can_stage' => true,
        'can_prep' => false,
        'training_role' => TrainingRole::None->value,
    ])
        ->assertRedirect(route('brothers.index'))
        ->assertSessionHas('success');

    $this->get(route('brothers.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('brothers', 1)
            ->where('brothers.0.name', 'Joel Angan-angan')
            ->where('brothers.0.is_ms', true)
            ->where('brothers.0.training_role', TrainingRole::None->value));
});

it('updates a brother', function () {
    $storage = fakeBrothersStorage();
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
    ]]);

    $this->put(route('brothers.update', 1), [
        'name' => 'Joel A.',
        'is_ms' => false,
        'can_audio' => true,
        'can_video' => false,
        'can_mic' => true,
        'can_stage' => false,
        'can_prep' => true,
        'training_role' => TrainingRole::TraineeAudio->value,
    ])
        ->assertRedirect(route('brothers.index'))
        ->assertSessionHas('success');

    $this->get(route('brothers.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('brothers.0.name', 'Joel A.')
            ->where('brothers.0.is_ms', false)
            ->where('brothers.0.can_video', false)
            ->where('brothers.0.training_role', TrainingRole::TraineeAudio->value));
});

it('deletes a brother', function () {
    $storage = fakeBrothersStorage();
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
    ]]);

    $this->delete(route('brothers.destroy', 1))
        ->assertRedirect(route('brothers.index'))
        ->assertSessionHas('success');

    $this->get(route('brothers.index'))
        ->assertInertia(fn (Assert $page) => $page->has('brothers', 0));
});

it('prevents deleting a brother referenced in meetings', function () {
    $storage = fakeBrothersStorage();
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
    ]]);
    $storage->seed('Meetings', config('google.sheets.meetings.headers'), [[
        'date' => '2026-08-02',
        'skip' => 'false',
        'allow_trainee' => 'true',
        'busy_brothers' => '1',
    ]]);

    $this->delete(route('brothers.destroy', 1))
        ->assertRedirect(route('brothers.index'))
        ->assertSessionHas('error');

    $this->get(route('brothers.index'))
        ->assertInertia(fn (Assert $page) => $page->has('brothers', 1));
});

it('validates required brother fields on create', function () {
    fakeBrothersStorage();

    $this->from(route('brothers.create'))
        ->post(route('brothers.store'), [
            'name' => '',
            'training_role' => 'INVALID',
        ])
        ->assertRedirect(route('brothers.create'))
        ->assertSessionHasErrors(['name', 'training_role']);
});

it('rejects duplicate brother names on create', function () {
    $storage = fakeBrothersStorage();
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
    ]]);

    $this->from(route('brothers.create'))
        ->post(route('brothers.store'), [
            'name' => 'joel angan-angan',
            'training_role' => TrainingRole::None->value,
        ])
        ->assertRedirect(route('brothers.create'))
        ->assertSessionHasErrors(['name']);
});

it('allows keeping the same name when updating a brother', function () {
    $storage = fakeBrothersStorage();
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
    ]]);

    $this->put(route('brothers.update', 1), [
        'name' => 'Joel Angan-angan',
        'is_ms' => true,
        'can_audio' => true,
        'can_video' => true,
        'can_mic' => true,
        'can_stage' => true,
        'can_prep' => false,
        'training_role' => TrainingRole::None->value,
    ])->assertRedirect(route('brothers.index'));
});

it('returns 404 when editing a missing brother', function () {
    fakeBrothersStorage();

    $this->get(route('brothers.edit', 99))->assertNotFound();
});

it('renders the create brother page', function () {
    fakeBrothersStorage();

    $this->get(route('brothers.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Brothers/Create')
            ->has('trainingRoles'));
});

it('renders the edit brother page', function () {
    $storage = fakeBrothersStorage();
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
    ]]);

    $this->get(route('brothers.edit', 1))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Brothers/Edit')
            ->where('brother.name', 'Joel Angan-angan'));
});
