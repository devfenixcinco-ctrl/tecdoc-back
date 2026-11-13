<?php

namespace App\Seo;

/**
 * Inserta en el index.html del build del frontend los metadatos de la página,
 * su contenido y la configuración pública del cliente.
 */
class SpaRenderer
{
    public function render(string $html, PageMeta $meta, array $config, string $url): string
    {
        $robots = config('seo.allow_indexing') ? $meta->robots : 'noindex, nofollow';

        $tags = [
            '<meta name="description" content="'.e($meta->description).'">',
            '<meta name="robots" content="'.e($robots).'">',
            '<link rel="canonical" href="'.e($url).'">',
            '<script>window.__CLIENT_CONFIG__ = '.$this->json($config).'</script>',
        ];

        // Quita los valores por defecto del build para no duplicar etiquetas
        $html = preg_replace('#<meta\s+name="(description|robots)"[^>]*>\s*#i', '', $html);

        $html = $this->replace('#<html[^>]*>#i', '<html lang="'.e($config['lang'] ?? 'es').'">', $html);
        $html = $this->replace('#<title>.*?</title>#is', '<title>'.e($meta->title).'</title>', $html);
        $html = $this->replace('#</head>#i', implode("\n    ", $tags)."\n  </head>", $html);

        // Vue reemplaza este contenido al montar la aplicación
        return $this->replace('#<div id="app">\s*</div>#', '<div id="app">'.$meta->content.'</div>', $html);
    }

    private function replace(string $pattern, string $replacement, string $html): string
    {
        return preg_replace_callback($pattern, fn () => $replacement, $html, 1);
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
