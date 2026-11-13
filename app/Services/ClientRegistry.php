<?php

namespace App\Services;

use Illuminate\Support\Arr;

class ClientRegistry
{
    public function has(string $client): bool
    {
        return is_array(config("clients.clients.$client"));
    }

    public function get(string $client): array
    {
        return config("clients.clients.$client", []);
    }

    public function default(): string
    {
        return config('clients.default');
    }

    /**
     * Cliente cuyo dominio coincide con el host de la petición; si ninguno
     * coincide se usa el cliente por defecto.
     */
    public function resolveByHost(string $host): string
    {
        foreach (config('clients.clients') as $id => $client) {
            $domains = array_map('trim', explode(',', $client['domains'] ?? ''));

            if (in_array(strtolower($host), array_map('strtolower', $domains), true)) {
                return $id;
            }
        }

        return $this->default();
    }

    /**
     * Valores que puede ver el navegador: excluye credenciales y dominios.
     */
    public function publicConfig(string $client): array
    {
        return ['id' => $client] + Arr::except($this->get($client), ['tecdoc', 'domains']);
    }
}
