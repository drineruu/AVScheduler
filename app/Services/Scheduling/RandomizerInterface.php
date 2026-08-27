<?php

namespace App\Services\Scheduling;

interface RandomizerInterface
{
    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    public function shuffle(array $items): array;

    public function nextFloat(): float;
}
