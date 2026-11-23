<?php

namespace App\Http\Controllers;

use App\Services\AudiencePages;

class AudienceController extends Controller
{
    /**
     * Contenido de la página para fabricantes, distribuidores o talleres.
     */
    public function __invoke(string $client, string $page, AudiencePages $pages)
    {
        $document = $pages->find($client, $page);

        if (! $document) {
            return response()->json(['error' => 'Página no encontrada'], 404);
        }

        return response()->json($document);
    }
}
