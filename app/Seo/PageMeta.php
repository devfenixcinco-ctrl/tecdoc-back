<?php

namespace App\Seo;

/**
 * Metadatos y contenido de una página del frontend para los buscadores.
 */
class PageMeta
{
    public function __construct(
        public string $title,
        public string $description,
        public string $content = '',
        public string $robots = 'index, follow',
        public int $status = 200,
        // Imagen propia de la página (p. ej. foto del producto); si es null se usa la del cliente
        public ?string $image = null,
        public string $type = 'website',
    ) {
    }
}
