<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Storage\SupabaseStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class UploadController extends Controller
{
    use RespondsWithApiResponse;

    public function image(Request $request, SupabaseStorageService $storage): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'directory' => ['sometimes', 'string', 'max:100'],
        ]);

        try {
            $upload = $storage->uploadImage(
                $data['image'],
                $data['directory'] ?? 'places'
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], 502);
        }

        return $this->success(
            $upload,
            'Image uploaded successfully.',
            201
        );
    }
}
