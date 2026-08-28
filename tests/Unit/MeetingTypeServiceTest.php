<?php

use App\Enums\MeetingType;
use App\Services\MeetingTypeService;

it('classifies saturday and sunday as weekend meetings', function () {
    $service = new MeetingTypeService;

    expect($service->fromDate('2026-08-01')->value)->toBe(MeetingType::Weekend->value)
        ->and($service->fromDate('2026-08-02')->value)->toBe(MeetingType::Weekend->value);
});

it('classifies other weekdays as midweek meetings', function () {
    $service = new MeetingTypeService;

    expect($service->fromDate('2026-08-07')->value)->toBe(MeetingType::Midweek->value)
        ->and($service->fromDate('2026-08-05')->value)->toBe(MeetingType::Midweek->value);
});

it('uses the configured weekend days', function () {
    config(['schedule.weekend_days' => [5]]);

    $service = new MeetingTypeService;

    expect($service->fromDate('2026-08-07')->value)->toBe(MeetingType::Weekend->value)
        ->and($service->fromDate('2026-08-02')->value)->toBe(MeetingType::Midweek->value);
});

it('rejects invalid meeting dates', function () {
    $service = new MeetingTypeService;

    $service->fromDate('08/02/2026');
})->throws(App\Exceptions\SpreadsheetException::class);
