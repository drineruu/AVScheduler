<?php

namespace App\Http\Requests\Concerns;

use App\Enums\TrainingRole;
use App\Repositories\BrotherRepository;
use Illuminate\Validation\Rule;

trait ValidatesBrotherInput
{
    /**
     * @return array<string, mixed>
     */
    protected function brotherRules(?int $ignoreId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId): void {
                    $name = trim((string) $value);

                    if ($name === '') {
                        return;
                    }

                    foreach ($this->brotherRepository()->all() as $brother) {
                        if ($ignoreId !== null && $brother['id'] === $ignoreId) {
                            continue;
                        }

                        if (strcasecmp($brother['name'], $name) === 0) {
                            $fail('A brother with this name already exists.');
                        }
                    }
                },
            ],
            'is_ms' => ['boolean'],
            'can_audio' => ['boolean'],
            'can_video' => ['boolean'],
            'can_mic' => ['boolean'],
            'can_stage' => ['boolean'],
            'can_prep' => ['boolean'],
            'training_role' => ['required', 'string', Rule::in(TrainingRole::values())],
        ];
    }

    protected function prepareBrotherInput(): void
    {
        $this->merge([
            'is_ms' => $this->boolean('is_ms'),
            'can_audio' => $this->boolean('can_audio'),
            'can_video' => $this->boolean('can_video'),
            'can_mic' => $this->boolean('can_mic'),
            'can_stage' => $this->boolean('can_stage'),
            'can_prep' => $this->boolean('can_prep'),
        ]);
    }

    protected function brotherRepository(): BrotherRepository
    {
        return app(BrotherRepository::class);
    }
}
