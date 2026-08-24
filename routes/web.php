<?php

use App\Http\Controllers\BrotherController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

Route::redirect('/', '/schedule');

// Add `auth` middleware to this group when authentication is introduced.
Route::middleware([])->group(function () {
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::get('/brothers', [BrotherController::class, 'index'])->name('brothers.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});
