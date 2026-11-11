<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TecdocClient
{
    public function hasClient(string $client): bool
    {
        return ! empty(config("tecdoc.clients.$client.api_key"));
    }

    /**
     * Llama a un método de TecDoc con las credenciales del cliente indicado.
     */
    public function call(string $client, string $method, array $params): array
    {
        $config = config("tecdoc.clients.$client");

        if (empty($config['api_key'])) {
            throw new RuntimeException("Cliente TecDoc no configurado: $client");
        }

        // El provider siempre lo define el servidor, nunca el navegador
        $params['provider'] = $config['provider'];

        return $this->request($config['api_key'], $method, $params);
    }

    private function request(string $apiKey, string $method, array $params): array
    {
        $response = Http::withHeaders(['X-Api-Key' => $apiKey])
            ->acceptJson()
            ->timeout(config('tecdoc.timeout'))
            ->post(config('tecdoc.url'), [$method => $params]);

        $body = $response->json();

        // TecDoc puede responder HTTP 200 con un status de error en el cuerpo
        if (! $response->successful() || ! is_array($body) || (isset($body['status']) && $body['status'] != 200)) {
            Log::warning('Error en TecDoc', [
                'method' => $method,
                'http_status' => $response->status(),
                'status' => $body['status'] ?? null,
                'message' => $body['statusText'] ?? null,
            ]);

            throw new RuntimeException('Error al consultar TecDoc');
        }

        return $body;
    }
}
