<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\AiRequestResource;
use App\Models\AiRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiRequestController extends Controller
{
    use RespondsWithApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:150'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'itinerary_id' => ['sometimes', 'integer', 'exists:trp_itineraries,id'],
            'request_type' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'processing', 'success', 'failed', 'timeout'])],
            'model_name' => ['sometimes', 'string', 'max:100'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = AiRequest::query()
            ->with(['user', 'itinerary']);

        $this->applyFilters($query, $filters);

        $aiRequests = $query
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated(
            $aiRequests,
            AiRequestResource::class,
            'Admin AI requests retrieved successfully.'
        );
    }

    public function show(Request $request, AiRequest $aiRequest): JsonResponse
    {
        return $this->success(
            (new AiRequestResource($aiRequest->load(['user', 'itinerary'])))->resolve($request),
            'Admin AI request detail retrieved successfully.'
        );
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('user_prompt', 'ilike', "%{$search}%")
                    ->orWhere('error_message', 'ilike', "%{$search}%")
                    ->orWhere('model_name', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function (Builder $query) use ($search): void {
                        $query
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });
            });
        }

        foreach (['user_id', 'itinerary_id', 'request_type', 'status', 'model_name'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }
}
