<?php

namespace App\Services\Scheduling;

class SeededRandomizer implements RandomizerInterface
{
    public function __construct(private int $seed = 1) {}

    public function shuffle(array $items): array
    {
        mt_srand($this->seed);
        $shuffled = $items;
        shuffle($shuffled);
        $this->seed++;

        return $shuffled;
    }

    public function nextFloat(): float
    {
        mt_srand($this->seed);
        $value = mt_rand() / mt_getrandmax();
        $this->seed++;

        return $value;
    }
}
