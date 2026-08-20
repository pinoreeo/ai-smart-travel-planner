<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FacilityController;
use App\Http\Controllers\Api\V1\PlaceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

$notImplemented = static fn (string $endpoint): JsonResponse => response()->json([
    'success' => false,
    'message' => 'Endpoint is registered but not implemented yet.',
    'data' => [
        'endpoint' => $endpoint,
    ],
], 501);

Route::prefix('v1')->name('api.v1.')->group(function () use ($notImplemented): void {
    Route::prefix('auth')->name('auth.')->group(function () use ($notImplemented): void {
        Route::post('register', fn (): JsonResponse => $notImplemented('auth.register'))->name('register');
        Route::post('login', fn (): JsonResponse => $notImplemented('auth.login'))->name('login');

        Route::get('me', fn (): JsonResponse => $notImplemented('auth.me'))->name('me');
        Route::post('logout', fn (): JsonResponse => $notImplemented('auth.logout'))->name('logout');
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

    Route::group([], function () use ($notImplemented): void {
        Route::get('profile', fn (): JsonResponse => $notImplemented('profile.show'))->name('profile.show');
        Route::patch('profile', fn (): JsonResponse => $notImplemented('profile.update'))->name('profile.update');

        Route::prefix('favorites')->name('favorites.')->group(function () use ($notImplemented): void {
            Route::get('/', fn (): JsonResponse => $notImplemented('favorites.index'))->name('index');
            Route::post('{place}', fn (): JsonResponse => $notImplemented('favorites.store'))->name('store');
            Route::delete('{place}', fn (): JsonResponse => $notImplemented('favorites.destroy'))->name('destroy');
        });

        Route::prefix('planner')->name('planner.')->group(function () use ($notImplemented): void {
            Route::post('drafts', fn (): JsonResponse => $notImplemented('planner.drafts.store'))->name('drafts.store');
            Route::patch('drafts/{itinerary}', fn (): JsonResponse => $notImplemented('planner.drafts.update'))->name('drafts.update');
            Route::post('generate', fn (): JsonResponse => $notImplemented('planner.generate'))->name('generate');
            Route::post('{itinerary}/modify', fn (): JsonResponse => $notImplemented('planner.modify'))->name('modify');
        });

        Route::prefix('trips')->name('trips.')->group(function () use ($notImplemented): void {
            Route::get('/', fn (): JsonResponse => $notImplemented('trips.index'))->name('index');
            Route::post('/', fn (): JsonResponse => $notImplemented('trips.store'))->name('store');
            Route::get('{itinerary}', fn (): JsonResponse => $notImplemented('trips.show'))->name('show');
            Route::patch('{itinerary}', fn (): JsonResponse => $notImplemented('trips.update'))->name('update');
            Route::delete('{itinerary}', fn (): JsonResponse => $notImplemented('trips.destroy'))->name('destroy');
            Route::post('{itinerary}/save', fn (): JsonResponse => $notImplemented('trips.save'))->name('save');
            Route::get('{itinerary}/timeline', fn (): JsonResponse => $notImplemented('trips.timeline'))->name('timeline');
            Route::get('{itinerary}/map', fn (): JsonResponse => $notImplemented('trips.map'))->name('map');
            Route::get('{itinerary}/budget', fn (): JsonResponse => $notImplemented('trips.budget'))->name('budget');
        });
    });
});
