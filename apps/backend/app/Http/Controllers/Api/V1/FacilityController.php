<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FacilityResource;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;

class FacilityController extends Controller
{
    use RespondsWithApiResponse;

    public function index(): JsonResponse
    {
        $facilities = Facility::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success(
            FacilityResource::collection($facilities)->resolve(request()),
            'Facilities retrieved successfully.'
        );
    }
}
