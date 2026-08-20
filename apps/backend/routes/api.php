<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\FacilityController;
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
        });
});
