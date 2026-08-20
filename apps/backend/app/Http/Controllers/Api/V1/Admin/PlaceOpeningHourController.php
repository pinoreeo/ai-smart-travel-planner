<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\PlaceOpeningHourResource;
use App\Models\Place;
use App\Models\PlaceOpeningHour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlaceOpeningHourController extends Controller
{
    use RespondsWithApiResponse;

    private const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function index(Request $request, Place $place): JsonResponse
    {
        $openingHours = $place->openingHours()
            ->orderByRaw(
                "case day_of_week
                    when 'monday' then 1
                    when 'tuesday' then 2
                    when 'wednesday' then 3
                    when 'thursday' then 4
                    when 'friday' then 5
                    when 'saturday' then 6
                    when 'sunday' then 7
                    else 8
                end"
            )
            ->orderBy('sort_order')
            ->get();

        return $this->success(
            PlaceOpeningHourResource::collection($openingHours)->resolve($request),
            'Place opening hours retrieved successfully.'
        );
    }

    public function store(Request $request, Place $place): JsonResponse
    {
        $data = $this->validatedData($request, $place);
        $isClosed = (bool) ($data['is_closed'] ?? false);

        $openingHour = $place->openingHours()->create([
            'day_of_week' => $data['day_of_week'],
            'sort_order' => $data['sort_order'] ?? 0,
            'open_time' => $isClosed ? null : ($data['open_time'] ?? null),
            'close_time' => $isClosed ? null : ($data['close_time'] ?? null),
            'is_closed' => $isClosed,
            'notes' => $data['notes'] ?? null,
        ]);

        $place->update(['updated_by' => $request->user()->id]);

        return $this->success(
            (new PlaceOpeningHourResource($openingHour))->resolve($request),
            'Place opening hour created successfully.',
            201
        );
    }

    public function update(
        Request $request,
        Place $place,
        PlaceOpeningHour $openingHour
    ): JsonResponse {
        $this->ensureBelongsToPlace($place, $openingHour);

        $data = $this->validatedData($request, $place, $openingHour);
        $isClosed = (bool) ($data['is_closed'] ?? $openingHour->is_closed);

        $openingHour->update([
            ...array_intersect_key($data, array_flip(['day_of_week', 'sort_order', 'notes'])),
            'open_time' => $isClosed ? null : ($data['open_time'] ?? $openingHour->open_time),
            'close_time' => $isClosed ? null : ($data['close_time'] ?? $openingHour->close_time),
            'is_closed' => $isClosed,
        ]);

        $place->update(['updated_by' => $request->user()->id]);

        return $this->success(
            (new PlaceOpeningHourResource($openingHour->refresh()))->resolve($request),
            'Place opening hour updated successfully.'
        );
    }

    public function destroy(Request $request, Place $place, PlaceOpeningHour $openingHour): JsonResponse
    {
        $this->ensureBelongsToPlace($place, $openingHour);

        $openingHour->delete();
        $place->update(['updated_by' => $request->user()->id]);

        return $this->success(null, 'Place opening hour deleted successfully.');
    }

    private function validatedData(
        Request $request,
        Place $place,
        ?PlaceOpeningHour $openingHour = null
    ): array {
        $required = $openingHour ? 'sometimes' : 'required';

        return $request->validate([
            'day_of_week' => [$required, 'string', Rule::in(self::DAYS)],
            'sort_order' => [$openingHour ? 'sometimes' : 'required', 'integer', 'min:0'],
            'open_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'close_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'is_closed' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
    }

    private function ensureBelongsToPlace(Place $place, PlaceOpeningHour $openingHour): void
    {
        abort_if($openingHour->place_id !== $place->id, 404);
    }
}
