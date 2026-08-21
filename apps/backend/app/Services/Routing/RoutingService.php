<?php

namespace App\Services\Routing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class RoutingService
{
    public function route(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        string $transportMode
    ): RoutingResult {
        $apiKey = (string) config('services.openrouteservice.key');

        if ($apiKey === '') {
            return $this->fallback($fromLat, $fromLng, $toLat, $toLng, $transportMode);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/geo+json, application/json',
            ])
                ->timeout(15)
                ->post($this->endpoint($transportMode), [
                    'coordinates' => [
                        [$fromLng, $fromLat],
                        [$toLng, $toLat],
                    ],
                ])
                ->throw();

            $feature = $response->json('features.0');
            $summary = $feature['properties']['summary'] ?? null;
            $geometry = $feature['geometry'] ?? null;

            if (! is_array($summary) || ! is_array($geometry)) {
                return $this->fallback($fromLat, $fromLng, $toLat, $toLng, $transportMode);
            }

            return new RoutingResult(
                distanceKm: round(((float) ($summary['distance'] ?? 0)) / 1000, 2),
                durationMinutes: max(1, (int) ceil(((float) ($summary['duration'] ?? 0)) / 60)),
                geometry: $geometry,
                provider: 'openrouteservice',
            );
        } catch (ConnectionException|RequestException) {
            return $this->fallback($fromLat, $fromLng, $toLat, $toLng, $transportMode);
        }
    }

    public function fallback(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        string $transportMode
    ): RoutingResult {
        $distanceKm = $this->distanceKm($fromLat, $fromLng, $toLat, $toLng);

        return new RoutingResult(
            distanceKm: round($distanceKm, 2),
            durationMinutes: $this->travelDurationMinutes($distanceKm, $transportMode),
            geometry: [
                'type' => 'LineString',
                'coordinates' => [
                    [$fromLng, $fromLat],
                    [$toLng, $toLat],
                ],
            ],
            provider: 'fallback',
            isFallback: true,
        );
    }

    private function endpoint(string $transportMode): string
    {
        $baseUrl = rtrim((string) config('services.openrouteservice.base_url'), '/');

        return "{$baseUrl}/v2/directions/{$this->profile($transportMode)}/geojson";
    }

    private function profile(string $transportMode): string
    {
        return match ($transportMode) {
            'walking' => 'foot-walking',
            'cycling' => 'cycling-regular',
            'motorcycle', 'car' => 'driving-car',
            default => 'driving-car',
        };
    }

    private function travelDurationMinutes(float $distanceKm, string $transportMode): int
    {
        $speedKmh = match ($transportMode) {
            'walking' => 5,
            'cycling' => 15,
            'motorcycle' => 30,
            'car' => 35,
            default => 25,
        };

        return max(5, (int) ceil(($distanceKm / $speedKmh) * 60));
    }

    private function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
