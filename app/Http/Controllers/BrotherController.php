<?php

namespace App\Http\Controllers;

use App\Enums\TrainingRole;
use App\Http\Requests\StoreBrotherRequest;
use App\Http\Requests\UpdateBrotherRequest;
use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\ScheduleRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrotherController extends Controller
{
    public function index(BrotherRepository $brothers): Response
    {
        return Inertia::render('Brothers/Index', [
            'brothers' => $brothers->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Brothers/Create', [
            'trainingRoles' => TrainingRole::values(),
        ]);
    }

    public function store(StoreBrotherRequest $request, BrotherRepository $brothers): RedirectResponse
    {
        $brothers->create($request->validated());

        return redirect()
            ->route('brothers.index')
            ->with('success', 'Brother created successfully.');
    }

    public function edit(int $brother, BrotherRepository $brothers): Response
    {
        $record = $brothers->find($brother);

        if ($record === null) {
            abort(404);
        }

        return Inertia::render('Brothers/Edit', [
            'brother' => $record,
            'trainingRoles' => TrainingRole::values(),
        ]);
    }

    public function update(
        UpdateBrotherRequest $request,
        int $brother,
        BrotherRepository $brothers,
    ): RedirectResponse {
        if ($brothers->find($brother) === null) {
            abort(404);
        }

        $brothers->update($brother, $request->validated());

        return redirect()
            ->route('brothers.index')
            ->with('success', 'Brother updated successfully.');
    }

    public function destroy(
        int $brother,
        BrotherRepository $brothers,
        MeetingRepository $meetings,
        ScheduleRepository $schedule,
    ): RedirectResponse {
        if ($brothers->find($brother) === null) {
            abort(404);
        }

        if ($this->brotherIsReferenced($brother, $meetings, $schedule)) {
            return redirect()
                ->route('brothers.index')
                ->with('error', 'This brother cannot be deleted because they are referenced in a meeting or schedule.');
        }

        $brothers->delete($brother);

        return redirect()
            ->route('brothers.index')
            ->with('success', 'Brother deleted successfully.');
    }

    private function brotherIsReferenced(
        int $brotherId,
        MeetingRepository $meetings,
        ScheduleRepository $schedule,
    ): bool {
        foreach ($meetings->all() as $meeting) {
            if (in_array($brotherId, $meeting['busy_brothers'], true)) {
                return true;
            }
        }

        foreach ($schedule->all() as $assignment) {
            foreach (['audio', 'video', 'mics', 'stage', 'preparation'] as $field) {
                if ($assignment[$field] === $brotherId) {
                    return true;
                }
            }
        }

        return false;
    }
}
