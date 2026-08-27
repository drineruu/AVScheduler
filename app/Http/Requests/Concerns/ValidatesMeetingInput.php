<?php

namespace App\Http\Requests\Concerns;

use App\Repositories\BrotherRepository;
use App\Repositories\MeetingRepository;

trait ValidatesMeetingInput
{
    /**
     * @return array<string, mixed>
     */
    protected function meetingRules(?string $ignoreDate = null): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($ignoreDate): void {
                    $date = trim((string) $value);

                    if ($date === '') {
                        return;
                    }

                    if ($ignoreDate !== null && $date === $ignoreDate) {
                        return;
                    }

                    if ($this->meetingRepository()->findByDate($date) !== null) {
                        $fail('A meeting already exists for this date.');
                    }
                },
            ],
            'skip' => ['boolean'],
            'allow_trainee' => ['boolean'],
            'busy_brothers' => ['array'],
            'busy_brothers.*' => [
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->brotherRepository()->find((int) $value) === null) {
                        $fail('One or more selected brothers do not exist.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meetingUpdateRules(): array
    {
        return [
            'skip' => ['boolean'],
            'allow_trainee' => ['boolean'],
            'busy_brothers' => ['array'],
            'busy_brothers.*' => [
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->brotherRepository()->find((int) $value) === null) {
                        $fail('One or more selected brothers do not exist.');
                    }
                },
            ],
        ];
    }

    protected function prepareMeetingInput(): void
    {
        $this->merge([
            'skip' => $this->boolean('skip'),
            'allow_trainee' => $this->boolean('allow_trainee'),
            'busy_brothers' => array_values(array_map(
                'intval',
                $this->input('busy_brothers', []),
            )),
        ]);
    }

    protected function meetingRepository(): MeetingRepository
    {
        return app(MeetingRepository::class);
    }

    protected function brotherRepository(): BrotherRepository
    {
        return app(BrotherRepository::class);
    }
}
