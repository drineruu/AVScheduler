<?php

namespace App\Enums;

enum TrainingRole: string
{
    case None = 'NONE';
    case TraineeAudio = 'Trainee - Audio';
    case TraineeVideo = 'Trainee - Video';
    case TraineeBoth = 'Trainee - Both';
    case TrainerAudio = 'Trainer - Audio';
    case TrainerVideo = 'Trainer - Video';
    case TrainerBoth = 'Trainer - Both';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }

    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array($value, self::values(), true);
    }

    public static function parse(string $value): self
    {
        return self::tryFrom($value) ?? self::None;
    }

    public function isTrainee(): bool
    {
        return in_array($this, [self::TraineeAudio, self::TraineeVideo, self::TraineeBoth], true);
    }

    public function isTrainer(): bool
    {
        return in_array($this, [self::TrainerAudio, self::TrainerVideo, self::TrainerBoth], true);
    }

    public function isTraineeForAudio(): bool
    {
        return $this === self::TraineeAudio || $this === self::TraineeBoth;
    }

    public function isTraineeForVideo(): bool
    {
        return $this === self::TraineeVideo || $this === self::TraineeBoth;
    }

    public function isTrainerForAudio(): bool
    {
        return $this === self::TrainerAudio || $this === self::TrainerBoth;
    }

    public function isTrainerForVideo(): bool
    {
        return $this === self::TrainerVideo || $this === self::TrainerBoth;
    }
}
