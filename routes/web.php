<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fuera de producción bloquea a todos los rastreadores
Route::get('/robots.txt', function () {
    $rules = config('seo.allow_indexing') ? 'Disallow:' : 'Disallow: /';

    return response("User-agent: *\n$rules\n", 200, ['Content-Type' => 'text/plain']);
});
