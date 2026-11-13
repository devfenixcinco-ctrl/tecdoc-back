<?php

namespace App\Http\Controllers;

use App\Services\ClientRegistry;

class ClientConfigController extends Controller
{
    /**
     * Configuración pública del cliente (sin credenciales).
     */
    public function __invoke(string $client, ClientRegistry $clients)
    {
        if (! $clients->has($client)) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        return response()->json($clients->publicConfig($client));
    }
}
