<?php

use Inertia\Testing\AssertableInertia as Assert;

it('returns a health status payload', function () {
    $this->get('/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

it('redirects the home page to the schedule', function () {
    $this->get('/')
        ->assertRedirect('/schedule');
});

it('renders the schedule page', function () {
    fakeBrothersStorage();

    $this->get('/schedule')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Schedule/Index'));
});

it('renders the meetings page', function () {
    fakeBrothersStorage();

    $this->get('/meetings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Meetings/Index'));
});

it('renders the brothers page', function () {
    fakeBrothersStorage();

    $this->get('/brothers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Brothers/Index'));
});

it('renders the settings page', function () {
    $this->get('/settings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Settings/Index'));
});
