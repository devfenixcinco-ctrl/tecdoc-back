<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// Fuera de producción bloquea a todos los rastreadores
Route::get('/robots.txt', function () {
    $rules = config('seo.allow_indexing') ? 'Disallow:' : 'Disallow: /';

    return response("User-agent: *\n$rules\n", 200, ['Content-Type' => 'text/plain']);
});

// Cualquier otra ruta es del frontend (Vue Router)
Route::get('/{path?}', SpaController::class)->where('path', '^(?!api(/|$)|up$).*');
