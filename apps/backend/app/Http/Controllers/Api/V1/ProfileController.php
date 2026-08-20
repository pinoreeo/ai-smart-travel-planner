<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use RespondsWithApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return $this->success(
            (new UserResource($user))->resolve($request),
            'Profile retrieved successfully.'
        );
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->fill($data);

        if (array_key_exists('email', $data) && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $user->load('roles');

        return $this->success(
            (new UserResource($user))->resolve($request),
            'Profile updated successfully.'
        );
    }
}
