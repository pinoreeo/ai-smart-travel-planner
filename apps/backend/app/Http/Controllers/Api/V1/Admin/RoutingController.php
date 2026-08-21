<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Routing\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoutingController extends Controller
{
    use RespondsWithApiResponse;

    public function preview(Request $request, RoutingService $routing): JsonResponse
    {
        $data = $request->validate([
            'from_latitude' => ['required', 'numeric', 'between:-90,90'],
            'from_longitude' => ['required', 'numeric', 'between:-180,180'],
            'to_latitude' => ['required', 'numeric', 'between:-90,90'],
            'to_longitude' => ['required', 'numeric', 'between:-180,180'],
            'transport_mode' => ['required', Rule::in(['walking', 'motorcycle', 'car', 'cycling'])],
        ]);

        $route = $routing->route(
            (float) $data['from_latitude'],
            (float) $data['from_longitude'],
            (float) $data['to_latitude'],
            (float) $data['to_longitude'],
            $data['transport_mode'],
        );

        return $this->success([
            'distance_km' => $route->distanceKm,
            'duration_minutes' => $route->durationMinutes,
            'route_geometry' => $route->geometry,
            'provider' => $route->provider,
            'is_fallback' => $route->isFallback,
        ], 'Route preview generated successfully.');
    }
}
