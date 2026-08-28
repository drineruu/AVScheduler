<?php

namespace App\Services;

use App\Enums\MeetingType;
use App\Enums\TrainingRole;
use App\Services\Scheduling\RandomizerInterface;
use App\Services\Scheduling\SchedulingState;

class ScheduleGeneratorService
{
    /** @var array<int, array<string, mixed>> */
    private array $brothersById = [];

    /** @var array<string, array<string, mixed>> */
    private array $assignmentsByDate = [];

    public function __construct(
        private MeetingTypeService $meetingTypes,
        private ?RandomizerInterface $randomizer = null,
    ) {}

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<int, array{
     *     id: int,
     *     name: string,
     *     is_ms: bool,
     *     can_audio: bool,
     *     can_video: bool,
     *     can_mic: bool,
     *     can_stage: bool,
     *     can_prep: bool,
     *     training_role: string
     * }>  $brothers
     * @param  array<int, array{
     *     date: string,
     *     skip: bool,
     *     allow_trainee: bool,
     *     busy_brothers: array<int, int>
     * }>  $meetings
     * @return array{
     *     schedule: array<int, array{
     *         date: string,
     *         audio: int|null,
     *         video: int|null,
     *         mics: int|null,
     *         stage: int|null,
     *         preparation: int|null
     *     }>,
     *     warnings: array<int, array{date: string, role: string, message: string}>
     * }
     */
    public function generate(array $settings, array $brothers, array $meetings): array
    {
        $randomizer = $this->randomizer ?? new Scheduling\NativeRandomizer;
        $this->brothersById = [];
        foreach ($brothers as $brother) {
            $this->brothersById[$brother['id']] = $brother;
        }

        $this->assignmentsByDate = [];
        $state = new SchedulingState;
        $state->initialize($brothers);
        $warnings = [];
        $schedule = [];

        $activeMeetings = array_values(array_filter(
            $meetings,
            static fn (array $meeting) => ! ($meeting['skip'] ?? false),
        ));

        usort($activeMeetings, static fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        foreach ($activeMeetings as $meeting) {
            $date = $meeting['date'];
            $assignedToday = [];

            [$audio, $video, $avWarnings] = $this->assignAudioVideo($meeting, $assignedToday, $state, $randomizer);
            $warnings = [...$warnings, ...$avWarnings];
            if ($audio !== null) {
                $assignedToday[] = $audio;
            }
            if ($video !== null) {
                $assignedToday[] = $video;
            }

            $mics = null;
            $stage = null;

            foreach (['mics' => 'can_mic', 'stage' => 'can_stage'] as $role => $qualification) {
                $brotherId = $this->selectCandidate($meeting, $role, $qualification, $assignedToday, $state, $randomizer);

                if ($brotherId === null) {
                    $warnings[] = $this->warning($date, $role, $this->unassignedReason($role));
                } else {
                    $assignedToday[] = $brotherId;
                    $state->recordAssignment($brotherId, $role, $date);

                    if ($role === 'mics') {
                        $mics = $brotherId;
                    } else {
                        $stage = $brotherId;
                    }
                }
            }

            $scheduleRow = [
                'date' => $date,
                'audio' => $audio,
                'video' => $video,
                'mics' => $mics,
                'stage' => $stage,
                'preparation' => null,
            ];

            if ((bool) ($settings['include_preparation'] ?? false)) {
                $prep = $this->selectCandidate($meeting, 'preparation', 'can_prep', $assignedToday, $state, $randomizer, applyAvCooldown: false);

                if ($prep === null) {
                    $warnings[] = $this->warning($date, 'preparation', 'No available brother has Preparation qualification.');
                } else {
                    $state->recordAssignment($prep, 'preparation', $date);
                }

                $scheduleRow['preparation'] = $prep;
            }

            $this->assignmentsByDate[$date] = $scheduleRow;
            $schedule[] = $scheduleRow;

            if ($audio !== null) {
                $state->recordAssignment($audio, 'audio', $date);
            }
            if ($video !== null) {
                $state->recordAssignment($video, 'video', $date);
            }
        }

        return [
            'schedule' => $schedule,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $meeting
     * @param  array<int, int>  $assignedToday
     * @return array{0: int|null, 1: int|null, 2: array<int, array{date: string, role: string, message: string}>}
     */
    private function assignAudioVideo(
        array $meeting,
        array $assignedToday,
        SchedulingState $state,
        RandomizerInterface $randomizer,
    ): array {
        $date = $meeting['date'];
        $swapPreferences = $this->weekendSwapPreferences($date);
        $warnings = [];

        $audio = $this->selectCandidate(
            $meeting,
            'audio',
            'can_audio',
            $assignedToday,
            $state,
            $randomizer,
            preferredBrotherIds: $swapPreferences['audio'],
        );

        $videoPoolRequiresMs = $audio !== null && ! ($this->brothersById[$audio]['is_ms'] ?? false);

        $video = $this->selectCandidate(
            $meeting,
            'video',
            'can_video',
            $audio !== null ? [...$assignedToday, $audio] : $assignedToday,
            $state,
            $randomizer,
            preferredBrotherIds: $swapPreferences['video'],
            requireMs: $videoPoolRequiresMs,
        );

        if ($this->violatesMsRequirement($audio, $video)) {
            $msAudio = $this->selectCandidate(
                $meeting,
                'audio',
                'can_audio',
                $assignedToday,
                $state,
                $randomizer,
                preferredBrotherIds: $swapPreferences['audio'],
                requireMs: true,
            );

            $msVideo = $this->selectCandidate(
                $meeting,
                'video',
                'can_video',
                $msAudio !== null ? [...$assignedToday, $msAudio] : $assignedToday,
                $state,
                $randomizer,
                preferredBrotherIds: $swapPreferences['video'],
                requireMs: $msAudio === null,
            );

            if (! $this->violatesMsRequirement($msAudio, $msVideo)) {
                return [$msAudio, $msVideo, $warnings];
            }

            $warnings[] = $this->msRequirementWarning($date);

            return [null, null, $warnings];
        }

        if (! $this->canSatisfyMsRequirement($meeting, $assignedToday, $state) && ($audio !== null || $video !== null)) {
            $warnings[] = $this->msRequirementWarning($date);

            return [null, null, $warnings];
        }

        if ($audio === null) {
            $warnings[] = $this->warning($date, 'audio', $this->unassignedReason('audio'));
        }

        if ($video === null) {
            $warnings[] = $this->warning($date, 'video', $this->unassignedReason('video'));
        }

        return [$audio, $video, $warnings];
    }

    /**
     * @param  array<string, mixed>  $meeting
     * @param  array<int, int>  $assignedToday
     * @param  array<int, int>  $preferredBrotherIds
     */
    private function selectCandidate(
        array $meeting,
        string $role,
        string $qualificationField,
        array $assignedToday,
        SchedulingState $state,
        RandomizerInterface $randomizer,
        array $preferredBrotherIds = [],
        bool $requireMs = false,
        bool $applyAvCooldown = true,
    ): ?int {
        $candidates = [];

        foreach ($this->brothersById as $brother) {
            if (! $this->isEligibleBrother($brother, $meeting, $role, $qualificationField, $assignedToday, $state, $requireMs, $applyAvCooldown)) {
                continue;
            }

            $trainingRole = TrainingRole::parse($brother['training_role']);
            $candidates[] = [
                'id' => $brother['id'],
                'role_count' => $state->roleCount($brother['id'], $role),
                'total_count' => $state->totalCount($brother['id']),
                'trainer_rank' => $this->trainerRank($trainingRole, $role),
                'swap_rank' => in_array($brother['id'], $preferredBrotherIds, true) ? 0 : 1,
                'random' => $randomizer->nextFloat(),
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static function (array $a, array $b): int {
            return [$a['role_count'], $a['total_count'], $a['trainer_rank'], $a['swap_rank'], $a['random']]
                <=> [$b['role_count'], $b['total_count'], $b['trainer_rank'], $b['swap_rank'], $b['random']];
        });

        return $candidates[0]['id'];
    }

    /**
     * @param  array<string, mixed>  $brother
     */
    private function passesTraineeRules(array $brother, string $role, bool $allowTrainee): bool
    {
        $trainingRole = TrainingRole::parse($brother['training_role']);

        if (! $trainingRole->isTrainee()) {
            return true;
        }

        if (! $allowTrainee) {
            return false;
        }

        return match ($role) {
            'audio' => $trainingRole->isTraineeForAudio(),
            'video' => $trainingRole->isTraineeForVideo(),
            default => false,
        };
    }

    private function trainerRank(TrainingRole $trainingRole, string $role): int
    {
        return match ($role) {
            'audio' => $trainingRole->isTrainerForAudio() ? 0 : 1,
            'video' => $trainingRole->isTrainerForVideo() ? 0 : 1,
            default => 1,
        };
    }

    private function violatesMsRequirement(?int $audio, ?int $video): bool
    {
        if ($audio === null || $video === null) {
            return false;
        }

        $audioBrother = $this->brothersById[$audio] ?? null;
        $videoBrother = $this->brothersById[$video] ?? null;

        if ($audioBrother === null || $videoBrother === null) {
            return true;
        }

        return ! ($audioBrother['is_ms'] ?? false) && ! ($videoBrother['is_ms'] ?? false);
    }

    /**
     * @return array{audio: array<int, int>, video: array<int, int>}
     */
    private function weekendSwapPreferences(string $weekendDate): array
    {
        if ($this->meetingTypes->fromDate($weekendDate) !== MeetingType::Weekend) {
            return ['audio' => [], 'video' => []];
        }

        $week = SchedulingState::isoWeek($weekendDate);
        $midweekAssignment = null;

        foreach ($this->assignmentsByDate as $date => $assignment) {
            if (SchedulingState::isoWeek($date) !== $week) {
                continue;
            }

            if ($this->meetingTypes->fromDate($date) !== MeetingType::Midweek) {
                continue;
            }

            $midweekAssignment = $assignment;
            break;
        }

        if ($midweekAssignment === null) {
            return ['audio' => [], 'video' => []];
        }

        $audioPrefs = [];
        $videoPrefs = [];

        if ($midweekAssignment['video'] !== null) {
            $audioPrefs[] = $midweekAssignment['video'];
        }

        if ($midweekAssignment['audio'] !== null) {
            $videoPrefs[] = $midweekAssignment['audio'];
        }

        return [
            'audio' => $this->filterSwapCandidates($audioPrefs, 'audio'),
            'video' => $this->filterSwapCandidates($videoPrefs, 'video'),
        ];
    }

    /**
     * @param  array<int, int>  $brotherIds
     * @return array<int, int>
     */
    private function filterSwapCandidates(array $brotherIds, string $role): array
    {
        $qualification = $role === 'audio' ? 'can_audio' : 'can_video';

        return array_values(array_filter($brotherIds, function (int $brotherId) use ($qualification): bool {
            return ($this->brothersById[$brotherId][$qualification] ?? false) === true;
        }));
    }

    /**
     * @param  array<string, mixed>  $brother
     * @param  array<string, mixed>  $meeting
     * @param  array<int, int>  $assignedToday
     */
    private function isEligibleBrother(
        array $brother,
        array $meeting,
        string $role,
        string $qualificationField,
        array $assignedToday,
        SchedulingState $state,
        bool $requireMs = false,
        bool $applyAvCooldown = true,
    ): bool {
        if (! ($brother[$qualificationField] ?? false)) {
            return false;
        }

        if ($requireMs && ! ($brother['is_ms'] ?? false)) {
            return false;
        }

        if (in_array($brother['id'], $meeting['busy_brothers'] ?? [], true)) {
            return false;
        }

        if (in_array($brother['id'], $assignedToday, true)) {
            return false;
        }

        if (! $this->passesTraineeRules($brother, $role, (bool) ($meeting['allow_trainee'] ?? false))) {
            return false;
        }

        if ($applyAvCooldown && in_array($role, ['audio', 'video'], true) && $state->isOnAvCooldown($brother['id'], $meeting['date'])) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $meeting
     * @param  array<int, int>  $assignedToday
     */
    private function canSatisfyMsRequirement(array $meeting, array $assignedToday, SchedulingState $state): bool
    {
        $audioCandidates = [];
        $videoCandidates = [];

        foreach ($this->brothersById as $brother) {
            if ($this->isEligibleBrother($brother, $meeting, 'audio', 'can_audio', $assignedToday, $state)) {
                $audioCandidates[] = $brother['id'];
            }

            if ($this->isEligibleBrother($brother, $meeting, 'video', 'can_video', $assignedToday, $state)) {
                $videoCandidates[] = $brother['id'];
            }
        }

        foreach ($audioCandidates as $audioId) {
            foreach ($videoCandidates as $videoId) {
                if ($audioId === $videoId) {
                    continue;
                }

                if (($this->brothersById[$audioId]['is_ms'] ?? false) || ($this->brothersById[$videoId]['is_ms'] ?? false)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{date: string, role: string, message: string}
     */
    private function msRequirementWarning(string $date): array
    {
        return $this->warning(
            $date,
            'audio/video',
            sprintf(
                'Unable to assign Audio/Video for %s because no qualified Ministerial Servant is available.',
                $this->formatDisplayDate($date),
            ),
        );
    }

    /**
     * @return array{date: string, role: string, message: string}
     */
    private function warning(string $date, string $role, string $message): array
    {
        return [
            'date' => $date,
            'role' => $role,
            'message' => $message,
        ];
    }

    private function unassignedReason(string $role): string
    {
        return match ($role) {
            'audio' => 'No available brother has Audio qualification.',
            'video' => 'No available brother has Video qualification.',
            'mics' => 'No available brother has Microphone qualification.',
            'stage' => 'No available brother has Stage qualification.',
            'preparation' => 'No available brother has Preparation qualification.',
            default => 'No available brother could be assigned.',
        };
    }

    private function formatDisplayDate(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable
            ? $parsed->format('F j')
            : $date;
    }
}
