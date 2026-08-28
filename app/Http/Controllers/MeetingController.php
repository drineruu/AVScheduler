<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;
use App\Support\FlashMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function index(
        Request $request,
        MeetingRepository $meetings,
        BrotherRepository $brothers,
    ): Response {
        $month = $this->resolveMonth($request->query('month'));

        $filtered = array_values(array_filter(
            $meetings->all(),
            static fn (array $meeting) => str_starts_with($meeting['date'], $month),
        ));

        return Inertia::render('Meetings/Index', [
            'meetings' => $filtered,
            'brothers' => $brothers->all(),
            'month' => $month,
        ]);
    }

    public function create(BrotherRepository $brothers): Response
    {
        return Inertia::render('Meetings/Create', [
            'brothers' => $brothers->all(),
            'weekendDays' => config('schedule.weekend_days'),
        ]);
    }

    public function store(StoreMeetingRequest $request, MeetingRepository $meetings): RedirectResponse
    {
        $meetings->create($request->validated());

        return redirect()
            ->route('meetings.index', ['month' => substr($request->validated('date'), 0, 7)])
            ->with('success', FlashMessages::DATA_UPDATED);
    }

    public function edit(string $date, MeetingRepository $meetings, BrotherRepository $brothers): Response
    {
        $meeting = $meetings->findByDate($date);

        if ($meeting === null) {
            abort(404);
        }

        return Inertia::render('Meetings/Edit', [
            'meeting' => $meeting,
            'brothers' => $brothers->all(),
            'weekendDays' => config('schedule.weekend_days'),
        ]);
    }

    public function update(
        UpdateMeetingRequest $request,
        string $date,
        MeetingRepository $meetings,
    ): RedirectResponse {
        if ($meetings->findByDate($date) === null) {
            abort(404);
        }

        $meetings->update($date, $request->validated());

        return redirect()
            ->route('meetings.index', ['month' => substr($date, 0, 7)])
            ->with('success', FlashMessages::DATA_UPDATED);
    }

    public function destroy(string $date, MeetingRepository $meetings): RedirectResponse
    {
        if ($meetings->findByDate($date) === null) {
            abort(404);
        }

        $meetings->delete($date);

        return redirect()
            ->route('meetings.index', ['month' => substr($date, 0, 7)])
            ->with('success', FlashMessages::DATA_UPDATED);
    }

    private function resolveMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return $month;
        }

        return now()->format('Y-m');
    }
}
