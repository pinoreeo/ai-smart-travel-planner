<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use RespondsWithApiResponse;

    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success(
            CategoryResource::collection($categories)->resolve(request()),
            'Categories retrieved successfully.'
        );
    }
}
