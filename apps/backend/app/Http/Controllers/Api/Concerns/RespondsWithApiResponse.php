<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

trait RespondsWithApiResponse
{
    protected function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'OK'
    ): JsonResponse {
        /** @var class-string<JsonResource> $resourceClass */
        return $this->success(
            $resourceClass::collection($paginator->getCollection())->resolve(request()),
            $message,
            200,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]
        );
    }
}
