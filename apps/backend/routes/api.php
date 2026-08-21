<?php

use App\Http\Controllers\Api\V1\Admin\AiRequestController as AdminAiRequestController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\FacilityController as AdminFacilityController;
use App\Http\Controllers\Api\V1\Admin\ItineraryController as AdminItineraryController;
use App\Http\Controllers\Api\V1\Admin\PlaceController as AdminPlaceController;
use App\Http\Controllers\Api\V1\Admin\PlaceImageController as AdminPlaceImageController;
use App\Http\Controllers\Api\V1\Admin\PlaceImportController as AdminPlaceImportController;
use App\Http\Controllers\Api\V1\Admin\PlaceOpeningHourController as AdminPlaceOpeningHourController;
use App\Http\Controllers\Api\V1\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\RoutingController as AdminRoutingController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FacilityController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\PlaceController;
use App\Http\Controllers\Api\V1\PlannerController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\TripController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index');

    Route::prefix('places')->name('places.')->group(function (): void {
        Route::get('/', [PlaceController::class, 'index'])->name('index');
        Route::get('featured', [PlaceController::class, 'featured'])->name('featured');
        Route::get('nearby', [PlaceController::class, 'nearby'])->name('nearby');
        Route::get('search', [PlaceController::class, 'search'])->name('search');
        Route::get('{place:slug}', [PlaceController::class, 'show'])->name('show');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::prefix('favorites')->name('favorites.')->group(function (): void {
            Route::get('/', [FavoriteController::class, 'index'])->name('index');
            Route::post('{place}', [FavoriteController::class, 'store'])->name('store');
            Route::delete('{place}', [FavoriteController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('planner')->name('planner.')->group(function (): void {
            Route::post('drafts', [PlannerController::class, 'storeDraft'])->name('drafts.store');
            Route::patch('drafts/{itinerary}', [PlannerController::class, 'updateDraft'])->name('drafts.update');
            Route::post('generate', [PlannerController::class, 'generate'])->name('generate');
            Route::post('{itinerary}/modify', [PlannerController::class, 'modify'])->name('modify');
        });

        Route::prefix('trips')->name('trips.')->group(function (): void {
            Route::get('/', [TripController::class, 'index'])->name('index');
            Route::post('/', [TripController::class, 'store'])->name('store');
            Route::get('{itinerary}', [TripController::class, 'show'])->name('show');
            Route::patch('{itinerary}', [TripController::class, 'update'])->name('update');
            Route::delete('{itinerary}', [TripController::class, 'destroy'])->name('destroy');
            Route::post('{itinerary}/save', [TripController::class, 'save'])->name('save');
            Route::get('{itinerary}/timeline', [TripController::class, 'timeline'])->name('timeline');
            Route::get('{itinerary}/map', [TripController::class, 'map'])->name('map');
            Route::get('{itinerary}/budget', [TripController::class, 'budget'])->name('budget');
        });
    });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['auth:sanctum', 'admin'])
        ->group(function (): void {
            Route::get('health', fn () => response()->json([
                'success' => true,
                'message' => 'Admin API is available.',
                'data' => [
                    'scope' => 'admin',
                ],
            ]))->name('health');

            Route::apiResource('categories', AdminCategoryController::class);
            Route::post('categories/{category}/restore', [AdminCategoryController::class, 'restore'])
                ->name('categories.restore');

            Route::apiResource('facilities', AdminFacilityController::class);
            Route::post('facilities/{facility}/restore', [AdminFacilityController::class, 'restore'])
                ->name('facilities.restore');

            Route::apiResource('roles', AdminRoleController::class)->only(['index', 'show']);
            Route::put('users/{user}/roles', [AdminUserController::class, 'syncRoles'])
                ->name('users.roles.sync');
            Route::apiResource('users', AdminUserController::class)->only(['index', 'show', 'update']);

            Route::post('routing/preview', [AdminRoutingController::class, 'preview'])
                ->name('routing.preview');

            Route::get('place-imports/osm/preview', [AdminPlaceImportController::class, 'previewOsm'])
                ->name('place-imports.osm.preview');
            Route::post('place-imports/preview', [AdminPlaceImportController::class, 'preview'])
                ->name('place-imports.preview');
            Route::post('place-imports/{placeImport}/approve', [AdminPlaceImportController::class, 'approve'])
                ->name('place-imports.approve');
            Route::post('place-imports/{placeImport}/reject', [AdminPlaceImportController::class, 'reject'])
                ->name('place-imports.reject');
            Route::apiResource('place-imports', AdminPlaceImportController::class)
                ->except(['create', 'edit'])
                ->parameters(['place-imports' => 'placeImport']);

            Route::put('places/{place}/categories', [AdminPlaceController::class, 'syncCategories'])
                ->name('places.categories.sync');
            Route::put('places/{place}/facilities', [AdminPlaceController::class, 'syncFacilities'])
                ->name('places.facilities.sync');
            Route::post('places/{place}/publish', [AdminPlaceController::class, 'publish'])
                ->name('places.publish');
            Route::post('places/{place}/unpublish', [AdminPlaceController::class, 'unpublish'])
                ->name('places.unpublish');
            Route::post('places/{place}/restore', [AdminPlaceController::class, 'restore'])
                ->name('places.restore');
            Route::apiResource('places', AdminPlaceController::class);

            Route::prefix('places/{place}/opening-hours')
                ->name('places.opening-hours.')
                ->group(function (): void {
                    Route::get('/', [AdminPlaceOpeningHourController::class, 'index'])->name('index');
                    Route::post('/', [AdminPlaceOpeningHourController::class, 'store'])->name('store');
                    Route::patch('{openingHour}', [AdminPlaceOpeningHourController::class, 'update'])->name('update');
                    Route::delete('{openingHour}', [AdminPlaceOpeningHourController::class, 'destroy'])->name('destroy');
                });

            Route::prefix('places/{place}/images')
                ->name('places.images.')
                ->group(function (): void {
                    Route::get('/', [AdminPlaceImageController::class, 'index'])->name('index');
                    Route::post('/', [AdminPlaceImageController::class, 'store'])->name('store');
                    Route::patch('{image}', [AdminPlaceImageController::class, 'update'])->name('update');
                    Route::delete('{image}', [AdminPlaceImageController::class, 'destroy'])->name('destroy');
                    Route::post('{image}/primary', [AdminPlaceImageController::class, 'setPrimary'])->name('primary');
                });

            Route::apiResource('itineraries', AdminItineraryController::class)->only(['index', 'show']);
            Route::apiResource('ai-requests', AdminAiRequestController::class)
                ->only(['index', 'show'])
                ->parameters(['ai-requests' => 'aiRequest']);
        });
});
