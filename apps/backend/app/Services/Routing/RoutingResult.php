<?php

namespace App\Services\Routing;

class RoutingResult
{
    public function __construct(
        public readonly float $distanceKm,
        public readonly int $durationMinutes,
        public readonly array $geometry,
        public readonly string $provider,
        public readonly bool $isFallback = false,
    ) {}
}
