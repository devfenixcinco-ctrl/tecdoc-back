<?php

use App\Http\Controllers\ClientConfigController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ManufacturersController;
use App\Http\Controllers\TecdocController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/sync-manufacturers', function () {
    return app()->call([app(ManufacturersController::class), 'syncManufacturers']);
})->middleware('throttle:tecdoc');

Route::get('/manufacturers/{type?}', function ($type = null) {
    return app(ManufacturersController::class)->getManufacturers($type);
});

Route::post('/tecdoc/{client}', TecdocController::class)->middleware('throttle:tecdoc');

Route::get('/config/{client}', ClientConfigController::class);

Route::get('/legal/{client}/{policy}', LegalController::class);

Route::post('/consents/{client}', [ConsentController::class, 'store'])->middleware('throttle:consents');
