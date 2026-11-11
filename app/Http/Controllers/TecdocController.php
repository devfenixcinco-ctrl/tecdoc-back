<?php

namespace App\Http\Controllers;

use App\Services\TecdocClient;
use Illuminate\Http\Request;
use RuntimeException;

class TecdocController extends Controller
{
    /**
     * Proxy hacia TecDoc. Recibe el mismo cuerpo que espera el webservice,
     * por ejemplo { "getArticles": { ... } }, sin clave ni provider.
     */
    public function __invoke(Request $request, string $client, TecdocClient $tecdoc)
    {
        if (! $tecdoc->hasClient($client)) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        $payload = $request->json()->all();
        $method = array_key_first($payload);

        if (count($payload) !== 1
            || ! in_array($method, config('tecdoc.allowed_methods'), true)
            || ! is_array($payload[$method])) {
            return response()->json(['error' => 'Solicitud no permitida'], 422);
        }

        try {
            return response()->json($tecdoc->call($client, $method, $payload[$method]));
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Error al consultar TecDoc'], 502);
        }
    }
}
