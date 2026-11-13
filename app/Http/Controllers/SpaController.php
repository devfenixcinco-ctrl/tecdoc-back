<?php

namespace App\Http\Controllers;

use App\Seo\SeoResolver;
use App\Seo\SpaRenderer;
use App\Services\ClientRegistry;
use Illuminate\Http\Request;

class SpaController extends Controller
{
    /**
     * Sirve el frontend (build de Vite) con los metadatos y el contenido de la
     * ruta ya incluidos en el HTML, para que los buscadores no dependan de JS.
     */
    public function __invoke(Request $request, SeoResolver $seo, SpaRenderer $renderer, ClientRegistry $clients)
    {
        $index = config('seo.spa_index');

        abort_unless(is_file($index), 503, 'No se encontró el build del frontend');

        $client = $clients->resolveByHost($request->getHost());
        $path = $request->path() === '/' ? '' : $request->path();
        $meta = $seo->resolve($path, $client);

        $html = $renderer->render(file_get_contents($index), $meta, $clients->publicConfig($client), $request->url());

        return response($html, $meta->status)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-cache');
    }
}
