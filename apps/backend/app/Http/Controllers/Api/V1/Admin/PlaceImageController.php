<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\PlaceImageResource;
use App\Models\Place;
use App\Models\PlaceImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceImageController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request, Place $place): JsonResponse
    {
        $images = $place->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->success(
            PlaceImageResource::collection($images)->resolve($request),
            'Place images retrieved successfully.'
        );
    }

    public function store(Request $request, Place $place): JsonResponse
    {
        $data = $this->validatedData($request);

        $image = DB::transaction(function () use ($data, $place, $request): PlaceImage {
            $isPrimary = (bool) ($data['is_primary'] ?? false);

            if ($isPrimary) {
                $place->images()->update(['is_primary' => false]);
            }

            $image = $place->images()->create([
                'image_url' => $data['image_url'],
                'public_id' => $data['public_id'] ?? null,
                'caption' => $data['caption'] ?? null,
                'alt_text' => $data['alt_text'] ?? null,
                'is_primary' => $isPrimary || ! $place->images()->exists(),
                'sort_order' => $data['sort_order'] ?? 0,
                'uploaded_by' => $request->user()->id,
            ]);

            $place->update(['updated_by' => $request->user()->id]);

            return $image;
        });

        return $this->success(
            (new PlaceImageResource($image))->resolve($request),
            'Place image created successfully.',
            201
        );
    }

    public function update(Request $request, Place $place, PlaceImage $image): JsonResponse
    {
        $this->ensureBelongsToPlace($place, $image);

        $data = $this->validatedData($request, $image);

        DB::transaction(function () use ($data, $place, $image, $request): void {
            if (($data['is_primary'] ?? false) === true) {
                $place->images()->whereKeyNot($image->id)->update(['is_primary' => false]);
            }

            $image->update(array_intersect_key($data, array_flip([
                'image_url',
                'public_id',
                'caption',
                'alt_text',
                'is_primary',
                'sort_order',
            ])));

            if (! $place->images()->where('is_primary', true)->exists()) {
                $image->update(['is_primary' => true]);
            }

            $place->update(['updated_by' => $request->user()->id]);
        });

        return $this->success(
            (new PlaceImageResource($image->refresh()))->resolve($request),
            'Place image updated successfully.'
        );
    }

    public function destroy(Request $request, Place $place, PlaceImage $image): JsonResponse
    {
        $this->ensureBelongsToPlace($place, $image);

        DB::transaction(function () use ($place, $image, $request): void {
            $wasPrimary = $image->is_primary;
            $image->delete();

            if ($wasPrimary) {
                $place->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()
                    ?->update(['is_primary' => true]);
            }

            $place->update(['updated_by' => $request->user()->id]);
        });

        return $this->success(null, 'Place image deleted successfully.');
    }

    public function setPrimary(Request $request, Place $place, PlaceImage $image): JsonResponse
    {
        $this->ensureBelongsToPlace($place, $image);

        DB::transaction(function () use ($place, $image, $request): void {
            $place->images()->whereKeyNot($image->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
            $place->update(['updated_by' => $request->user()->id]);
        });

        return $this->success(
            (new PlaceImageResource($image->refresh()))->resolve($request),
            'Primary place image updated successfully.'
        );
    }

    private function validatedData(Request $request, ?PlaceImage $image = null): array
    {
        return $request->validate([
            'image_url' => [$image ? 'sometimes' : 'required', 'url'],
            'public_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:255'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
    }

    private function ensureBelongsToPlace(Place $place, PlaceImage $image): void
    {
        abort_if($image->place_id !== $place->id, 404);
    }
}
