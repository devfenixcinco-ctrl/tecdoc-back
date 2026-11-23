<?php

namespace App\Services;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Table\TableExtension;

/**
 * Textos por cliente en Markdown: resources/{sección}/{cliente}/{nombre}.md y, si el
 * cliente no tiene el suyo, el texto modelo de resources/{sección}/default/{nombre}.md.
 * Los marcadores {company}, {phone}, etc. se completan con la configuración del cliente.
 */
class ClientContent
{
    public function __construct(private ClientRegistry $clients)
    {
    }

    /**
     * @return array{title: string, html: string, version: string, updated_at: string}|null
     */
    public function find(string $client, string $section, string $name): ?array
    {
        if (! $this->clients->has($client)) {
            return null;
        }

        $path = $this->path($section, $client, $name);

        if (! $path) {
            return null;
        }

        $markdown = strtr(file_get_contents($path), $this->replacements($client));

        // Identifica el texto exacto (p. ej. la política que el usuario aceptó); no
        // depende de la fecha del archivo, que cambia en cada despliegue
        $version = substr(sha1($markdown), 0, 12);

        $updated = date('Y-m-d', filemtime($path));
        $markdown = str_replace('{updated}', $updated, $markdown);

        preg_match('/^#\s+(.+)$/m', $markdown, $title);

        return [
            'title' => trim($title[1] ?? $name),
            'html' => Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false], [new TableExtension]),
            'version' => $version,
            'updated_at' => $updated,
        ];
    }

    private function path(string $section, string $client, string $name): ?string
    {
        foreach ([$client, 'default'] as $folder) {
            $path = resource_path("$section/$folder/$name.md");

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
