<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
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

        $query = Category::query()
            ->withCount(['places', 'itineraries']);

        if (($filters['with_trashed'] ?? false) === true) {
            $query->withTrashed();
        }

        $this->applyFilters($query, $filters);

        $categories = $query
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $categories,
            CategoryResource::class,
            'Admin categories retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $category = Category::create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->success(
            (new CategoryResource($category->loadCount(['places', 'itineraries'])))->resolve($request),
            'Category created successfully.',
            201
        );
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        return $this->success(
            (new CategoryResource($category->loadCount(['places', 'itineraries'])))->resolve($request),
            'Category detail retrieved successfully.'
        );
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $category->update($this->validatedData($request, $category));

        return $this->success(
            (new CategoryResource($category->loadCount(['places', 'itineraries'])))->resolve($request),
            'Category updated successfully.'
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->success(null, 'Category deleted successfully.');
    }

    public function restore(Request $request, int $category): JsonResponse
    {
        $category = Category::withTrashed()->findOrFail($category);
        $category->restore();

        return $this->success(
            (new CategoryResource($category->loadCount(['places', 'itineraries'])))->resolve($request),
            'Category restored successfully.'
        );
    }

    private function validatedData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [$category ? 'sometimes' : 'required', 'string', 'max:100'],
            'slug' => [
                $category ? 'sometimes' : 'required',
                'string',
                'max:120',
                Rule::unique('trp_categories', 'slug')->ignore($category?->id),
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
