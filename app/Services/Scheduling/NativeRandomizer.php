<?php

namespace App\Services\Scheduling;

class NativeRandomizer implements RandomizerInterface
{
    public function shuffle(array $items): array
    {
        shuffle($items);

        return $items;
    }

    public function nextFloat(): float
    {
        return mt_rand() / mt_getrandmax();
    }
}
