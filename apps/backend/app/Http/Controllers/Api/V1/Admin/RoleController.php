<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\RoleResource;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Role::query()
            ->withCount('users');

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

        $roles = $query
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $roles,
            RoleResource::class,
            'Admin roles retrieved successfully.'
        );
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        return $this->success(
            (new RoleResource($role->loadCount('users')))->resolve($request),
            'Admin role detail retrieved successfully.'
        );
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
