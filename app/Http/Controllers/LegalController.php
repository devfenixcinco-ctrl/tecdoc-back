<?php

namespace App\Http\Controllers;

use App\Services\LegalDocuments;

class LegalController extends Controller
{
    /**
     * Política de privacidad o de cookies del cliente, en HTML.
     */
    public function __invoke(string $client, string $policy, LegalDocuments $documents)
    {
        $document = $documents->find($client, $policy);

        if (! $document) {
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }

        return response()->json($document);
    }
}
