<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:150'],
            'role' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()
            ->with(['roles' => fn ($query) => $query->orderBy('name')])
            ->withCount(['itineraries', 'favorites', 'aiRequests', 'createdPlaces', 'uploadedPlaceImages']);

        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $roles = $this->splitValues($filters['role']);

            $query->whereHas('roles', function (Builder $query) use ($roles): void {
                $query->whereIn('slug', $roles);
            });
        }

        $users = $query
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $users,
            UserResource::class,
            'Admin users retrieved successfully.'
        );
    }

    public function show(Request $request, User $user): JsonResponse
    {
        return $this->success(
            (new UserResource($this->loadUser($user)))->resolve($request),
            'Admin user detail retrieved successfully.'
        );
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'string', 'min:8'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'distinct', Rule::exists('trp_roles', 'id')->where('is_active', true)],
            'role_slugs' => ['sometimes', 'array'],
            'role_slugs.*' => ['string', 'distinct', Rule::exists('trp_roles', 'slug')->where('is_active', true)],
        ]);

        $user->update($this->userAttributes($data));
        $this->syncRolesFromPayload($user, $data);

        return $this->success(
            (new UserResource($this->loadUser($user)))->resolve($request),
            'User updated successfully.'
        );
    }

    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'distinct', Rule::exists('trp_roles', 'id')->where('is_active', true)],
            'role_slugs' => ['sometimes', 'array'],
            'role_slugs.*' => ['string', 'distinct', Rule::exists('trp_roles', 'slug')->where('is_active', true)],
        ]);

        abort_if(! array_key_exists('role_ids', $data) && ! array_key_exists('role_slugs', $data), 422, 'role_ids or role_slugs is required.');

        $this->syncRolesFromPayload($user, $data);

        return $this->success(
            (new UserResource($this->loadUser($user)))->resolve($request),
            'User roles synced successfully.'
        );
    }

    private function userAttributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name',
            'email',
            'password',
        ]));
    }

    private function syncRolesFromPayload(User $user, array $data): void
    {
        if (! array_key_exists('role_ids', $data) && ! array_key_exists('role_slugs', $data)) {
            return;
        }

        $roleIds = $data['role_ids'] ?? [];

        if (! empty($data['role_slugs'])) {
            $roleIds = [
                ...$roleIds,
                ...Role::query()
                    ->where('is_active', true)
                    ->whereIn('slug', $data['role_slugs'])
                    ->pluck('id')
                    ->all(),
            ];
        }

        $user->roles()->sync(array_values(array_unique($roleIds)));
    }

    private function loadUser(User $user): User
    {
        return $user->load([
            'roles' => fn ($query) => $query->orderBy('name'),
        ])->loadCount(['itineraries', 'favorites', 'aiRequests', 'createdPlaces', 'uploadedPlaceImages']);
    }

    private function splitValues(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value)
        )));
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
