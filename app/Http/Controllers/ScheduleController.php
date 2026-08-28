<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateScheduleRequest;
use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SettingsRepository;
use App\Services\ScheduleGeneratorService;
use App\Support\FlashMessages;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ScheduleController extends Controller
{
    public function index(
        Request $request,
        ScheduleRepository $schedule,
        BrotherRepository $brothers,
        SettingsRepository $settings,
    ): InertiaResponse {
        [$startDate, $endDate] = $this->resolveDateRange(
            $request->query('start_date'),
            $request->query('end_date'),
        );

        $allSchedule = $schedule->all();
        $filtered = $this->filterScheduleByDateRange($allSchedule, $startDate, $endDate);

        return Inertia::render('Schedule/Index', [
            'schedule' => $filtered,
            'brothers' => $brothers->all(),
            'settings' => $settings->get(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'hasSavedSchedule' => $allSchedule !== [],
            'warnings' => $request->session()->get('schedule_warnings', []),
        ]);
    }

    public function generate(
        GenerateScheduleRequest $request,
        SettingsRepository $settings,
        BrotherRepository $brothers,
        MeetingRepository $meetings,
        ScheduleRepository $schedule,
        ScheduleGeneratorService $generator,
    ): RedirectResponse {
        $startDate = $request->validated('start_date');
        $endDate = $request->validated('end_date');

        $meetingsInRange = $this->filterMeetingsByDateRange($meetings->all(), $startDate, $endDate);

        $result = $generator->generate(
            $settings->get(),
            $brothers->all(),
            $meetingsInRange,
        );

        $hadSchedule = $schedule->all() !== [];

        $merged = $this->mergeSchedule($schedule->all(), $result['schedule'], $startDate, $endDate);
        $schedule->save($merged);

        $message = $hadSchedule
            ? 'Schedule regenerated successfully.'
            : 'Schedule generated successfully.';

        return redirect()
            ->route('schedule.index', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ])
            ->with('success', $message)
            ->with('schedule_warnings', $result['warnings']);
    }

    public function pdf(
        Request $request,
        ScheduleRepository $schedule,
        BrotherRepository $brothers,
        SettingsRepository $settings,
    ): Response {
        [$startDate, $endDate] = $this->resolveDateRange(
            $request->query('start_date'),
            $request->query('end_date'),
        );

        $settingsData = $settings->get();
        $brotherNames = $this->brotherNameMap($brothers->all());

        $filtered = $this->filterScheduleByDateRange($schedule->all(), $startDate, $endDate);

        $rows = array_map(function (array $row) use ($brotherNames): array {
            return [
                'date_label' => $this->formatPdfDate($row['date']),
                'audio' => $this->assignmentLabel($row['audio'], $brotherNames),
                'video' => $this->assignmentLabel($row['video'], $brotherNames),
                'mics' => $this->assignmentLabel($row['mics'], $brotherNames),
                'stage' => $this->assignmentLabel($row['stage'], $brotherNames),
                'preparation' => $this->assignmentLabel($row['preparation'], $brotherNames),
            ];
        }, $filtered);

        return Pdf::loadView('pdf.schedule', [
            'settings' => $settingsData,
            'rows' => $rows,
            'periodLabel' => $this->formatPeriodLabel($startDate, $endDate),
        ])
            ->setPaper('a4', 'landscape')
            ->download("av-schedule-{$startDate}-to-{$endDate}.pdf");
    }

    /**
     * @param  array<int, array{date: string}>  $schedule
     * @return array<int, array{date: string}>
     */
    private function filterScheduleByDateRange(array $schedule, string $startDate, string $endDate): array
    {
        return array_values(array_filter(
            $schedule,
            static fn (array $row) => $row['date'] >= $startDate && $row['date'] <= $endDate,
        ));
    }

    /**
     * @param  array<int, array{date: string}>  $meetings
     * @return array<int, array{date: string}>
     */
    private function filterMeetingsByDateRange(array $meetings, string $startDate, string $endDate): array
    {
        return array_values(array_filter(
            $meetings,
            static fn (array $meeting) => $meeting['date'] >= $startDate && $meeting['date'] <= $endDate,
        ));
    }

    /**
     * @param  array<int, array{date: string}>  $existing
     * @param  array<int, array{date: string}>  $generated
     * @return array<int, array{date: string}>
     */
    private function mergeSchedule(array $existing, array $generated, string $startDate, string $endDate): array
    {
        $outsideRange = array_values(array_filter(
            $existing,
            static fn (array $row) => $row['date'] < $startDate || $row['date'] > $endDate,
        ));

        $merged = [...$outsideRange, ...$generated];

        usort($merged, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return array_values($merged);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(?string $startDate, ?string $endDate): array
    {
        if ($this->isValidDate($startDate) && $this->isValidDate($endDate) && $startDate <= $endDate) {
            return [$startDate, $endDate];
        }

        $now = now();

        return [
            $now->copy()->startOfMonth()->format('Y-m-d'),
            $now->copy()->endOfMonth()->format('Y-m-d'),
        ];
    }

    private function isValidDate(?string $date): bool
    {
        if (! is_string($date)) {
            return false;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    /**
     * @param  array<int, array{id: int, name: string}>  $brothers
     * @return array<int, string>
     */
    private function brotherNameMap(array $brothers): array
    {
        $map = [];

        foreach ($brothers as $brother) {
            $map[$brother['id']] = $brother['name'];
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $brotherNames
     */
    private function assignmentLabel(?int $brotherId, array $brotherNames): string
    {
        if ($brotherId === null) {
            return 'Unassigned';
        }

        return $brotherNames[$brotherId] ?? "#{$brotherId}";
    }

    private function formatPdfDate(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable
            ? $parsed->format('D, M j, Y')
            : $date;
    }

    private function formatPeriodLabel(string $startDate, string $endDate): string
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

        if (! $start instanceof \DateTimeImmutable || ! $end instanceof \DateTimeImmutable) {
            return "{$startDate} to {$endDate}";
        }

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->format('F j').' – '.$end->format('j, Y');
        }

        return $start->format('F j, Y').' – '.$end->format('F j, Y');
    }
}
