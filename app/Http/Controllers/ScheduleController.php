<?php

namespace App\Http\Controllers;

use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SettingsRepository;
use App\Services\ScheduleGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(
        Request $request,
        ScheduleRepository $schedule,
        BrotherRepository $brothers,
        SettingsRepository $settings,
    ): Response {
        $month = $this->resolveMonth($request->query('month'));
        $allSchedule = $schedule->all();

        $filtered = array_values(array_filter(
            $allSchedule,
            static fn (array $row) => str_starts_with($row['date'], $month),
        ));

        return Inertia::render('Schedule/Index', [
            'schedule' => $filtered,
            'brothers' => $brothers->all(),
            'settings' => $settings->get(),
            'month' => $month,
            'hasSavedSchedule' => $allSchedule !== [],
            'warnings' => $request->session()->get('schedule_warnings', []),
        ]);
    }

    public function generate(
        Request $request,
        SettingsRepository $settings,
        BrotherRepository $brothers,
        MeetingRepository $meetings,
        ScheduleRepository $schedule,
        ScheduleGeneratorService $generator,
    ): RedirectResponse {
        $result = $generator->generate(
            $settings->get(),
            $brothers->all(),
            $meetings->all(),
        );

        $hadSchedule = $schedule->all() !== [];

        $schedule->save($result['schedule']);

        $month = $this->resolveMonth($request->input('month'));

        if ($result['schedule'] !== []) {
            $month = substr($result['schedule'][0]['date'], 0, 7);
        }

        $message = $hadSchedule
            ? 'Schedule regenerated successfully.'
            : 'Schedule generated successfully.';

        return redirect()
            ->route('schedule.index', ['month' => $month])
            ->with('success', $message)
            ->with('schedule_warnings', $result['warnings']);
    }

    private function resolveMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return $month;
        }

        return now()->format('Y-m');
    }
}
