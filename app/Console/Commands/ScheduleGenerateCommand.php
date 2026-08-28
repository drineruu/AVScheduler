<?php

namespace App\Console\Commands;

use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SettingsRepository;
use App\Services\ScheduleGeneratorService;
use Illuminate\Console\Command;

class ScheduleGenerateCommand extends Command
{
    protected $signature = 'schedule:generate';

    protected $description = 'Generate the AV schedule from brothers and meetings data';

    public function handle(
        SettingsRepository $settings,
        BrotherRepository $brothers,
        MeetingRepository $meetings,
        ScheduleRepository $schedule,
        ScheduleGeneratorService $generator,
    ): int {
        $result = $generator->generate(
            $settings->get(),
            $brothers->all(),
            $meetings->all(),
        );

        $schedule->save($result['schedule']);

        $assignmentCount = array_sum(array_map(
            static fn (array $row) => count(array_filter(
                [$row['audio'], $row['video'], $row['mics'], $row['stage'], $row['preparation']],
                static fn ($value) => $value !== null,
            )),
            $result['schedule'],
        ));

        $this->info('Schedule generated successfully.');
        $this->line('Meetings: '.count($result['schedule']));
        $this->line('Assignments: '.$assignmentCount);
        $this->line('Warnings: '.count($result['warnings']));

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning['message']);
        }

        return self::SUCCESS;
    }
}
