<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockIndexing
{
    /**
     * En entornos no productivos indica a los buscadores que no indexen nada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('seo.allow_indexing')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
