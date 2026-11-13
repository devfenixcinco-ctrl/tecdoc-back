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
        $lang = $config['lang'] ?? 'es';
        $image = $this->absolute($meta->image ?? $config['seo']['og_image'] ?? '');
        $favicon = $config['seo']['favicon'] ?? null;

        $tags = [
            '<meta name="description" content="'.e($meta->description).'">',
            '<meta name="robots" content="'.e($robots).'">',
            '<link rel="canonical" href="'.e($url).'">',

            // Vista previa en redes sociales y mensajería (Open Graph / Twitter)
            '<meta property="og:type" content="'.e($meta->type).'">',
            '<meta property="og:site_name" content="'.e($config['name'] ?? '').'">',
            '<meta property="og:locale" content="'.e($lang.'_'.($config['country'] ?? '')).'">',
            '<meta property="og:title" content="'.e($meta->title).'">',
            '<meta property="og:description" content="'.e($meta->description).'">',
            '<meta property="og:url" content="'.e($url).'">',
            '<meta property="og:image" content="'.e($image).'">',
            '<meta name="twitter:card" content="'.($meta->image ? 'summary_large_image' : 'summary').'">',
            '<meta name="twitter:title" content="'.e($meta->title).'">',
            '<meta name="twitter:description" content="'.e($meta->description).'">',
            '<meta name="twitter:image" content="'.e($image).'">',

            '<script>window.__CLIENT_CONFIG__ = '.$this->json($config).'</script>',
        ];

        if ($favicon) {
            $tags[] = '<link rel="icon" href="'.e($favicon).'">';
        }

        // Quita los valores por defecto del build para no duplicar etiquetas
        $html = preg_replace('#<meta\s+name="(description|robots)"[^>]*>\s*#i', '', $html);

        if ($favicon) {
            $html = preg_replace('#<link\s+rel="icon"[^>]*>\s*#i', '', $html);
        }

        $html = $this->replace('#<html[^>]*>#i', '<html lang="'.e($lang).'">', $html);
        $html = $this->replace('#<title>.*?</title>#is', '<title>'.e($meta->title).'</title>', $html);
        $html = $this->replace('#</head>#i', implode("\n    ", $tags)."\n  </head>", $html);

        // Vue reemplaza este contenido al montar la aplicación
        return $this->replace('#<div id="app">\s*</div>#', '<div id="app">'.$meta->content.'</div>', $html);
    }

    // Open Graph exige URLs absolutas
    private function absolute(string $url): string
    {
        return $url === '' || preg_match('#^https?://#i', $url) ? $url : url($url);
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
