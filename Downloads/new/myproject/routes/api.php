<?php

use App\Http\Controllers\GeoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/countries', [GeoController::class, 'countries']);
Route::get('/countries/{country}', [GeoController::class, 'country']);
Route::get('/countries/{country}/cities', [GeoController::class, 'countryCities']);
Route::get('/cities', [GeoController::class, 'cities']);
Route::get('/cities/search', [GeoController::class, 'citySearch']);
Route::get('/cities/nearby', [GeoController::class, 'nearby']);
