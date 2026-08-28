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
    Route::post('/schedule/generate', [ScheduleController::class, 'generate'])->name('schedule.generate');
    Route::get('/schedule/pdf', [ScheduleController::class, 'pdf'])->name('schedule.pdf');
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{date}/edit', [MeetingController::class, 'edit'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('meetings.edit');
    Route::put('/meetings/{date}', [MeetingController::class, 'update'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('meetings.update');
    Route::delete('/meetings/{date}', [MeetingController::class, 'destroy'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('meetings.destroy');
    Route::get('/brothers', [BrotherController::class, 'index'])->name('brothers.index');
    Route::get('/brothers/create', [BrotherController::class, 'create'])->name('brothers.create');
    Route::post('/brothers', [BrotherController::class, 'store'])->name('brothers.store');
    Route::get('/brothers/{brother}/edit', [BrotherController::class, 'edit'])->name('brothers.edit');
    Route::put('/brothers/{brother}', [BrotherController::class, 'update'])->name('brothers.update');
    Route::delete('/brothers/{brother}', [BrotherController::class, 'destroy'])->name('brothers.destroy');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});
