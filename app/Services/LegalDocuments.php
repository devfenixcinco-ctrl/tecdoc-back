<?php

namespace App\Services;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Table\TableExtension;

/**
 * Políticas legales por cliente (Markdown en resources/legal/{cliente}). Si el
 * cliente no tiene su propio texto se usa el modelo de resources/legal/default.
 */
class LegalDocuments
{
    public const POLICIES = ['privacidad', 'cookies'];

    public function __construct(private ClientRegistry $clients)
    {
    }

    /**
     * @return array{policy: string, title: string, html: string, version: string, updated_at: string}|null
     */
    public function find(string $client, string $policy): ?array
    {
        if (! $this->clients->has($client) || ! in_array($policy, self::POLICIES, true)) {
            return null;
        }

        $path = $this->path($client, $policy);

        if (! $path) {
            return null;
        }

        $markdown = strtr(file_get_contents($path), $this->replacements($client));

        // Identifica el texto exacto que el usuario aceptó (LGPD-03); no depende de
        // la fecha del archivo, que cambia en cada despliegue
        $version = substr(sha1($markdown), 0, 12);

        $updated = date('Y-m-d', filemtime($path));
        $markdown = str_replace('{updated}', $updated, $markdown);

        preg_match('/^#\s+(.+)$/m', $markdown, $title);

        return [
            'policy' => $policy,
            'title' => trim($title[1] ?? $policy),
            'html' => Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false], [new TableExtension]),
            'version' => $version,
            'updated_at' => $updated,
        ];
    }

    private function path(string $client, string $policy): ?string
    {
        foreach ([$client, 'default'] as $folder) {
            $path = resource_path("legal/$folder/$policy.md");

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Datos del cliente que se insertan en el texto; los que faltan quedan marcados.
     */
    private function replacements(string $client): array
    {
        $settings = $this->clients->get($client);
        $footer = $settings['footer'] ?? [];
        $legal = $settings['legal'] ?? [];

        $values = [
            'site' => $settings['name'] ?? null,
            'company' => $footer['company'] ?? null,
            'tax_id' => $footer['tax_id'] ?? null,
            'address' => $footer['address'] ?? null,
            'email' => $footer['email'] ?? null,
            'phone' => $footer['phone'] ?? null,
            'dpo_name' => $legal['dpo_name'] ?? null,
            'dpo_email' => $legal['dpo_email'] ?? null,
        ];

        return collect($values)
            ->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => $value ?: '[pendiente]'])
            ->all();
    }
}
