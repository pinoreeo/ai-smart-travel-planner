<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\FacilityResource;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacilityController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'with_trashed' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Facility::query()
            ->withCount('places');

        if (($filters['with_trashed'] ?? false) === true) {
            $query->withTrashed();
        }

        $this->applyFilters($query, $filters);

        $facilities = $query
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $facilities,
            FacilityResource::class,
            'Admin facilities retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $facility = Facility::create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->success(
            (new FacilityResource($facility->loadCount('places')))->resolve($request),
            'Facility created successfully.',
            201
        );
    }

    public function show(Request $request, Facility $facility): JsonResponse
    {
        return $this->success(
            (new FacilityResource($facility->loadCount('places')))->resolve($request),
            'Facility detail retrieved successfully.'
        );
    }

    public function update(Request $request, Facility $facility): JsonResponse
    {
        $facility->update($this->validatedData($request, $facility));

        return $this->success(
            (new FacilityResource($facility->loadCount('places')))->resolve($request),
            'Facility updated successfully.'
        );
    }

    public function destroy(Facility $facility): JsonResponse
    {
        $facility->delete();

        return $this->success(null, 'Facility deleted successfully.');
    }

    public function restore(Request $request, int $facility): JsonResponse
    {
        $facility = Facility::withTrashed()->findOrFail($facility);
        $facility->restore();

        return $this->success(
            (new FacilityResource($facility->loadCount('places')))->resolve($request),
            'Facility restored successfully.'
        );
    }

    private function validatedData(Request $request, ?Facility $facility = null): array
    {
        return $request->validate([
            'name' => [$facility ? 'sometimes' : 'required', 'string', 'max:100'],
            'slug' => [
                $facility ? 'sometimes' : 'required',
                'string',
                'max:120',
                Rule::unique('trp_facilities', 'slug')->ignore($facility?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
