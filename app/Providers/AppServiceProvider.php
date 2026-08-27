<?php

namespace App\Providers;

use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SettingsRepository;
use App\Services\Contracts\SpreadsheetStorageInterface;
use App\Services\GoogleSheetsService;
use App\Services\MeetingTypeService;
use App\Services\ScheduleGeneratorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MeetingTypeService::class);
        $this->app->singleton(ScheduleGeneratorService::class);

        // Construct explicitly so Laravel does not auto-wire Google\Client / Sheets.
        $this->app->singleton(GoogleSheetsService::class, fn () => new GoogleSheetsService);
        $this->app->singleton(SpreadsheetStorageInterface::class, GoogleSheetsService::class);

        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(BrotherRepository::class);
        $this->app->singleton(MeetingRepository::class);
        $this->app->singleton(ScheduleRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
